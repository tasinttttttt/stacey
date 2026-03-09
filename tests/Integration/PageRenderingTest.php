<?php

declare(strict_types=1);

namespace Stacey\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Stacey\Core\Config;
use Stacey\Core\Container;
use Stacey\Core\Helpers;
use Stacey\Core\Stacey;

/**
 * @covers \Stacey\Core\Stacey
 * @covers \Stacey\Core\Cache
 * @covers \Stacey\Core\Asset\Page
 */
final class PageRenderingTest extends TestCase
{
    private Config $config;
    private Container $container;

    protected function setUp(): void
    {
        // Clear file cache to avoid pollution between tests
        Helpers::clearFileCache();

        // Use relative paths to match how static methods create Config instances
        $this->config = new Config(
            rootFolder: './',
            contentFolder: './content',
            templatesFolder: './templates',
            cacheFolder: './app/_cache',
        );

        $this->container = new Container($this->config, [
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'off',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);
    }

    public function test_can_create_stacey_instance(): void
    {
        $stacey = $this->container->get(Stacey::class);

        $this->assertInstanceOf(Stacey::class, $stacey);
    }

    public function test_can_render_index_page(): void
    {
        $stacey = $this->container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/'));

        $this->assertNotEmpty($output);
    }

    public function test_server_returns_rendered_index_page(): void
    {
        $stacey = $this->container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/'));

        $this->assertNotEmpty($output, 'Server should return non-empty output');
        $this->assertStringContainsString('<title>Home</title>', $output, 'Should render title from content/index.yml');
        $this->assertStringContainsString('<h1>Home</h1>', $output, 'Should render heading from template using page data');
        $this->assertStringContainsString('A test home page', $output, 'Should render page description from content/index.yml');
        $this->assertStringContainsString('This is the home page content', $output, 'Should render content from content/index.yml');
        $this->assertStringContainsString('<!DOCTYPE html>', $output, 'Should render full HTML structure from template');
        $this->assertStringContainsString('<footer>', $output, 'Should render template footer');
    }

    public function test_can_render_projects_page(): void
    {
        $stacey = $this->container->get(Stacey::class);

        // Note: URL must have trailing slash to avoid redirect
        $output = $this->captureOutput(fn () => $stacey->run('/projects/'));

        // The projects page uses 'category' template which doesn't exist,
        // so it should render a 404
        $this->assertNotEmpty($output);
    }

    public function test_handles_404_for_nonexistent_page(): void
    {
        $stacey = $this->container->get(Stacey::class);

        // Note: URL must have trailing slash to avoid redirect
        $output = $this->captureOutput(fn () => $stacey->run('/nonexistent-page-12345/'));

        // Should render 404 page content
        $this->assertStringContainsString('404', $output);
    }

    public function test_trailing_slash_redirect_does_not_throw(): void
    {
        $stacey = $this->container->get(Stacey::class);

        // This should not throw an exception
        $this->captureOutput(fn () => $stacey->run('/projects'));

        // If we get here, no exception was thrown
        $this->assertTrue(true);
    }

    public function test_page_with_nonexistent_template_defaults_to_default_template(): void
    {
        $stacey = $this->container->get(Stacey::class);

        // This page has a template name 'nonexistent' which doesn't exist
        // It should fall back to default.html template
        $output = $this->captureOutput(fn () => $stacey->run('/projects/nonexistent/'));

        // Should not be a 404
        $this->assertStringNotContainsString('<h1>404</h1>', $output, 'Should not render 404 for existing page');

        // Should render with default.html template (which contains these elements)
        $this->assertStringContainsString("'s Portfolio", $output, 'Should use default.html template');
        $this->assertStringContainsString('Gräphic Designer', $output, 'Should render shared data in default template');
        $this->assertStringContainsString('<link rel="stylesheet"', $output, 'Should render default template CSS');
    }

    public function test_page_renders_image_assets(): void
    {
        $stacey = $this->container->get(Stacey::class);

        // Test page with image asset - uses default template since 'test-asset' template doesn't exist
        $output = $this->captureOutput(fn () => $stacey->run('/test-asset/'));

        // Should not be a 404
        $this->assertStringNotContainsString('<h1>404</h1>', $output, 'Should not render 404 for existing page');

        // Should render with default template
        $this->assertStringContainsString("'s Portfolio", $output, 'Should use default.html template');

        // Check for image tags (from project thumbnails in default template)
        $this->assertStringContainsString('<img', $output, 'Should render image HTML elements');
    }

    public function test_nested_pages_render_correctly(): void
    {
        $stacey = $this->container->get(Stacey::class);

        // Test child page (one level deep)
        $childOutput = $this->captureOutput(fn () => $stacey->run('/projects/project-1/child-section/'));

        // Should not be a 404
        $this->assertStringNotContainsString('<h1>404</h1>', $childOutput, 'Child page should not render 404');

        // Should render with default template (nested pages use default template)
        $this->assertStringContainsString("'s Portfolio", $childOutput, 'Should use default.html template for child page');

        // Check URL paths are correctly nested (should have ../../../ for 3 levels deep from /projects/project-1/child-section/)
        $this->assertStringContainsString('../../../', $childOutput, 'Should have correct relative paths for nested child page');

        // Test grandchild page (two levels deep)
        $grandchildOutput = $this->captureOutput(fn () => $stacey->run('/projects/project-1/child-section/grandchild/'));

        // Should not be a 404
        $this->assertStringNotContainsString('<h1>404</h1>', $grandchildOutput, 'Grandchild page should not render 404');

        // Should render with default template
        $this->assertStringContainsString("'s Portfolio", $grandchildOutput, 'Should use default.html template for grandchild page');

        // Check URL paths are correctly nested (should have ../../../../ for 4 levels deep)
        $this->assertStringContainsString('../../../../', $grandchildOutput, 'Should have correct relative paths for nested grandchild page');
    }

    public function test_can_loop_through_pages_with_images_and_captions(): void
    {
        $stacey = $this->container->get(Stacey::class);

        // Test page that loops through all top-level pages and displays their images with captions
        $output = $this->captureOutput(fn () => $stacey->run('/page-loop-test/'));

        // Should not be a 404
        $this->assertStringNotContainsString('<h1>404</h1>', $output, 'Page loop test should not render 404');

        // Should render the page loop template
        $this->assertStringContainsString('Page Loop Test', $output, 'Should render page title');
        $this->assertStringContainsString('page-list', $output, 'Should render page list container');

        // Should iterate through top-level pages (Projects, About, Contact)
        // Projects is numbered 1.projects so it appears in page.root
        $this->assertStringContainsString('Projects', $output, 'Should display Projects page title in loop');
        $this->assertStringContainsString('About', $output, 'Should display About page title in loop');
        $this->assertStringContainsString('Contact', $output, 'Should display Contact page title in loop');

        // Each top-level page now has images (01.jpg), so all should render img tags
        $this->assertStringContainsString('<img', $output, 'Should render image elements');

        // Should have image elements for each page
        $imgCount = substr_count($output, '<img');
        $this->assertGreaterThanOrEqual(4, $imgCount, 'Should have at least 4 image tags (one per top-level page)');

        // Should render image captions from YAML content
        $this->assertStringContainsString('image-caption', $output, 'Should render image caption CSS class');
        
        // Should display specific captions set in YAML files
        $this->assertStringContainsString('Projects overview image showing all work', $output, 'Should render Projects image caption from YAML');
        $this->assertStringContainsString('About page hero image', $output, 'Should render About image caption from YAML');
        $this->assertStringContainsString('Contact page illustration', $output, 'Should render Contact image caption from YAML');
    }

    public function test_inline_images_render_with_captions(): void
    {
        $stacey = $this->container->get(Stacey::class);

        // Test page that uses inline markdown image syntax: ![Caption](filename.jpg)
        $output = $this->captureOutput(fn () => $stacey->run('/inline-image-test/'));

        // Should not be a 404
        $this->assertStringNotContainsString('<h1>404</h1>', $output, 'Page should not render 404');

        // Should render the page content with inline image
        $this->assertStringContainsString('Inline Image Test', $output, 'Should render page title');

        // Should render inline image from markdown content
        // Markdown syntax: ![Caption](01.jpg) should become <img> tag
        $this->assertStringContainsString('<img', $output, 'Should render inline image from markdown');

        // The inline image caption should be rendered as alt text
        $this->assertStringContainsString('A beautiful test image showing the caption feature', $output, 'Should render inline image caption as alt text');
    }

    /**
     * Helper to capture output from a callable
     */
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
}
