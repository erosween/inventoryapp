<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeMomDateFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_month_end_filter_uses_last_available_day_of_previous_month(): void
    {
        $response = $this->getDashboard('2024-07-31');

        $this->assertSame('2024-07-31', $response->viewData('momSelectedDate')->toDateString());
        $this->assertSame('2024-06-30', $response->viewData('momEndPrevMonthPartial')->toDateString());
        $response->assertSee('MTD (31 JUL)');
        $response->assertSee('M-1 (30 JUN)');
    }

    public function test_regular_day_filter_compares_the_same_day_in_previous_month(): void
    {
        $response = $this->getDashboard('2024-07-28');

        $this->assertSame('2024-06-28', $response->viewData('momEndPrevMonthPartial')->toDateString());
        $response->assertSee('MTD (28 JUL)');
        $response->assertSee('M-1 (28 JUN)');

        $row = $response->viewData('momTap')->first();
        if (! $row) {
            return;
        }

        $currentTotal = DB::table('keluarsf')
            ->where('idtap', $row->idtap)
            ->whereBetween('tgl', [Carbon::parse('2024-07-01'), Carbon::parse('2024-07-28')->endOfDay()])
            ->sum('qty');
        $previousTotal = DB::table('keluarsf')
            ->where('idtap', $row->idtap)
            ->whereBetween('tgl', [Carbon::parse('2024-06-01'), Carbon::parse('2024-06-28')->endOfDay()])
            ->sum('qty');

        $this->assertSame((int) $currentTotal, (int) $row->curr_qty);
        $this->assertSame((int) $previousTotal, (int) $row->prev_partial_qty);
    }

    public function test_month_end_filter_uses_full_previous_month_even_when_it_is_longer(): void
    {
        $response = $this->getDashboard('2024-06-30');

        $this->assertSame('2024-05-31', $response->viewData('momEndPrevMonthPartial')->toDateString());
        $response->assertSee('MTD (30 JUN)');
        $response->assertSee('M-1 (31 MEI)');
        $response->assertSee('action="http://localhost/home#mom-cluster-card"', false);
        $response->assertSee('loadMomDateWithoutRefresh');
        $response->assertSee('expandedTargets');
    }

    public function test_monthly_sales_can_switch_between_table_and_chart(): void
    {
        $response = $this->getDashboard('2024-07-31');

        $response->assertSee('id="monthly-sales-table-btn"', false);
        $response->assertSee('id="monthly-sales-chart-btn"', false);
        $response->assertSee('id="monthly-sales-chart"', false);
        $response->assertSee('const monthlySalesMatrix =', false);
        $response->assertSee("setMonthlySalesView('chart')", false);
    }

    public function test_monthly_sales_year_is_independent_from_global_dashboard_year(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/home?year=2024&sales_year=2023&mom_date=2024-07-31')
            ->assertOk();

        $this->assertSame('2024', (string) $response->viewData('selectedYear'));
        $this->assertSame(2023, $response->viewData('salesYear'));
        $response->assertSee('Penjualan Bulanan (Tahun 2023)');
        $response->assertSee('name="sales_year" value="2023"', false);
        $response->assertSee('name="year" value="2024"', false);
    }

    public function test_monthly_sales_chart_view_is_preserved_when_year_changes(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/home?sales_year=2025&sales_view=chart')
            ->assertOk();

        $this->assertSame('chart', $response->viewData('salesView'));
        $response->assertSee('name="sales_view" id="monthly-sales-view-input" value="chart"', false);
        $response->assertSee('id="monthly-sales-chart-wrap" class="" aria-hidden="false"', false);
        $response->assertSee('setMonthlySalesView(getMonthlySalesPayload().view)', false);
        $response->assertSee("'#4E79A7', '#F28E2B', '#E15759', '#76B7B2', '#59A14F', '#EDC948', '#B07AA1', '#FF5A8A'", false);
    }

    public function test_annual_sales_uses_labeled_bar_chart_without_grand_total_line(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/home?sales_year=2025&sales_period=annual&sales_view=chart')
            ->assertOk();

        $this->assertSame('annual', $response->viewData('salesPeriod'));
        $this->assertSame('chart', $response->viewData('salesView'));
        $this->assertIsArray($response->viewData('annualSales'));
        $response->assertSee('Penjualan Tahunan (2024–'.date('Y').')');
        foreach ($response->viewData('annualSales') as $yearValues) {
            $this->assertArrayNotHasKey(2023, $yearValues);
        }
        $response->assertSee("type: 'bar'", false);
        $response->assertDontSee("label: 'GRAND TOTAL'", false);
        $response->assertSee('compactSalesQty', false);
        $response->assertSee('annualBarValuePlugin', false);
        $response->assertSee('.filter(year => year >= 2024', false);
        $response->assertSee('renderActiveSalesChartToCanvas', false);
        $response->assertSee('renderCurrentSalesCardToCanvas', false);
        $response->assertSee('id="monthly-sales-year-wrap" class="d-none"', false);
        $response->assertSee('id="sales-card-reload-btn"', false);
        $response->assertSee('class="fas fa-sync-alt"', false);
        $response->assertSee('reloadMonthlySalesCard', false);
        $response->assertSee("currentCard.replaceWith(nextCard)", false);
        $response->assertSee("scrollIntoView({ block: 'start' })", false);
    }

    public function test_annual_sales_has_table_view_with_year_and_grand_totals(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/home?sales_period=annual&sales_view=table&sales_annual_cutoff=7')
            ->assertOk();

        $this->assertSame('annual', $response->viewData('salesPeriod'));
        $this->assertSame('table', $response->viewData('salesView'));
        $response->assertSee('NAMA TAP');
        $response->assertSee('% YTD '.((int) date('Y') - 1));
        $response->assertSee('% YTD '.date('Y'));
        $response->assertSee('YoY '.date('Y'));
        $response->assertSee('MoM '.date('Y'));
        $response->assertSee('Dumai Bengkalis');
        $response->assertSee('Rokan Hilir');
        $response->assertSee('GRAND TOTAL');
        $response->assertSee((string) date('Y'));
        $response->assertSee("currentUrl.searchParams.set('sales_view'", false);
        $response->assertSee("setMonthlySalesView('table')", false);
        $response->assertSee('renderAnnualSalesTableToCanvas', false);
        $response->assertSee('getCurrentSalesView()', false);
        $response->assertSee('`penjualan-${payload.period}-${activeView}`', false);

        $annualSales = $response->viewData('annualSales');
        $currentTotal = array_sum(array_map(fn ($years) => (int) ($years[(int) date('Y')] ?? 0), $annualSales));
        $previousTotal = array_sum(array_map(fn ($years) => (int) ($years[(int) date('Y') - 1] ?? 0), $annualSales));
        if ($previousTotal > 0) {
            $expectedYtd = (($currentTotal - $previousTotal) / $previousTotal) * 100;
            $response->assertSee(number_format(abs($expectedYtd), 1).'%');
        }
        $twoYearsAgoTotal = array_sum(array_map(fn ($years) => (int) ($years[(int) date('Y') - 2] ?? 0), $annualSales));
        if ($twoYearsAgoTotal > 0) {
            $expectedPreviousYtd = (($previousTotal - $twoYearsAgoTotal) / $twoYearsAgoTotal) * 100;
            $response->assertSee(number_format(abs($expectedPreviousYtd), 1).'%');
        }

        $comparisonYear = (int) date('Y');
        $currentMonthTotal = DB::table('keluarsf')->whereYear('tgl', $comparisonYear)->whereMonth('tgl', 7)->sum('qty');
        $previousYearMonthTotal = DB::table('keluarsf')->whereYear('tgl', $comparisonYear - 1)->whereMonth('tgl', 7)->sum('qty');
        $previousMonthTotal = DB::table('keluarsf')->whereYear('tgl', $comparisonYear)->whereMonth('tgl', 6)->sum('qty');
        if ($previousYearMonthTotal > 0) {
            $expectedYoy = (($currentMonthTotal - $previousYearMonthTotal) / $previousYearMonthTotal) * 100;
            $response->assertSee(number_format(abs($expectedYoy), 1).'%');
        }
        if ($previousMonthTotal > 0) {
            $expectedMom = (($currentMonthTotal - $previousMonthTotal) / $previousMonthTotal) * 100;
            $response->assertSee(number_format(abs($expectedMom), 1).'%');
        }
    }

    public function test_annual_sales_month_cutoff_is_applied_equally_to_each_year(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/home?sales_period=annual&sales_annual_cutoff=7')
            ->assertOk();

        $this->assertSame('7', (string) $response->viewData('annualCutoff'));
        $response->assertSee('Perbandingan total penjualan setiap TAP per tahun · Jul');
        $response->assertDontSee('s.d. Jul');
        $response->assertSee('id="annual-sales-cutoff-wrap" class=""', false);
        $response->assertSee('id="annual-sales-cutoff"', false);

        $annualSales = $response->viewData('annualSales');
        $tap = array_key_first($annualSales);
        if (! $tap) {
            return;
        }

        foreach (range(2024, (int) date('Y')) as $year) {
            $expected = DB::table('keluarsf')
                ->where('idtap', $tap)
                ->whereYear('tgl', $year)
                ->whereMonth('tgl', '<=', 7)
                ->sum('qty');

            $this->assertSame((int) $expected, (int) ($annualSales[$tap][$year] ?? 0));
        }
    }

    public function test_annual_full_uses_last_completed_month_for_current_ytd_yoy_and_mom(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/home?sales_period=annual&sales_view=table&sales_annual_cutoff=full')
            ->assertOk();

        $comparisonMonth = $response->viewData('annualComparisonMonth');
        $this->assertGreaterThanOrEqual(1, $comparisonMonth);
        $this->assertLessThanOrEqual(12, $comparisonMonth);
        $this->assertNotEmpty($response->viewData('annualPeriodComparisons'));

        $currentYear = (int) date('Y');
        $annualSales = $response->viewData('annualSales');
        $currentYtdSales = $response->viewData('annualCurrentYtdSales');
        $tap = array_key_first($annualSales);
        if (! $tap) {
            return;
        }

        $expectedFullPreviousYear = DB::table('keluarsf')
            ->where('idtap', $tap)
            ->whereYear('tgl', $currentYear - 1)
            ->sum('qty');
        $this->assertSame((int) $expectedFullPreviousYear, (int) ($annualSales[$tap][$currentYear - 1] ?? 0));

        foreach ([$currentYear - 1, $currentYear] as $year) {
            $expectedYtd = DB::table('keluarsf')
                ->where('idtap', $tap)
                ->whereYear('tgl', $year)
                ->whereMonth('tgl', '<=', $comparisonMonth)
                ->sum('qty');
            $this->assertSame((int) $expectedYtd, (int) ($currentYtdSales[$tap][$year] ?? 0));
        }
    }

    private function getDashboard(string $momDate)
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        return $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/home?mom_date='.$momDate)
            ->assertOk();
    }
}
