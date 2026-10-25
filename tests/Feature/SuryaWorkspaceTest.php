<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SuryaWorkspaceTest extends TestCase
{
    use DatabaseTransactions;

    public static function pages(): array
    {
        return [
            'map' => ['/surya?panel=outlet', 'Peta Outlet'],
            'visits' => ['/surya?panel=visit-detail', 'Detail Visit'],
            'outlets' => ['/surya/detail-outlet', 'Detail Outlet'],
            'non-digipos' => ['/surya/detail-outlet?tab=non-digipos', 'Detail Outlet'],
            'warehouse' => ['/surya/stock-gudang', 'Stock Gudang'],
            'pv-stock' => ['/surya/stock-on-hand?family=PV', 'Stock on Hand'],
            'sa-stock' => ['/surya/stock-on-hand?family=SA', 'Stock on Hand'],
            'targets' => ['/surya/input-target', 'Input Target'],
            'uploads' => ['/surya/transaction-upload', 'Upload Transaksi'],
            'approval' => ['/surya/visits', 'Approval Visit'],
            'consignment' => ['/surya/field-reports', 'Laporan Lapangan'],
            'survey' => ['/surya/field-reports?type=survey', 'Laporan Lapangan'],
            'network' => ['/surya/field-reports?type=network', 'Laporan Lapangan'],
        ];
    }

    #[DataProvider('pages')]
    public function test_pages_render_one_shared_navigation_and_page_title(string $url, string $title): void
    {
        $admin = User::where('username', 'admin_super')->firstOrFail();
        $response = $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])->get($url)->assertOk();
        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        $xpath = new DOMXPath($document);

        $this->assertSame(1, $xpath->query('//*[@data-surya-workspace]')->length);
        $this->assertSame(1, $xpath->query('//*[@aria-label="Header SURYA"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="surya-sidebar"]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-surya-sidebar-toggle]')->length);
        $this->assertSame(1, $xpath->query('//header//following-sibling::main')->length);
        $this->assertSame(1, $xpath->query('//h1[normalize-space()="'.$title.'"]')->length);
        $this->assertSame(7, $xpath->query('//*[@class="sidebar-nav"]/a')->length);
        $this->assertSame(1, $xpath->query('//a[contains(concat(" ", @class, " "), " sidebar-link ") and contains(concat(" ", @class, " "), " active ")]')->length);
        $response->assertDontSee('Data terhubung')->assertDontSee('INVENTORY MSP');
    }

    public function test_target_editor_loads_the_correct_sf_target_and_saves_by_id(): void
    {
        $admin = User::where('username', 'admin_super')->firstOrFail();
        DB::table('idsf')->insert(['idsf' => 'SF-AUDIT-TARGET', 'namasf' => 'AUDIT TARGET SALES', 'idtap' => 'DUMAI']);
        $id = DB::table('surya_sf_targets')->insertGetId([
            'idsf' => 'SF-AUDIT-TARGET', 'product_family' => 'PV', 'validity' => '1 HARI',
            'target_qty' => 37, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherId = DB::table('surya_sf_targets')->insertGetId([
            'idsf' => 'SF-AUDIT-TARGET', 'product_family' => 'SA', 'validity' => '3GB',
            'target_qty' => 91, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])->get('/surya/input-target')
            ->assertOk()->assertSee('AUDIT TARGET SALES')->assertSee('value="37"', false)->assertSee('value="91"', false);
        $this->patch('/surya/input-target/'.$id, ['target_qty' => 56])->assertRedirect();
        $this->assertDatabaseHas('surya_sf_targets', ['id' => $id, 'target_qty' => 56]);
        $this->assertDatabaseHas('surya_sf_targets', ['id' => $otherId, 'target_qty' => 91]);
    }

    public function test_cluster_role_keeps_shared_navigation_without_admin_tools(): void
    {
        $user = User::where('username', 'cluster_dumai')->firstOrFail();
        $this->actingAs($user)->withSession(['idtap' => 'CLUSTER_DUMAI'])->get('/surya/stock-gudang')
            ->assertOk()->assertSee('Header SURYA')->assertDontSee('INPUT TARGET')->assertDontSee('UPLOAD TRANSAKSI');
        $this->get('/surya/input-target')->assertForbidden();
        $this->get('/surya/transaction-upload')->assertForbidden();
    }
}
