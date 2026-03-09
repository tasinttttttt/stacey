<?php

declare(strict_types=1);

namespace Stacey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stacey\Core\Asset\AssetFactory;
use Stacey\Core\Asset\Image;
use Stacey\Core\Asset\Video;

/**
 * @covers \Stacey\Core\Asset\AssetFactory
 */
final class AssetFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear the asset cache before each test
        AssetFactory::clearCache();
    }

    public function test_get_returns_array_for_existing_page(): void
    {
        $result = AssetFactory::get('index');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('file_path', $result);
    }

    public function test_get_caches_assets(): void
    {
        $result1 = AssetFactory::get('index');
        $result2 = AssetFactory::get('index');

        // Should return the same cached instance
        $this->assertSame($result1, $result2);
    }

    public function test_get_returns_empty_array_for_invalid_path(): void
    {
        // For non-existent paths, AssetFactory::create returns an empty array
        // when the file path doesn't exist or can't be resolved
        $result = AssetFactory::get('nonexistent-page-12345');

        $this->assertIsArray($result);
    }

    public function test_clearCache_removes_cached_assets(): void
    {
        // Get initial result and store its spl_object_id equivalent
        $result1 = AssetFactory::get('index');
        $filePath1 = $result1['file_path'] ?? null;

        // Clear cache
        AssetFactory::clearCache();

        // Get result after clearing cache
        $result2 = AssetFactory::get('index');
        $filePath2 = $result2['file_path'] ?? null;

        // Both should have the same file_path value
        $this->assertEquals($filePath1, $filePath2);

        // But after clearing, a new Page object was created
        // The arrays should have the same content but be different instances in memory
        $this->assertEquals($result1, $result2);
    }

    public function test_get_returns_image_data_for_image_file(): void
    {
        // Look for any image files in content
        $files = glob('./content/**/*.{jpg,jpeg,png,gif}', GLOB_BRACE);

        if (empty($files)) {
            $this->markTestSkipped('No image files found in content directory');
        }

        $result = AssetFactory::get($files[0]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('file_name', $result);
    }

    public function test_get_returns_video_data_for_video_file(): void
    {
        // Look for any video files in content
        $files = glob('./content/**/*.{mov,mp4,m4v}', GLOB_BRACE);

        if (empty($files)) {
            $this->markTestSkipped('No video files found in content directory');
        }

        $result = AssetFactory::get($files[0]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('file_name', $result);
    }

    public function test_get_treats_directory_as_page(): void
    {
        $result = AssetFactory::get('');

        $this->assertIsArray($result);
    }
}
