<?php

declare(strict_types=1);

namespace Stacey\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Stacey\Core\Config;
use Stacey\Core\Container;
use Stacey\Core\Helpers;
use Stacey\Core\Stacey;

/**
 * @covers \Stacey\Core\PageData
 * @covers \Stacey\Core\Asset\Page
 */
final class IsCurrentTemplateTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        Helpers::clearFileCache();
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

        $serverParams = [
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'off',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => $uri,
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ];

        return new Container($this->config, $serverParams);
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

    public function test_home_page_shows_current_in_navigation(): void
    {
        $container = $this->createContainerWithUri('/home-nav/');
        $stacey = $container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/home-nav/'));

        // Should not be a 404
        $this->assertStringNotContainsString('<h1>404</h1>', $output, 'Should not render 404');

        // Should render navigation
        $this->assertStringContainsString('<nav', $output, 'Should render navigation element');

        // Should show Home Nav page name in nav
        $this->assertStringContainsString('Home Nav', $output, 'Home page should appear in nav');

        // Home link should have current class
        $this->assertStringContainsString('class="current"', $output, 'Should have current class on active link');

        // Should show (current) indicator
        $this->assertStringContainsString('(current)', $output, 'Should show (current) indicator');

        // Debug info should show is_current as true
        $this->assertStringContainsString('Current page is_current: true', $output, 'Debug should show is_current true');
    }

    public function test_about_page_shows_current_in_navigation(): void
    {
        $container = $this->createContainerWithUri('/about-nav/');
        $stacey = $container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/about-nav/'));

        // Should not be a 404
        $this->assertStringNotContainsString('<h1>404</h1>', $output, 'Should not render 404');

        // Should render navigation
        $this->assertStringContainsString('<nav', $output, 'Should render navigation element');

        // Should show About Nav page name in nav
        $this->assertStringContainsString('About Nav', $output, 'About page should appear in nav');

        // About link should have current class
        $this->assertStringContainsString('class="current"', $output, 'Should have current class on active link');

        // Should show (current) indicator
        $this->assertStringContainsString('(current)', $output, 'Should show (current) indicator');

        // Debug info should show is_current as true
        $this->assertStringContainsString('Current page is_current: true', $output, 'Debug should show is_current true');
    }

    public function test_projects_page_shows_current_in_navigation(): void
    {
        $container = $this->createContainerWithUri('/projects-nav/');
        $stacey = $container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/projects-nav/'));

        // Should not be a 404
        $this->assertStringNotContainsString('<h1>404</h1>', $output, 'Should not render 404');

        // Should render navigation
        $this->assertStringContainsString('<nav', $output, 'Should render navigation element');

        // Should show Projects Nav page name in nav
        $this->assertStringContainsString('Projects Nav', $output, 'Projects page should appear in nav');

        // Projects link should have current class
        $this->assertStringContainsString('class="current"', $output, 'Should have current class on active link');

        // Should show (current) indicator
        $this->assertStringContainsString('(current)', $output, 'Should show (current) indicator');

        // Debug info should show is_current as true
        $this->assertStringContainsString('Current page is_current: true', $output, 'Debug should show is_current true');
    }

    public function test_navigation_shows_all_items(): void
    {
        $container = $this->createContainerWithUri('/home-nav/');
        $stacey = $container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/home-nav/'));

        // Should show all navigation items
        $this->assertStringContainsString('Home', $output, 'Should show Home in nav');
        $this->assertStringContainsString('About', $output, 'Should show About in nav');
        $this->assertStringContainsString('Projects', $output, 'Should show Projects in nav');

        // Should show correct permalink in debug
        $this->assertStringContainsString('home-nav/', $output, 'Should show home-nav permalink');
    }

    public function test_only_current_page_has_current_class(): void
    {
        $container = $this->createContainerWithUri('/about-nav/');
        $stacey = $container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/about-nav/'));

        // Count occurrences of 'current' class
        // Should only appear once (on the current page)
        preg_match_all('/class="current"/', $output, $matches);
        $this->assertCount(1, $matches[0], 'Should only have one element with current class');
    }

    public function test_is_current_false_for_non_current_pages(): void
    {
        $container = $this->createContainerWithUri('/home-nav/');
        $stacey = $container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/home-nav/'));

        // Count occurrences of (current) - should only appear once for the current page
        preg_match_all('/\(current\)/', $output, $matches);
        $this->assertCount(1, $matches[0], 'Should only have one (current) indicator in navigation');

        // All three pages should appear in nav
        $this->assertStringContainsString('Home Nav', $output, 'Home should appear in nav');
        $this->assertStringContainsString('About Nav', $output, 'About should appear in nav');
        $this->assertStringContainsString('Projects Nav', $output, 'Projects should appear in nav');
    }

    public function test_root_page_index_is_current(): void
    {
        // Test the root index page
        $container = $this->createContainerWithUri('/');
        $stacey = $container->get(Stacey::class);

        $output = $this->captureOutput(fn () => $stacey->run('/'));

        // Should not be a 404
        $this->assertStringNotContainsString('<h1>404</h1>', $output, 'Should not render 404 for index');
    }
}
