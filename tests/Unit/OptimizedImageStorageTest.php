<?php

namespace Tests\Unit;

use App\Services\OptimizedImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptimizedImageStorageTest extends TestCase
{
    public function test_it_stores_webp_display_image_and_lightweight_thumbnail(): void
    {
        Storage::fake('public');

        $result = app(OptimizedImageStorage::class)->store(
            UploadedFile::fake()->image('outlet.jpg', 2400, 1800),
            'surya-visits/front'
        );

        Storage::disk('public')->assertExists($result['path']);
        Storage::disk('public')->assertExists($result['thumbnail_path']);
        $this->assertStringEndsWith('.webp', $result['path']);
        $this->assertStringContainsString('/thumbnails/', $result['thumbnail_path']);

        $full = getimagesizefromstring(Storage::disk('public')->get($result['path']));
        $thumbnail = getimagesizefromstring(Storage::disk('public')->get($result['thumbnail_path']));

        $this->assertSame('image/webp', $full['mime']);
        $this->assertLessThanOrEqual(1600, max($full[0], $full[1]));
        $this->assertSame('image/webp', $thumbnail['mime']);
        $this->assertLessThanOrEqual(360, max($thumbnail[0], $thumbnail[1]));
        $this->assertLessThan(
            Storage::disk('public')->size($result['path']),
            Storage::disk('public')->size($result['thumbnail_path'])
        );
    }

    public function test_thumbnail_path_is_derived_without_database_columns(): void
    {
        $this->assertSame(
            'surya-visits/front/thumbnails/photo.webp',
            OptimizedImageStorage::thumbnailPathFor('surya-visits/front/photo.webp')
        );
    }
}
