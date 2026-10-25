<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SuryaOutletScopeTest extends TestCase
{
    use DatabaseTransactions;

    private const TAPS = ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING', 'BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'];

    public static function users(): array
    {
        return [
            'asmen-dumai' => ['asmen_dumai', ['DUMAI']],
            'asmen-duri' => ['asmen_duri', ['DURI']],
            'asmen-bengkalis' => ['asmen_bengkalis', ['BENGKALIS']],
            'asmen-rupat' => ['asmen_rupat', ['RUPAT']],
            'asmen-sei-pakning' => ['asmen_sei_pakning', ['SEI PAKNING']],
            'asmen-bagan-batu' => ['asmen_bagan_batu', ['BAGAN BATU']],
            'asmen-bagan-siapi-api' => ['asmen_bagan_siapi_api', ['BAGAN SIAPI-API']],
            'asmen-ujung-tanjung' => ['asmen_ujung_tanjung', ['UJUNG TANJUNG']],
            'cluster-dumai' => ['cluster_dumai', array_slice(self::TAPS, 0, 5)],
            'cluster-rohil' => ['cluster_rohil', array_slice(self::TAPS, 5)],
            'admin-super' => ['admin_super', self::TAPS],
        ];
    }

    #[DataProvider('users')]
    public function test_outlet_pages_filters_data_and_export_keep_the_users_tap_scope(string $username, array $allowedTaps): void
    {
        $this->freezeTime();
        $user = User::where('username', $username)->firstOrFail();
        foreach (self::TAPS as $index => $tap) {
            DB::table('appsdumais')->insert([
                'id_outlet' => 990008001 + $index,
                'nama_outlet' => 'Outlet Scope Regression '.$tap,
                'tap' => $tap, 'sf' => 'Scope Sales', 'pjp' => 'Senin',
            ]);
        }
        $this->actingAs($user)->withSession(['idtap' => $user->idtap]);

        foreach (['digipos', 'non-digipos'] as $tab) {
            $response = $this->get(route('surya.outlet-detail', ['tab' => $tab, 'q' => 'Outlet Scope Regression']))->assertOk();
            $this->assertEqualsCanonicalizing($allowedTaps, $response->viewData('taps')->all());
            $response->assertViewHas('canEditOutlet', $username === 'admin_super');
            if ($tab === 'digipos') {
                $this->assertEqualsCanonicalizing($allowedTaps, $response->viewData('outlets')->getCollection()->pluck('tap')->all());
            }
        }

        $data = $this->getJson(route('surya.outlet-detail.data', ['search' => ['value' => 'Outlet Scope Regression']]))
            ->assertOk()->assertJsonPath('recordsFiltered', count($allowedTaps));
        $this->assertEqualsCanonicalizing($allowedTaps, array_column($data->json('data'), 'tap'));

        $forbiddenTap = collect(self::TAPS)->diff($allowedTaps)->first();
        if ($forbiddenTap !== null) {
            $this->get(route('surya.outlet-detail', ['tap' => $forbiddenTap]))->assertOk()
                ->assertViewHas('outlets', fn ($rows) => $rows->total() === 0);
        }

        Excel::fake();
        $this->get(route('surya.outlet-detail.export', ['q' => 'Outlet Scope Regression']))->assertOk();
        Excel::assertDownloaded('surya-detail-outlet-'.now()->format('Y-m-d-His').'.xlsx', function ($export) use ($allowedTaps) {
            $this->assertEqualsCanonicalizing($allowedTaps, $export->sheets()[0]->collection()->pluck('tap')->all());

            return true;
        });
    }
}
