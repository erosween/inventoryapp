<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SuryaSidebarStateTest extends TestCase
{
    public function test_collapsed_sidebar_is_applied_before_paint_without_hover_reveal(): void
    {
        $root = dirname(__DIR__, 2);
        $boot = file_get_contents($root.'/resources/views/surya/partials/sidebar-boot.blade.php');
        $css = file_get_contents($root.'/public/css/surya-sidebar.css');

        $this->assertStringContainsString("localStorage.getItem('surya-sidebar-collapsed')", $boot);
        $this->assertStringContainsString("root.classList.add('surya-sidebar-precollapsed')", $boot);
        $this->assertStringContainsString("root.classList.add('surya-sidebar-initializing')", $boot);
        $this->assertStringContainsString("root.classList.remove('surya-sidebar-initializing')", $boot);
        $this->assertStringContainsString('html.surya-sidebar-precollapsed .enterprise-sidebar{transform:translateX(-100%)}', $css);
        $this->assertStringContainsString('html.surya-sidebar-precollapsed .app-main,html.surya-sidebar-precollapsed .main{width:100%;margin-left:0}', $css);
        $this->assertStringContainsString('html.surya-sidebar-initializing .enterprise-sidebar', $css);
        $this->assertStringNotContainsString('sidebar-collapsed .enterprise-sidebar:hover', $css);
    }
}
