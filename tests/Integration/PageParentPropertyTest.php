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

    public function test_parent_property_contains_correct_page_name(): void
    {
        // Get a nested page directly via AssetFactory
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        // Skip if test fixture doesn't exist
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture projects/01.project-1 does not exist');
        }

        $pageData = AssetFactory::get('projects/01.project-1');

        // The parent should contain the projects page data
        $this->assertNotEmpty($pageData['parent'], 'Parent property should not be empty for nested page');
        
        // Check that parent has correct page_name (from folder name "projects")
        $parent = $pageData['parent'][0];
        $this->assertEquals('Projects', $parent['page_name'], 'Parent page_name should be "Projects"');
    }

    public function test_parent_property_contains_correct_title(): void
    {
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture projects/01.project-1 does not exist');
        }

        $pageData = AssetFactory::get('projects/01.project-1');

        $parent = $pageData['parent'][0];
        // Title comes from category.yml
        $this->assertEquals('Projects', $parent['title'], 'Parent title should be "Projects"');
    }

    public function test_parent_property_contains_correct_content(): void
    {
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture projects/01.project-1 does not exist');
        }

        $pageData = AssetFactory::get('projects/01.project-1');

        $parent = $pageData['parent'][0];
        // Content comes from category.yml
        $this->assertStringContainsString('Projects page content', $parent['content'], 'Parent content should contain "Projects page content"');
    }

    public function test_parent_property_contains_url_and_permalink(): void
    {
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture projects/01.project-1 does not exist');
        }

        $pageData = AssetFactory::get('projects/01.project-1');

        $parent = $pageData['parent'][0];
        
        // Check URL is correct
        $this->assertArrayHasKey('url', $parent, 'Parent should have url');
        $this->assertStringContainsString('projects', $parent['url'], 'Parent URL should contain "projects"');
        
        // Check permalink is correct
        $this->assertArrayHasKey('permalink', $parent, 'Parent should have permalink');
        $this->assertStringContainsString('projects', $parent['permalink'], 'Parent permalink should contain "projects"');
    }

    public function test_parent_property_contains_slug(): void
    {
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture projects/01.project-1 does not exist');
        }

        $pageData = AssetFactory::get('projects/01.project-1');

        $parent = $pageData['parent'][0];
        
        // Slug should be derived from folder name
        $this->assertArrayHasKey('slug', $parent, 'Parent should have slug');
        $this->assertEquals('projects', $parent['slug'], 'Parent slug should be "projects"');
    }

    public function test_parent_property_contains_image_data(): void
    {
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture projects/01.project-1 does not exist');
        }

        $pageData = AssetFactory::get('projects/01.project-1');

        $parent = $pageData['parent'][0];
        
        // Check image from category.yml
        $this->assertArrayHasKey('image', $parent, 'Parent should have image');
        $this->assertEquals('01.jpg', $parent['image'], 'Parent image should be "01.jpg"');
        
        // Check image_caption
        $this->assertArrayHasKey('image_caption', $parent, 'Parent should have image_caption');
        $this->assertStringContainsString('Projects overview', $parent['image_caption'], 'Parent image_caption should contain "Projects overview"');
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

    public function test_parents_property_contains_file_paths(): void
    {
        // Get a deeply nested page to test parents property
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture projects/01.project-1 does not exist');
        }

        $pageData = AssetFactory::get('projects/01.project-1');

        $this->assertArrayHasKey('parents', $pageData, 'Page should have parents property');
        
        // parents is an array of file paths, not page data
        $this->assertNotEmpty($pageData['parents'], 'Parents property should not be empty for nested page');
        
        // Check that it contains the content folder path
        $foundContentFolder = false;
        foreach ($pageData['parents'] as $parentPath) {
            if (str_contains($parentPath, 'content')) {
                $foundContentFolder = true;
                break;
            }
        }
        $this->assertTrue($foundContentFolder, 'Parents should contain the content folder path');
    }
}
