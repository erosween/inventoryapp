<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NocanAdminPerformanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_page_uses_server_side_table_without_embedding_all_records(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) $this->markTestSkipped('Akun admin_super tidak tersedia.');

        $response = $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/nocanadmin')->assertOk()->assertSee('REKAPAN NOCAN MSP');

        $this->assertStringContainsString('nocanadmin\\/data', $response->getContent());
        $this->assertLessThan(200000, strlen($response->getContent()));
        $this->assertSame(1, substr_count($response->getContent(), 'id="nocanEditModal"'));
        $this->assertSame(1, substr_count($response->getContent(), 'id="nocanResetModal"'));
    }

    public function test_data_endpoint_is_paginated_server_side(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) $this->markTestSkipped('Akun admin_super tidak tersedia.');

        $response = $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->getJson('/nocanadmin/data?draw=1&start=0&length=25')->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $this->assertLessThanOrEqual(25, count($response->json('data')));
    }
}
