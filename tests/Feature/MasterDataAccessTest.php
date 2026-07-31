<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class MasterDataAccessTest extends TestCase
{
    public function test_admin_super_can_access_master_data(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (!$admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI']);

        $this->get('/denoms')->assertOk();
        $this->get('/sf')->assertOk();
    }

    public function test_admin_cluster_cannot_access_master_data(): void
    {
        $admin = User::where('username', 'admin_cluster')->first();
        if (!$admin) {
            $this->markTestSkipped('Akun admin_cluster tidak tersedia.');
        }

        $this->actingAs($admin)->withSession(['idtap' => $admin->idtap]);

        $this->get('/denoms')->assertForbidden();
        $this->get('/sf')->assertForbidden();
    }
}
