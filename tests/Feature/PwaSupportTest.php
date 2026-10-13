<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class PwaSupportTest extends TestCase
{
    private function projectPath(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);

        return $path === '' ? $root : $root.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    }

    public function test_mobile_and_inventory_manifests_have_valid_install_assets(): void
    {
        $manifests = [
            'manifest.json' => '/mobile',
            'manifest-admin.json' => '/',
        ];

        foreach ($manifests as $filename => $expectedScope) {
            $manifest = json_decode(
                file_get_contents($this->projectPath('public/'.$filename)),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $this->assertSame('standalone', $manifest['display']);
            $this->assertSame($expectedScope, $manifest['scope']);
            $this->assertFalse($manifest['prefer_related_applications']);

            $sizes = collect($manifest['icons'])->pluck('sizes');
            $this->assertTrue($sizes->contains('192x192'));
            $this->assertTrue($sizes->contains('512x512'));
            $this->assertTrue(collect($manifest['icons'])->contains('purpose', 'maskable'));

            foreach ($manifest['icons'] as $icon) {
                $iconPath = $this->projectPath('public/'.ltrim($icon['src'], '/'));
                $this->assertFileExists($iconPath);

                [$expectedWidth, $expectedHeight] = array_map('intval', explode('x', $icon['sizes']));
                [$actualWidth, $actualHeight] = getimagesize($iconPath);

                $this->assertSame($expectedWidth, $actualWidth, $icon['src']);
                $this->assertSame($expectedHeight, $actualHeight, $icon['src']);
            }
        }
    }

    public function test_login_pages_expose_installable_pwa_metadata(): void
    {
        $inventoryLogin = file_get_contents($this->projectPath('resources/views/index.blade.php'));
        $mobileLogin = file_get_contents($this->projectPath('resources/views/mobile/auth/login.blade.php'));
        $installer = file_get_contents($this->projectPath('resources/views/partials/pwa-installer.blade.php'));

        $this->assertStringContainsString('/manifest-admin.json', $inventoryLogin);
        $this->assertStringContainsString('data-pwa-install', $inventoryLogin);
        $this->assertStringContainsString('/manifest.json', $mobileLogin);
        $this->assertStringContainsString('data-pwa-install', $mobileLogin);
        $this->assertStringContainsString('/assets/pwa/surya/icon-180.png', $mobileLogin);
        $this->assertStringContainsString("navigator.serviceWorker.register('/sw.js'", $installer);
    }

    public function test_service_worker_keeps_dynamic_pages_network_first(): void
    {
        $serviceWorker = file_get_contents($this->projectPath('public/sw.js'));

        $this->assertStringContainsString("request.mode === 'navigate'", $serviceWorker);
        $this->assertStringContainsString("cache: 'no-store'", $serviceWorker);
        $this->assertStringContainsString("requestUrl.pathname.startsWith('/assets/')", $serviceWorker);
        $this->assertStringNotContainsString("requestUrl.pathname.startsWith('/mobile')", $serviceWorker);
    }

    public function test_mobile_pages_use_solid_ios_status_bar_boundaries(): void
    {
        $mobileLogin = file_get_contents($this->projectPath('resources/views/mobile/auth/login.blade.php'));
        $mobileLayout = file_get_contents($this->projectPath('resources/views/layout/mobile_layout.blade.php'));

        foreach ([$mobileLogin, $mobileLayout] as $view) {
            $this->assertStringContainsString(
                'apple-mobile-web-app-status-bar-style" content="black-translucent',
                $view
            );
            $this->assertStringContainsString('safe-area-inset-top', $view);
            $this->assertStringNotContainsString('background: #f8fafc;\n            border-bottom', $view);
        }
    }
}
