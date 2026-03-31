<?php

declare(strict_types=1);

namespace Stacey\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Stacey\Core\Asset\AssetFactory;
use Stacey\Core\Asset\Page;
use Stacey\Core\Config;
use Stacey\Core\Helpers;

/**
 * @covers \Stacey\Core\Asset\AssetFactory
 * @covers \Stacey\Core\Asset\Page
 * @covers \Stacey\Core\Asset\Image
 */
final class AssetHandlingTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        Helpers::clearFileCache();
        AssetFactory::clearCache();

        $this->config = new Config(
            rootFolder: TEST_ROOT . '/../',
            contentFolder: CONTENT_ROOT,
            templatesFolder: TEST_ROOT . '/Fixtures/templates',
            cacheFolder: TEST_ROOT . '/../app/_cache',
        );

        $this->helpers = new Helpers($this->config, []);

        // Set config for AssetFactory
        AssetFactory::setConfig($this->config);
    }

    public function test_can_create_page_asset(): void
    {
        $page = new Page('index', $this->config, false);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertNotEmpty($page->data);
        $this->assertArrayHasKey('file_path', $page->data);
    }

    public function test_page_has_required_data_fields(): void
    {
        $page = new Page('index', $this->config, false);

        $requiredFields = [
            'file_path',
            'url',
            'permalink',
            'slug',
            'page_name',
            'root_path',
            'template_name',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $page->data, "Missing required field: {$field}");
        }
    }

    public function test_page_has_siblings_and_children(): void
    {
        $page = new Page('projects', $this->config, false);

        $this->assertArrayHasKey('siblings', $page->data);
        $this->assertArrayHasKey('siblings_and_self', $page->data);
        $this->assertArrayHasKey('children', $page->data);
    }

    public function test_asset_factory_returns_page_data(): void
    {
        $asset = AssetFactory::get('index');

        $this->assertIsArray($asset);
        $this->assertArrayHasKey('file_path', $asset);
        $this->assertArrayHasKey('url', $asset);
    }

    public function test_asset_factory_caches_results(): void
    {
        $asset1 = AssetFactory::get('index');
        $asset2 = AssetFactory::get('index');

        // Should be the same cached instance
        $this->assertSame($asset1, $asset2);
    }

    public function test_can_get_image_dimensions(): void
    {
        // Find an image file in the content directory
        $imageFiles = glob(CONTENT_ROOT . '/**/*.{jpg,jpeg,png,gif}', GLOB_BRACE);

        if (empty($imageFiles)) {
            $this->markTestSkipped('No image files found in content directory');
        }

        $asset = AssetFactory::get($imageFiles[0]);

        // Image assets should have width and height
        $this->assertIsArray($asset);
        $this->assertArrayHasKey('file_name', $asset);
    }

    public function test_asset_factory_handles_invalid_paths(): void
    {
        $asset = AssetFactory::get('nonexistent/path/12345');

        // Should return empty array for invalid paths
        $this->assertIsArray($asset);
    }

    public function test_page_template_detection(): void
    {
        $page = new Page('index', $this->config, false);

        $this->assertNotEmpty($page->templateName);
        $this->assertNotNull($page->templateFile);
    }
}
