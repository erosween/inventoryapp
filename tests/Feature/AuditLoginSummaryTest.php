<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLoginSummaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_audit_page_summarizes_current_month_logins_per_user(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum tersedia.');
        }

        $testUser = User::where('username', '!=', 'admin_super')->firstOrFail();
        $existingLoginCount = DB::table('logs')
            ->where('username', $testUser->username)
            ->where('action', 'LOGIN')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();
        DB::table('logs')->insert([
            [
                'username' => $testUser->username,
                'action' => 'LOGIN',
                'module' => 'Authentication',
                'record_id' => $testUser->id,
                'created_at' => Carbon::now()->startOfMonth()->addDay(),
                'updated_at' => Carbon::now()->startOfMonth()->addDay(),
            ],
            [
                'username' => $testUser->username,
                'action' => 'LOGIN',
                'module' => 'Authentication',
                'record_id' => $testUser->id,
                'created_at' => Carbon::now()->startOfMonth()->addDays(2),
                'updated_at' => Carbon::now()->startOfMonth()->addDays(2),
            ],
            [
                'username' => $testUser->username,
                'action' => 'LOGIN',
                'module' => 'Authentication',
                'record_id' => $testUser->id,
                'created_at' => Carbon::now()->subMonth()->startOfMonth(),
                'updated_at' => Carbon::now()->subMonth()->startOfMonth(),
            ],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/audit')
            ->assertOk();

        $summary = $response->viewData('loginSummary')->firstWhere('username', $testUser->username);
        $this->assertNotNull($summary);
        $this->assertSame($existingLoginCount + 2, (int) $summary->login_count);
        $response->assertSee('Summary Login per User');
        $response->assertSee($testUser->username);
        $response->assertSee(number_format($existingLoginCount + 2).'x');
        $response->assertSee(Carbon::now()->translatedFormat('F Y'));
        $response->assertSee('id="login-summary-search"', false);
        $response->assertSee("document.querySelectorAll('.login-summary-row')", false);
        $response->assertSee('data-search=', false);
        $response->assertSee("classList.toggle('is-filtering'", false);
        $response->assertSee('id="login-summary-result"', false);
    }
}
