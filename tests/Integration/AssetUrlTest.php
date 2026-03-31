<?php

declare(strict_types=1);

namespace Stacey\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Stacey\Core\Asset\Image;
use Stacey\Core\Config;
use Stacey\Core\Helpers;

/**
 * @covers \Stacey\Core\Asset\Asset
 * @covers \Stacey\Core\Asset\Image
 */
final class AssetUrlTest extends TestCase
{
    private Config $config;
    private Helpers $helpers;

    protected function setUp(): void
    {
        $this->config = new Config(
            rootFolder: TEST_ROOT . '/../',
            contentFolder: CONTENT_ROOT,
            templatesFolder: TEST_ROOT . '/Fixtures/templates',
            cacheFolder: TEST_ROOT . '/../app/_cache',
        );

        $this->helpers = new Helpers($this->config, []);
    }

    public function test_asset_url_includes_content_folder(): void
    {
        // Create a test image path that includes content folder
        $imagePath = $this->config->contentFolder . '/projects/01.project-1/thumb.jpg';

        // Create the image asset
        $image = new Image($imagePath, $this->helpers);
        $data = $image->getData();

        // URL should include /content/ folder
        $this->assertStringContainsString('/content/', $data['url'], 'Asset URL should include /content/ folder');
        $this->assertStringContainsString('/projects/', $data['url'], 'Asset URL should include page path');
        $this->assertStringContainsString('/01.project-1/', $data['url'], 'Asset URL should include folder with number prefix');
        $this->assertStringContainsString('thumb.jpg', $data['url'], 'Asset URL should include filename');
    }

    public function test_nested_asset_url_structure(): void
    {
        // Test deeply nested asset
        $imagePath = $this->config->contentFolder . '/projects/01.project-1/child-section/grandchild/01.jpg';

        $image = new Image($imagePath, $this->helpers);
        $data = $image->getData();

        // Verify full path structure is preserved
        $this->assertStringContainsString('/content/', $data['url']);
        $this->assertStringContainsString('/projects/', $data['url']);
        $this->assertStringContainsString('/01.project-1/', $data['url']);
        $this->assertStringContainsString('/child-section/', $data['url']);
        $this->assertStringContainsString('/grandchild/', $data['url']);
        $this->assertStringEndsWith('01.jpg', $data['url']);
    }

    public function test_asset_url_accessible_via_http(): void
    {
        // Create test image in fixtures
        $testImagePath = $this->config->contentFolder . '/test-asset/01.jpg';

        if (! file_exists($testImagePath)) {
            $this->markTestSkipped('Test image not found: ' . $testImagePath);
        }

        $image = new Image($testImagePath, $this->helpers);
        $data = $image->getData();

        // URL should start with relative path and include content
        $this->assertMatchesRegularExpression('#^\./|\.\./|^/#', $data['url'], 'URL should start with relative path');
        $this->assertStringContainsString('/content/', $data['url']);
        $this->assertStringContainsString('/test-asset/', $data['url']);
    }

    public function test_asset_url_does_not_start_with_number_prefix(): void
    {
        // This tests that the URL doesn't incorrectly strip the content/ prefix
        // which would result in URLs like ../1.projects/... instead of ../content/1.projects/...
        $imagePath = $this->config->contentFolder . '/2.about/01.jpg';

        if (! file_exists($imagePath)) {
            $this->markTestSkipped('Test image not found: ' . $imagePath);
        }

        $image = new Image($imagePath, $this->helpers);
        $data = $image->getData();

        // The URL should NOT start with something like ./2.about/ (without content/)
        // It should be ./content/2.about/ or similar
        $this->assertDoesNotMatchRegularExpression(
            '#^\./\d+\.[^/]+/#',
            $data['url'],
            'URL should not start with number-prefixed folder directly; content/ prefix is missing'
        );
        $this->assertStringContainsString(
            '/content/',
            $data['url'],
            'URL must include /content/ folder to be accessible via HTTP'
        );
    }
}
