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
        foreach (['cluster_dumai', 'cluster_rohil'] as $username) {
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
}
