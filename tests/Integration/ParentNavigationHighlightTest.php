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
 * Test parent-based navigation highlighting
 * 
 * Scenario: Navigation shows only top-level pages, but when viewing
 * a child page (e.g., projects/01.project-1), the parent "Projects"
 * item should be marked as current.
 */
final class ParentNavigationHighlightTest extends TestCase
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
        
        // Ensure AssetFactory has correct config
        AssetFactory::setConfig($this->config);
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

    /**
     * Test: When on a child page, can we detect if a nav item is the parent?
     */
    public function test_can_detect_parent_in_navigation_on_child_page(): void
    {
        // Simulate being on a child project page
        $container = $this->createContainerWithUri('/projects/project-1/');
        $stacey = $container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/projects/project-1/'));

        // Should render successfully  
        $this->assertStringNotContainsString('<h1>404</h1>', $output);
    }

    /**
     * Test: Parent page data is accessible when on child page
     */
    public function test_parent_data_available_on_child_page(): void
    {
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture does not exist');
        }

        // Get the child page data
        $childPage = AssetFactory::get('projects/01.project-1');

        // Parent should be populated
        $this->assertNotEmpty($childPage['parent'], 'Child page should have parent');
        
        $parent = $childPage['parent'][0];
        
        // Parent should have identifying info we can use for comparison
        $this->assertArrayHasKey('url', $parent, 'Parent should have url');
        $this->assertArrayHasKey('permalink', $parent, 'Parent should have permalink');
        
        // The parent should be the projects page
        $this->assertStringContainsString('projects', $parent['url'], 'Parent URL should contain projects');
    }

    /**
     * Test: Can compare current page's parent slug with known top-level pages
     */
    public function test_can_identify_parent_by_slug(): void
    {
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture does not exist');
        }

        // Get the child page
        $childPage = AssetFactory::get('projects/01.project-1');
        
        // Get parent info
        $parent = $childPage['parent'][0] ?? null;
        $this->assertNotNull($parent, 'Should have parent');
        
        // Get parent slug
        $parentSlug = $parent['slug'] ?? '';
        $this->assertNotEmpty($parentSlug, 'Parent should have slug');
        
        // The slug should be 'projects' (from folder name)
        $this->assertEquals('projects', $parentSlug, 'Parent slug should be "projects"');
        
        // Now we can use this slug to identify which nav item is the parent
        // In a template: {% if item.slug == page.parent[0].slug %}active{% endif %}
    }

    /**
     * Test: Parent has all necessary data for navigation comparison
     */
    public function test_parent_has_navigation_comparison_data(): void
    {
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture does not exist');
        }

        $childPage = AssetFactory::get('projects/01.project-1');
        $parent = $childPage['parent'][0] ?? null;
        
        $this->assertNotNull($parent, 'Should have parent');
        
        // Check all fields useful for navigation comparison
        $this->assertArrayHasKey('slug', $parent, 'Parent should have slug for comparison');
        $this->assertArrayHasKey('url', $parent, 'Parent should have url');
        $this->assertArrayHasKey('permalink', $parent, 'Parent should have permalink');
        $this->assertArrayHasKey('page_name', $parent, 'Parent should have page_name');
        $this->assertArrayHasKey('title', $parent, 'Parent should have title');
    }

    /**
     * Test: Top-level pages have empty parent
     */
    public function test_top_level_pages_have_empty_parent(): void
    {
        // Get a top-level page
        $topLevelPage = AssetFactory::get('about');
        
        // Should have empty parent
        $this->assertEmpty($topLevelPage['parent'], 'Top-level page should have empty parent');
        
        // When on a top-level page, parent-based highlighting would not apply
        // You would use is_current instead
    }

    /**
     * Test: Parent-based highlighting distinguishes between parent and siblings
     */
    public function test_parent_distinguishes_from_siblings(): void
    {
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture does not exist');
        }

        $childPage = AssetFactory::get('projects/01.project-1');
        $parent = $childPage['parent'][0] ?? null;
        
        $this->assertNotNull($parent, 'Should have parent');
        
        // Parent should be 'projects'
        $this->assertEquals('projects', $parent['slug'], 'Parent should be projects');
        
        // Should NOT be 'about' or 'contact' - verify we can distinguish
        $this->assertNotEquals('about', $parent['slug'], 'Parent should not be about');
        $this->assertNotEquals('contact', $parent['slug'], 'Parent should not be contact');
    }

    /**
     * Test: Template example showing how to use parent for navigation
     */
    public function test_template_example_for_parent_based_nav(): void
    {
        $nestedPagePath = CONTENT_ROOT . '/projects/01.project-1';
        
        if (!is_dir($nestedPagePath)) {
            $this->markTestSkipped('Test fixture does not exist');
        }

        // This demonstrates the template logic you would use:
        
        // 1. Get the child page data
        $childPage = AssetFactory::get('projects/01.project-1');
        $parent = $childPage['parent'][0] ?? null;
        $parentSlug = $parent['slug'] ?? '';
        
        // 2. In your template, iterate root items:
        // {% for item in page.root %}
        //
        // 3. Check if this item is the current page's parent:
        // {% set is_parent_active = (page.parent is not empty and item.slug == page.parent[0].slug) %}
        //
        // 4. Combine with is_current for full highlighting:
        // <a href="{{ item.url }}" class="{% if item.is_current or is_parent_active %}active{% endif %}">
        //   {{ item.page_name }}
        // </a>
        //
        // 5. This works because:
        // - When on child page (projects/01.project-1):
        //   - item.is_current = false (this is the Projects top-level page, not the child)
        //   - is_parent_active = true (Projects.slug == parent.slug)
        //   - Result: Projects link is highlighted
        //
        // - When on Projects top-level page:
        //   - item.is_current = true
        //   - is_parent_active = false (Projects has no parent)
        //   - Result: Projects link is highlighted
        
        $this->assertNotEmpty($parentSlug, 'Should have parent slug for comparison');
        $this->assertEquals('projects', $parentSlug, 'Parent slug should be projects');
        
        // The key insight: page.parent[0].slug gives you the parent's identifier
        // You can compare this against any navigation item's slug
    }
}
