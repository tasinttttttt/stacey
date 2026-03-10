<?php

declare(strict_types=1);

namespace Stacey\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Stacey\Core\Asset\AssetFactory;
use Stacey\Core\Config;
use Stacey\Core\Container;
use Stacey\Core\Helpers;
use Stacey\Core\Stacey;

/**
 * @covers \Stacey\Core\PageData
 * @covers \Stacey\Core\Asset\Page
 */
final class PageParentPropertyTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        Helpers::clearFileCache();
        AssetFactory::clearCache();
        $this->clearPageCache();

        $this->config = new Config(
            rootFolder: TEST_ROOT . '/Fixtures/',
            contentFolder: CONTENT_ROOT,
            templatesFolder: TEST_ROOT . '/Fixtures/templates',
            cacheFolder: TEST_ROOT . '/../app/_cache',
        );
    }

    private function clearPageCache(): void
    {
        $cacheDir = __DIR__ . '/../../app/_cache/pages';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }

    private function createContainerWithUri(string $uri): Container
    {
        $this->clearPageCache();

        return new Container($this->config, [
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'off',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => $uri,
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);
    }

    private function captureOutput(callable $callback): string
    {
        ob_start();

        try {
            $callback();

            return ob_get_clean() ?: '';
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }
    }

    public function test_child_page_has_parent(): void
    {
        // Test a page that is nested (e.g., projects/01.project-1)
        $container = $this->createContainerWithUri('/projects/project-1/');
        $stacey = $container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/projects/project-1/'));

        // Should render successfully
        $this->assertStringNotContainsString('<h1>404</h1>', $output, 'Should not render 404');
    }

    public function test_parent_property_is_not_empty_for_nested_page(): void
    {
        // Get a nested page directly via AssetFactory
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        // Skip if test fixture doesn't exist
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture projects/01.project-1 does not exist');
        }

        $pageData = AssetFactory::get('projects/01.project-1');

        // Debug: Check what parent property contains
        $this->assertArrayHasKey('parent', $pageData, 'Page should have parent property');
        
        // The parent should contain the projects page data
        $this->assertNotEmpty($pageData['parent'], 'Parent property should not be empty for nested page');
        
        // Check that parent has expected data
        $parent = $pageData['parent'][0] ?? null;
        $this->assertIsArray($parent, 'Parent should be an array');
        $this->assertArrayHasKey('page_name', $parent, 'Parent should have page_name');
        $this->assertArrayHasKey('url', $parent, 'Parent should have url');
    }

    public function test_root_page_has_empty_parent(): void
    {
        // Get root page (index)
        $pageData = AssetFactory::get('index');

        $this->assertArrayHasKey('parent', $pageData, 'Page should have parent property');
        $this->assertEmpty($pageData['parent'], 'Root page should have empty parent');
    }

    public function test_top_level_page_has_empty_parent(): void
    {
        // Get a top-level page (e.g., about)
        $pageData = AssetFactory::get('about');

        $this->assertArrayHasKey('parent', $pageData, 'Page should have parent property');
        $this->assertEmpty($pageData['parent'], 'Top-level page should have empty parent');
    }

    public function test_parents_property_contains_all_ancestors(): void
    {
        // Get a deeply nested page to test parents property
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture projects/01.project-1 does not exist');
        }

        $pageData = AssetFactory::get('projects/01.project-1');

        $this->assertArrayHasKey('parents', $pageData, 'Page should have parents property');
        
        // For a page nested under 'projects', parents should contain 'projects'
        $this->assertNotEmpty($pageData['parents'], 'Parents property should not be empty for nested page');
    }
}
