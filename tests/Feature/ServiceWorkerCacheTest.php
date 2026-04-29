<?php

namespace Tests\Feature;

use Tests\TestCase;

class ServiceWorkerCacheTest extends TestCase
{
    public function test_service_worker_keeps_mobile_pages_network_fresh(): void
    {
        $serviceWorker = file_get_contents(public_path('sw.js'));

        $this->assertStringNotContainsString("  '/mobile',", $serviceWorker);
        $this->assertStringNotContainsString('  "/mobile",', $serviceWorker);
        $this->assertStringContainsString("requestUrl.pathname.startsWith('/mobile')", $serviceWorker);
        $this->assertStringContainsString("event.request.mode === 'navigate'", $serviceWorker);
        $this->assertStringContainsString('no-store', file_get_contents(app_path('Http/Middleware/MobileSFAccess.php')));
    }
}
