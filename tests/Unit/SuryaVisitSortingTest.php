<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SuryaVisitSortingTest extends TestCase
{
    public function test_visit_table_uses_one_sort_controller_with_complete_row_values(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/monitadumai/index.blade.php');

        $this->assertSame(1, substr_count($view, "const compareRows = (a, b) =>"));
        $this->assertStringNotContainsString('Isolated visit-table controls', $view);
        $this->assertStringContainsString('option value="actual-desc"', $view);
        $this->assertStringContainsString('data-sort="visitPercent"', $view);
        $this->assertStringContainsString('data-non-buying="{{ $row[\'non_buying\'] }}"', $view);
        $this->assertStringContainsString("header.setAttribute('aria-sort'", $view);
    }
}
