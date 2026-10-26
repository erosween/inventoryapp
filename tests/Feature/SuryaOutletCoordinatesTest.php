<?php

namespace Tests\Feature;

use App\Http\Controllers\SuryaVisitReportController;
use App\Models\SuryaVisit;
use App\Models\User;
use App\Services\OptimizedImageStorage;
use App\Services\SuryaOutletCoordinates;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SuryaOutletCoordinatesTest extends TestCase
{
    use DatabaseTransactions;

    private function outlet(int $id): void
    {
        DB::table('appsdumais')->updateOrInsert(['id_outlet' => $id], [
            'nama_outlet' => 'GPS Regression '.$id, 'tap' => 'BENGKALIS',
            'sf' => 'GPS SALES TEST', 'pjp' => 'Senin',
            'latitude' => 0.53449532, 'longitude' => 101.42113987,
        ]);
    }

    private function visit(int $id, array $attributes = []): int
    {
        return DB::table('surya_visits')->insertGetId(array_merge([
            'id_outlet' => (string) $id, 'idtap' => 'BENGKALIS', 'idsf' => 'SF-GPS-TEST',
            'visited_at' => now()->subMinute(), 'latitude' => 1.470001, 'longitude' => 102.120001,
            'gps_status' => 'approved', 'visit_type' => 'pjp',
            'photo_front_path' => 'surya-visits/front/gps.webp', 'photo_inside_path' => 'surya-visits/inside/gps.webp',
            'created_at' => now(), 'updated_at' => now(),
        ], $attributes));
    }

    private function admin(): void
    {
        $this->actingAs(User::where('username', 'admin_super')->firstOrFail())
            ->withSession(['idtap' => 'SBP_DUMAI']);
    }

    public static function approvalMethods(): array
    {
        return ['automatic' => ['automatic'], 'manual' => ['manual']];
    }

    #[DataProvider('approvalMethods')]
    public function test_far_visit_is_rejected_then_retaken_and_approval_uses_only_the_new_photo_gps(string $method): void
    {
        $this->freezeTime();
        Storage::fake('public');
        $id = 990011014;
        $this->outlet($id);
        $this->admin();
        $this->withSession(['mobile_sf_id' => 'SF-GPS-TEST', 'mobile_sf_name' => 'GPS SALES TEST', 'idtap' => 'BENGKALIS']);
        $this->mock(OptimizedImageStorage::class, function ($mock) {
            $mock->shouldReceive('isLikelyBlack')->andReturn(false);
            $mock->shouldReceive('store')->times(4)->andReturn(
                ['path' => 'surya-visits/first-front.webp'], ['path' => 'surya-visits/first-inside.webp'],
                ['path' => 'surya-visits/retake-front.webp'], ['path' => 'surya-visits/retake-inside.webp'],
            );
        });
        $payload = fn ($lat, $lng) => [
            'id_outlet' => (string) $id, 'visit_type' => 'pjp', 'latitude' => $lat, 'longitude' => $lng, 'accuracy' => 7,
            'photo_front' => UploadedFile::fake()->image('front.jpg'), 'photo_inside' => UploadedFile::fake()->image('inside.jpg'),
            'branding_dominance' => 'Tsel', 'branding_quality' => 'Bagus',
        ];
        $this->post(route('mobile.visit.store'), $payload(1.480325, 102.1108652))
            ->assertSessionHasNoErrors()->assertRedirect();
        $first = SuryaVisit::where('id_outlet', (string) $id)->firstOrFail();
        $this->assertSame('pending_admin', $first->gps_status);
        $this->assertGreaterThan(100000, $first->distance_meters);
        $this->assertEqualsWithDelta(0.53449532, DB::table('appsdumais')->where('id_outlet', $id)->value('latitude'), 0.00000001);
        $this->post(route('surya.visits.reject', $first))->assertRedirect();
        $this->assertSame('rejected', $first->fresh()->gps_status);

        // The old 15-second window has passed, but a retake must restart it.
        $this->travel(1)->minutes();
        $this->post(route('mobile.visit.store'), $payload(1.490325, 102.1308652))
            ->assertSessionHasNoErrors()->assertRedirect();
        $retake = $first->fresh();
        $this->assertSame(1, SuryaVisit::where('id_outlet', (string) $id)->count());
        $this->assertSame('pending_admin', $retake->gps_status);
        $this->assertSame('surya-visits/retake-front.webp', $retake->photo_front_path);
        $this->assertEqualsWithDelta(1.490325, $retake->latitude, 0.00000001);
        $this->assertEqualsWithDelta(102.1308652, $retake->longitude, 0.00000001);
        $this->getJson(route('mobile.visit.status', ['id_outlet' => (string) $id]))->assertOk()
            ->assertJson(['gps_status' => 'pending_admin', 'can_sell' => false]);
        $this->assertEqualsWithDelta(0.53449532, DB::table('appsdumais')->where('id_outlet', $id)->value('latitude'), 0.00000001);

        if ($method === 'automatic') {
            $this->travel(14)->seconds();
            $this->getJson(route('mobile.visit.status', ['id_outlet' => (string) $id]))->assertOk()->assertJson(['gps_status' => 'pending_admin']);
            $this->travel(1)->seconds();
            $this->getJson(route('mobile.visit.status', ['id_outlet' => (string) $id]))->assertOk()
                ->assertJson(['gps_status' => 'approved', 'can_sell' => true]);
        } else {
            $this->post(route('surya.visits.approve', $retake))->assertRedirect();
        }
        $master = DB::table('appsdumais')->where('id_outlet', $id)->first();
        $this->assertSame('approved', $retake->fresh()->gps_status);
        $this->assertEqualsWithDelta(1.490325, $master->latitude, 0.00000001);
        $this->assertEqualsWithDelta(102.1308652, $master->longitude, 0.00000001);
        $search = $this->getJson('/surya/search?keyword='.$id)->assertOk()->json('0');
        $this->assertSame('verified', $search['map_visit_state']);
        $this->assertEqualsWithDelta(1.490325, $search['latitude'], 0.00000001);
        $this->assertEqualsWithDelta(102.1308652, $search['longitude'], 0.00000001);
    }

    public function test_nearby_is_measured_against_the_current_confirmed_point_not_a_stale_master(): void
    {
        $this->freezeTime();
        $id = 990011015;
        $this->outlet($id);
        $this->visit($id, ['idsf' => 'SF-GPS-OTHER']);
        $this->mock(OptimizedImageStorage::class, function ($mock) {
            $mock->shouldReceive('isLikelyBlack')->andReturn(false);
            $mock->shouldReceive('store')->twice()->andReturn(['path' => 'surya-visits/near-point.webp']);
        });
        $this->withSession(['mobile_sf_id' => 'SF-GPS-TEST', 'mobile_sf_name' => 'GPS SALES TEST', 'idtap' => 'BENGKALIS'])
            ->post(route('mobile.visit.store'), [
                'id_outlet' => (string) $id, 'visit_type' => 'pjp', 'latitude' => 1.470011, 'longitude' => 102.120001,
                'photo_front' => UploadedFile::fake()->image('front.jpg'), 'photo_inside' => UploadedFile::fake()->image('inside.jpg'),
                'branding_dominance' => 'Tsel', 'branding_quality' => 'Bagus',
            ])->assertSessionHasNoErrors()->assertRedirect();
        $visit = SuryaVisit::where('id_outlet', (string) $id)->latest('visited_at')->firstOrFail();
        $this->assertSame('valid', $visit->gps_status);
        $this->assertLessThan(10, $visit->distance_meters);
        $this->assertEqualsWithDelta(1.470011, DB::table('appsdumais')->where('id_outlet', $id)->value('latitude'), 0.00000001);
    }

    public function test_photos_do_not_make_rejected_or_pending_visits_look_approved_and_map_exposes_gps_source(): void
    {
        $this->freezeTime();
        $this->outlet(990011011);
        $this->outlet(990011012);
        $this->outlet(990011013);
        $this->visit(990011011, ['gps_status' => 'rejected']);
        $this->visit(990011012, ['gps_status' => 'pending_admin', 'visited_at' => now()]);
        $this->visit(990011013, ['latitude' => 0.53449532, 'longitude' => 101.42113987]);
        $this->admin();
        $rows = collect($this->getJson('/surya/outlets')->assertOk()->json())->keyBy('id_outlet');
        foreach ([990011011 => 'rejected', 990011012 => 'pending'] as $id => $state) {
            $row = $rows[$id];
            $this->assertTrue($row['has_front_photo']);
            $this->assertSame($state, $row['map_visit_state']);
            $this->assertSame('master', $row['coordinate_source']);
            $this->assertEqualsWithDelta(0.53449532, $row['latitude'], 0.00000001);
            $this->assertEqualsWithDelta(1.470001, $row['latest_visit_latitude'], 0.00000001);
            $search = $this->getJson('/surya/search?keyword='.$id)->assertOk()->json('0');
            $this->assertSame($state, $search['map_visit_state']);
            $this->assertSame('master', $search['coordinate_source']);
        }
        $this->assertSame('verified', $rows[990011013]['map_visit_state']);
        $this->assertSame('visit', $rows[990011013]['coordinate_source']);
        $this->assertEquals($rows[990011013]['latitude'], $rows[990011013]['latest_visit_latitude']);
    }

    public function test_all_map_and_search_results_use_visit_time_and_the_backfill_repairs_every_outlet_idempotently(): void
    {
        $this->freezeTime();
        $ids = [1201073824, 1201142452, 990011003];
        foreach ($ids as $index => $id) {
            $this->outlet($id);
            $this->visit($id, ['latitude' => 1.47 + $index / 100, 'longitude' => 102.6]);
            // An old imported visit with a higher ID must never win.
            $this->visit($id, ['visited_at' => now()->subDays(3), 'latitude' => 0.5, 'longitude' => 101.4]);
            $this->visit($id, ['visited_at' => now(), 'gps_status' => 'rejected', 'latitude' => 0.6]);
            $this->visit($id, ['visited_at' => now(), 'gps_status' => 'pending_admin', 'latitude' => 0.7]);
            $this->visit($id, ['visited_at' => now(), 'gps_status' => 'valid', 'latitude' => 91]);
            $this->visit($id, ['visited_at' => now(), 'gps_status' => 'approved', 'latitude' => 0, 'longitude' => 0]);
        }
        $this->admin();
        $map = collect($this->getJson('/surya/outlets')->assertOk()->json())->keyBy('id_outlet');
        foreach ($ids as $index => $id) {
            $expected = 1.47 + $index / 100;
            $this->assertEqualsWithDelta($expected, $map[$id]['latitude'], 0.00000001);
            $this->assertEqualsWithDelta(102.6, $map[$id]['longitude'], 0.00000001);
            $search = $this->getJson('/surya/search?keyword='.$id)->assertOk()->json('0');
            $this->assertEqualsWithDelta($expected, $search['latitude'], 0.00000001);
            $this->assertEqualsWithDelta(102.6, $search['longitude'], 0.00000001);
        }

        $this->artisan('surya:sync-outlet-coordinates', ['--dry-run' => true, '--outlet' => $ids])->assertExitCode(0);
        foreach ($ids as $id) {
            $this->assertEqualsWithDelta(0.53449532, DB::table('appsdumais')->where('id_outlet', $id)->value('latitude'), 0.00000001);
        }
        $this->artisan('surya:sync-outlet-coordinates', ['--outlet' => $ids])->assertExitCode(0);
        foreach ($ids as $index => $id) {
            $this->assertEqualsWithDelta(1.47 + $index / 100, DB::table('appsdumais')->where('id_outlet', $id)->value('latitude'), 0.00000001);
        }
        $this->assertSame(0, app(SuryaOutletCoordinates::class)->synchronize(false, $ids)['changed']);
    }

    public function test_auto_approval_of_an_older_visit_cannot_rewind_a_newer_confirmed_location(): void
    {
        $this->freezeTime();
        $id = 990011004;
        $this->outlet($id);
        $pending = $this->visit($id, ['visited_at' => now()->subHour(), 'gps_status' => 'pending_admin', 'latitude' => 0.5]);
        $this->visit($id, ['gps_status' => 'valid', 'latitude' => 1.48]);
        $this->admin();
        $this->getJson('/surya/outlets')->assertOk();
        $this->assertDatabaseHas('surya_visits', ['id' => $pending, 'gps_status' => 'approved']);
        $this->assertEqualsWithDelta(1.48, DB::table('appsdumais')->where('id_outlet', $id)->value('latitude'), 0.00000001);
    }

    public function test_equal_visit_times_use_the_latest_id_and_outlets_without_visits_keep_their_master(): void
    {
        $this->freezeTime();
        $this->outlet(990011009);
        $this->outlet(990011010);
        $this->visit(990011009, ['latitude' => 1.4]);
        $this->visit(990011009, ['latitude' => 1.5]);
        app(SuryaOutletCoordinates::class)->synchronize(false, ['990011009', '990011010']);
        $this->assertEqualsWithDelta(1.5, DB::table('appsdumais')->where('id_outlet', 990011009)->value('latitude'), 0.00000001);
        $this->assertEqualsWithDelta(0.53449532, DB::table('appsdumais')->where('id_outlet', 990011010)->value('latitude'), 0.00000001);
    }

    public function test_all_auto_approval_paths_require_complete_photos_timeout_and_valid_coordinates(): void
    {
        $this->freezeTime();
        $this->outlet(990011005);
        $incomplete = $this->visit(990011005, ['gps_status' => 'pending_admin', 'photo_inside_path' => null]);
        $recent = $this->visit(990011005, ['gps_status' => 'pending_admin', 'visited_at' => now()]);
        $invalid = $this->visit(990011005, ['gps_status' => 'pending_admin', 'latitude' => 91]);
        $rejected = $this->visit(990011005, ['gps_status' => 'rejected']);
        $this->admin();
        $this->getJson(route('surya.visits.auto-approve-pending'))->assertOk();
        foreach ([$incomplete, $recent, $invalid] as $id) {
            $this->assertDatabaseHas('surya_visits', ['id' => $id, 'gps_status' => 'pending_admin']);
        }
        $this->assertDatabaseHas('surya_visits', ['id' => $rejected, 'gps_status' => 'rejected']);
        $this->assertFalse(app(SuryaOutletCoordinates::class)->syncOutlet('990011005'));
    }

    public function test_mobile_status_approves_and_synchronizes_the_outlet(): void
    {
        $this->freezeTime();
        $id = 990011006;
        $this->outlet($id);
        $visit = $this->visit($id, ['gps_status' => 'pending_admin']);
        $this->withSession(['mobile_sf_id' => 'SF-GPS-TEST', 'mobile_sf_name' => 'GPS SALES TEST', 'idtap' => 'BENGKALIS'])
            ->getJson(route('mobile.visit.status', ['id_outlet' => (string) $id]))
            ->assertOk()->assertJson(['gps_status' => 'approved', 'can_sell' => true]);
        $this->assertDatabaseHas('surya_visits', ['id' => $visit, 'gps_status' => 'approved']);
        $this->assertEqualsWithDelta(1.470001, DB::table('appsdumais')->where('id_outlet', $id)->value('latitude'), 0.00000001);
    }

    public function test_stale_admin_approval_does_not_override_a_rejection_or_move_the_outlet(): void
    {
        $id = 990011007;
        $this->outlet($id);
        $visitId = $this->visit($id, ['gps_status' => 'pending_admin']);
        $stale = SuryaVisit::findOrFail($visitId);
        DB::table('surya_visits')->where('id', $visitId)->update(['gps_status' => 'rejected']);
        app(SuryaVisitReportController::class)->approve($stale);
        $this->assertDatabaseHas('surya_visits', ['id' => $visitId, 'gps_status' => 'rejected']);
        $this->assertEqualsWithDelta(0.53449532, DB::table('appsdumais')->where('id_outlet', $id)->value('latitude'), 0.00000001);
    }

    public function test_saving_a_valid_visit_immediately_updates_master_to_its_measured_gps(): void
    {
        $this->freezeTime();
        $id = 990011008;
        $this->outlet($id);
        $this->mock(OptimizedImageStorage::class, function ($mock) {
            $mock->shouldReceive('isLikelyBlack')->andReturn(false);
            $mock->shouldReceive('store')->twice()->andReturn(['path' => 'surya-visits/gps-test.webp']);
        });
        $this->withSession(['mobile_sf_id' => 'SF-GPS-TEST', 'mobile_sf_name' => 'GPS SALES TEST', 'idtap' => 'BENGKALIS'])
            ->post(route('mobile.visit.store'), [
                'id_outlet' => (string) $id, 'visit_type' => 'pjp',
                'latitude' => 0.53450532, 'longitude' => 101.42113987, 'accuracy' => 5,
                'photo_front' => UploadedFile::fake()->image('front.jpg'),
                'photo_inside' => UploadedFile::fake()->image('inside.jpg'),
                'branding_dominance' => 'Tsel', 'branding_quality' => 'Bagus',
            ])->assertSessionHasNoErrors()->assertRedirect(route('mobile.visit.menu', ['id_outlet' => $id]));
        $this->assertDatabaseHas('surya_visits', ['id_outlet' => (string) $id, 'gps_status' => 'valid']);
        $this->assertEqualsWithDelta(0.53450532, DB::table('appsdumais')->where('id_outlet', $id)->value('latitude'), 0.00000001);
    }

    public function test_photo_upload_returns_confirmation_only_after_saving_and_a_retry_does_not_duplicate_it(): void
    {
        $this->freezeTime();
        $id = 990011016;
        $this->outlet($id);
        $this->mock(OptimizedImageStorage::class, function ($mock) {
            $mock->shouldReceive('isLikelyBlack')->twice()->andReturn(false);
            $mock->shouldReceive('store')->twice()->andReturn(['path' => 'surya-visits/upload.webp']);
        });
        $this->withSession(['mobile_sf_id' => 'SF-GPS-TEST', 'mobile_sf_name' => 'GPS SALES TEST', 'idtap' => 'BENGKALIS']);
        $payload = fn () => [
            'id_outlet' => (string) $id, 'visit_type' => 'pjp', 'latitude' => 0.53450532, 'longitude' => 101.42113987,
            'photo_front' => UploadedFile::fake()->image('front.jpg'), 'photo_inside' => UploadedFile::fake()->image('inside.jpg'),
            'branding_dominance' => 'Tsel', 'branding_quality' => 'Bagus',
        ];
        $headers = ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];
        $url = route('mobile.visit.menu', ['id_outlet' => $id]);
        $response = $this->post(route('mobile.visit.store'), $payload(), $headers)
            ->assertOk()->assertJson(['redirect_url' => $url])->assertSessionHas('success');
        $this->assertStringContainsString('photos;dur=', $response->headers->get('Server-Timing'));
        $this->assertDatabaseHas('surya_visits', ['id_outlet' => (string) $id, 'gps_status' => 'valid', 'photo_inside_path' => 'surya-visits/upload.webp']);
        $this->post(route('mobile.visit.store'), $payload(), $headers)->assertOk()->assertJson(['redirect_url' => $url]);
        $this->assertSame(1, SuryaVisit::where('id_outlet', (string) $id)->count());
    }

    public function test_invalid_photo_upload_returns_validation_without_creating_a_visit(): void
    {
        $id = 990011017;
        $this->outlet($id);
        $this->mock(OptimizedImageStorage::class, function ($mock) {
            $mock->shouldReceive('isLikelyBlack')->once()->andReturn(true);
            $mock->shouldNotReceive('store');
        });
        $this->withSession(['mobile_sf_id' => 'SF-GPS-TEST', 'mobile_sf_name' => 'GPS SALES TEST', 'idtap' => 'BENGKALIS'])
            ->post(route('mobile.visit.store'), [
                'id_outlet' => (string) $id, 'visit_type' => 'pjp', 'latitude' => 0.53450532, 'longitude' => 101.42113987,
                'photo_front' => UploadedFile::fake()->image('front.jpg'), 'photo_inside' => UploadedFile::fake()->image('inside.jpg'),
                'branding_dominance' => 'Tsel', 'branding_quality' => 'Bagus',
            ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('photo_front');
        $this->assertDatabaseMissing('surya_visits', ['id_outlet' => (string) $id]);
    }

    public function test_map_boot_does_not_build_hidden_reports_or_compute_all_outlets_sales(): void
    {
        $this->admin();
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $page = $this->get('/surya?panel=outlet')->assertOk()->assertDontSee('telegram-web-app.js')
                ->assertSee('vendor/leaflet/leaflet.js')->assertSee('vendor/leaflet-markercluster/leaflet.markercluster.js')
                ->assertDontSee('https://unpkg.com/leaflet');
            $this->assertSame('outlet', $page->viewData('dashboard')['loadedPanel']);
            $this->assertArrayNotHasKey('visitDetails', $page->viewData('dashboard'));
            $this->assertFalse(collect(DB::getQueryLog())->contains(fn ($query) => preg_match('/from [`"]?(surya_visits|mobile_penjualan|keluarsf|mobile_sales_receipts)/i', $query['query'])));
            DB::flushQueryLog();
            $this->getJson('/surya/outlets')->assertOk()->assertHeader('Server-Timing');
            $this->assertFalse(collect(DB::getQueryLog())->contains(fn ($query) => preg_match('/from [`"]?(mobile_penjualan|keluarsf)/i', $query['query'])));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        $visitPage = $this->get('/surya?panel=visit-detail')->assertOk();
        $this->assertSame('visit-detail', $visitPage->viewData('dashboard')['loadedPanel']);
        $this->assertArrayHasKey('visitDetails', $visitPage->viewData('dashboard'));
    }

    public function test_coordinate_lookup_matches_the_exact_outlet_id_without_numeric_coercion(): void
    {
        $this->admin();
        $this->freezeTime();
        $id = 990011020;
        $this->outlet($id);
        $approved = $this->visit($id, ['visited_at' => now()->subHours(2), 'latitude' => 1.47, 'longitude' => 102.12]);
        $unrelated = $this->visit($id, ['visited_at' => now()->subHour(), 'latitude' => 2.1, 'longitude' => 100.8]);
        DB::table('surya_visits')->where('id', $unrelated)->update(['id_outlet' => $id.'-OTHER']);
        $rows = collect($this->getJson('/surya/outlets')->assertOk()->json())->where('id_outlet', $id)->values();
        $this->assertCount(1, $rows, 'A textual visit ID with the same numeric prefix must not duplicate or move a master outlet.');
        $this->assertSame($approved, $rows[0]['coordinate_visit_id']);
        $this->assertEqualsWithDelta(1.47, $rows[0]['latitude'], 0.00000001);
        $this->assertEqualsWithDelta(102.12, $rows[0]['longitude'], 0.00000001);
        $search = $this->getJson('/surya/search?keyword='.$id)->assertOk()->json();
        $this->assertCount(1, $search);
        $this->assertSame($approved, $search[0]['coordinate_visit_id']);
    }

    public function test_latest_history_lookup_returns_only_latest_rows_and_keeps_photo_history_separate(): void
    {
        $this->freezeTime();
        $id = 990011018;
        $other = 990011019;
        $oldPhoto = $this->visit($id, ['visited_at' => now()->subHours(3), 'branding_dominance' => 'Tsel']);
        $latestPhoto = $this->visit($id, ['visited_at' => now()->subHour(), 'branding_dominance' => 'XL-Smart']);
        $noPhoto = $this->visit($id, ['visited_at' => now(), 'photo_front_path' => null]);
        // A backdated import with a larger ID must not become the latest record.
        $this->visit($id, ['visited_at' => now()->subHours(2), 'branding_dominance' => 'Indosat-Tri']);
        $tie = $this->visit($other, ['visited_at' => now()->subMinute()]);
        $latestTie = $this->visit($other, ['visited_at' => now()->subMinute()]);
        $service = app(SuryaOutletCoordinates::class);
        $all = $service->latestVisitRecords([(string) $id, (string) $other])->get()->keyBy('id_outlet');
        $this->assertCount(2, $all);
        $this->assertSame($noPhoto, $all[$id]->id);
        $this->assertSame($latestTie, $all[$other]->id);
        $photos = $service->latestVisitRecords([(string) $id], true)->get();
        $this->assertCount(1, $photos);
        $this->assertSame($latestPhoto, $photos->first()->id);
        $this->assertSame('XL-Smart', $photos->first()->branding_dominance);
    }
}
