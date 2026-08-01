<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TapTransferCancellationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sender_can_cancel_pending_transfer_without_changing_stock(): void
    {
        $admin = User::where('username', 'admin_super')->firstOrFail();
        $sender = DB::table('kodetap')->value('idtap');
        $receiver = DB::table('kodetap')->where('idtap', '!=', $sender)->value('idtap');
        $denom = DB::table('denom')->value('iddenom');
        $stockBefore = DB::table('stockawaltap')->where('idtap', $sender)->where('iddenom', $denom)->value('stock');

        $id = DB::table('keluar')->insertGetId([
            'tgl' => now()->toDateString(),
            'iddenom' => $denom,
            'pengirim' => $sender,
            'penerima' => $receiver,
            'idtap' => $sender,
            'qty' => 1,
            'sn' => 'TEST-CANCEL',
            'status' => 1,
        ], 'idkeluar');

        $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->post(route('keluar.cancel', $id))
            ->assertRedirect(route('keluar.index'));

        $this->assertFalse(DB::table('keluar')->where('idkeluar', $id)->exists());
        $this->assertSame($stockBefore, DB::table('stockawaltap')->where('idtap', $sender)->where('iddenom', $denom)->value('stock'));
    }

    public function test_approved_transfer_cannot_be_cancelled(): void
    {
        $admin = User::where('username', 'admin_super')->firstOrFail();
        $existing = DB::table('keluar')->first();
        if (! $existing) {
            $this->markTestSkipped('Tidak ada contoh transfer.');
        }

        $id = DB::table('keluar')->insertGetId(array_merge((array) $existing, [
            'idkeluar' => null,
            'status' => 0,
        ]), 'idkeluar');

        $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->post(route('keluar.cancel', $id))
            ->assertStatus(422);

        $this->assertTrue(DB::table('keluar')->where('idkeluar', $id)->exists());
    }

    public function test_outgoing_transfer_page_uses_sweetalert_for_cancel_confirmation(): void
    {
        $admin = User::where('username', 'admin_super')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('keluar.index'))
            ->assertOk()
            ->assertSee("Swal.fire({", false)
            ->assertSee("title: 'Batalkan pengiriman?'", false)
            ->assertSee("confirmButtonText: '<i class=", false)
            ->assertDontSee("window.confirm", false);
    }
}
