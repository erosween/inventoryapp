<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuryaClusterAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cluster_users_are_redirected_to_surya_after_login(): void
    {
        foreach (['cluster_dumai', 'cluster_rohil', 'gm_cluster', 'branch_dumai'] as $username) {
            $user = User::where('username', $username)->first();
            if (! $user) {
                $this->markTestSkipped("Fixture {$username} belum tersedia.");
            }

            $password = "test-{$username}-password";
            DB::table('login')->where('id', $user->id)->update([
                'password' => Hash::make($password),
            ]);

            $this->post(route('login.post'), compact('username', 'password'))
                ->assertRedirect(route('surya.dashboard'));

            $this->get(route('logout'));
        }
    }

    public function test_cluster_users_only_receive_outlets_from_their_assigned_taps(): void
    {
        $dumaiUser = User::where('username', 'cluster_dumai')->first();
        $rohilUser = User::where('username', 'cluster_rohil')->first();
        if (! $dumaiUser || ! $rohilUser) {
            $this->markTestSkipped('Fixture user cluster belum tersedia.');
        }

        DB::table('appsdumais')->insert([
            [
                'id_outlet' => 990000101,
                'nama_outlet' => 'Scope Cluster Test Dumai',
                'tap' => 'DUMAI',
                'sf' => 'UNMAPPING',
                'pjp' => 'UNMAPPING',
            ],
            [
                'id_outlet' => 990000102,
                'nama_outlet' => 'Scope Cluster Test Rohil',
                'tap' => 'BAGAN BATU',
                'sf' => 'UNMAPPING',
                'pjp' => 'UNMAPPING',
            ],
        ]);

        $this->actingAs($dumaiUser)
            ->withSession(['idtap' => 'CLUSTER_DUMAI'])
            ->get('/surya/suggest?keyword=Scope%20Cluster%20Test')
            ->assertOk()
            ->assertJsonFragment(['id_outlet' => 990000101])
            ->assertJsonMissing(['id_outlet' => 990000102]);

        $this->actingAs($rohilUser)
            ->withSession(['idtap' => 'CLUSTER_ROHIL'])
            ->get('/surya/suggest?keyword=Scope%20Cluster%20Test')
            ->assertOk()
            ->assertJsonFragment(['id_outlet' => 990000102])
            ->assertJsonMissing(['id_outlet' => 990000101]);
    }

    public function test_gm_cluster_can_receive_outlets_from_every_tap(): void
    {
        $gm = User::where('username', 'gm_cluster')->first();
        if (! $gm) {
            $this->markTestSkipped('Fixture user gm_cluster belum tersedia.');
        }

        DB::table('appsdumais')->insert([
            [
                'id_outlet' => 990000103,
                'nama_outlet' => 'Scope GM Test Dumai',
                'tap' => 'DUMAI',
                'sf' => 'UNMAPPING',
                'pjp' => 'UNMAPPING',
            ],
            [
                'id_outlet' => 990000104,
                'nama_outlet' => 'Scope GM Test Rohil',
                'tap' => 'UJUNG TANJUNG',
                'sf' => 'UNMAPPING',
                'pjp' => 'UNMAPPING',
            ],
        ]);

        $this->actingAs($gm)
            ->withSession(['idtap' => 'ALL TAP'])
            ->get('/surya/suggest?keyword=Scope%20GM%20Test')
            ->assertOk()
            ->assertJsonFragment(['id_outlet' => 990000103])
            ->assertJsonFragment(['id_outlet' => 990000104]);

        $this->actingAs($gm)
            ->view('surya.partials.sidebar', ['activePage' => 'dashboard'])
            ->assertSee(route('sisastock.index'), false)
            ->assertDontSee('LIST OUTLET')
            ->assertDontSee('UPLOAD TRANSAKSI');
    }

    public function test_branch_dumai_uses_all_tap_surya_scope_with_restricted_sidebar(): void
    {
        $branch = User::where('username', 'branch_dumai')->first();
        if (! $branch) {
            $this->markTestSkipped('Fixture user branch_dumai belum tersedia.');
        }

        DB::table('appsdumais')->insert([
            [
                'id_outlet' => 990000105,
                'nama_outlet' => 'Scope Branch Test Dumai',
                'tap' => 'DUMAI',
                'sf' => 'UNMAPPING',
                'pjp' => 'UNMAPPING',
            ],
            [
                'id_outlet' => 990000106,
                'nama_outlet' => 'Scope Branch Test Rohil',
                'tap' => 'UJUNG TANJUNG',
                'sf' => 'UNMAPPING',
                'pjp' => 'UNMAPPING',
            ],
        ]);

        $this->actingAs($branch)
            ->withSession(['idtap' => 'ALL TAP'])
            ->get('/surya/suggest?keyword=Scope%20Branch%20Test')
            ->assertOk()
            ->assertJsonFragment(['id_outlet' => 990000105])
            ->assertJsonFragment(['id_outlet' => 990000106]);

        $this->actingAs($branch)
            ->view('surya.partials.sidebar', ['activePage' => 'dashboard'])
            ->assertSee(route('sisastock.index'), false)
            ->assertDontSee('LIST OUTLET')
            ->assertDontSee('UPLOAD TRANSAKSI');

        $this->actingAs($branch)->get(route('surya.outlet-list'))->assertForbidden();
        $this->actingAs($branch)->get(route('surya.transaction-upload'))->assertForbidden();
    }

    public function test_authenticated_gm_cluster_is_redirected_from_login_page_to_surya(): void
    {
        $gm = User::where('username', 'gm_cluster')->first();
        if (! $gm) {
            $this->markTestSkipped('Fixture user gm_cluster belum tersedia.');
        }

        $this->actingAs($gm)
            ->withSession(['idtap' => 'ALL TAP'])
            ->get('/')
            ->assertRedirect(route('surya.dashboard'));
    }

    public function test_gm_cluster_home_fallback_uses_all_tap_inventory(): void
    {
        $gm = User::where('username', 'gm_cluster')->first();
        if (! $gm) {
            $this->markTestSkipped('Fixture user gm_cluster belum tersedia.');
        }

        $expectedSealed = (int) DB::table('stockawaltap')
            ->whereIn('iddenom', ['SEGEL', 'V16', 'V33'])
            ->sum('stock')
            + (int) DB::table('stockawalsf as sf')
                ->join('idsf', 'sf.idsf', '=', 'idsf.idsf')
                ->whereIn('sf.iddenom', ['SEGEL', 'V16', 'V33'])
                ->sum('sf.stock');

        $response = $this->actingAs($gm)
            ->withSession(['idtap' => 'ALL TAP'])
            ->get('/home')
            ->assertOk();

        $this->assertSame($expectedSealed, (int) $response->viewData('stokSegel'));
        $response->assertSee('DASHBOARD')
            ->assertSee('STOCK GUDANG')
            ->assertSee('SURYA')
            ->assertSee('Dumai Bengkalis')
            ->assertSee('Rokan Hilir')
            ->assertSee('GRAND TOTAL MOM')
            ->assertDontSee('KOTAK MASUK')
            ->assertDontSee('STOCK MOVEMENT')
            ->assertDontSee('INPUT STOK')
            ->assertDontSee('SALES FORCE');
    }
}
