<?php

declare(strict_types=1);

namespace Stacey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stacey\Core\Config;
use Stacey\Core\Helpers;
use Stacey\Core\PageData;

/**
 * @covers \Stacey\Core\PageData
 */
final class PageDataIsCurrentTest extends TestCase
{
    private Config $config;
    private Helpers $helpers;

    protected function setUp(): void
    {
        // Clear file cache to avoid pollution between tests
        Helpers::clearFileCache();

        $this->config = new Config(
            rootFolder: TEST_ROOT . '/../',
            contentFolder: TEST_ROOT . '/../content',
            templatesFolder: TEST_ROOT . '/../templates',
        );

        $this->helpers = new Helpers($this->config, [
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'off',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
        ]);

        // Clear the global route before each test
        $GLOBALS['current_route'] = '';
    }

    protected function tearDown(): void
    {
        // Clean up global state after each test
        unset($GLOBALS['current_route']);
    }

    public function test_is_current_returns_true_when_route_matches_permalink(): void
    {
        $GLOBALS['current_route'] = 'projects';

        $pageData = new PageData($this->config, $this->helpers, []);

        $result = $pageData->isCurrent('projects');

        $this->assertTrue($result);
    }

    public function test_is_current_returns_false_when_route_does_not_match(): void
    {
        $GLOBALS['current_route'] = 'about';

        $pageData = new PageData($this->config, $this->helpers, []);

        $result = $pageData->isCurrent('projects');

        $this->assertFalse($result);
    }

    public function test_is_current_handles_nested_routes(): void
    {
        $GLOBALS['current_route'] = 'projects/my-project';

        $pageData = new PageData($this->config, $this->helpers, []);

        $result = $pageData->isCurrent('projects/my-project');

        $this->assertTrue($result);
    }

    public function test_is_current_handles_trailing_slashes(): void
    {
        $GLOBALS['current_route'] = 'projects/';

        $pageData = new PageData($this->config, $this->helpers, []);

        // Should match even with trailing slash differences
        $result = $pageData->isCurrent('projects');

        $this->assertTrue($result);
    }

    public function test_is_current_index_page_when_route_is_empty(): void
    {
        $GLOBALS['current_route'] = '';

        $pageData = new PageData($this->config, $this->helpers, []);

        // Index page should be current when route is empty
        $result = $pageData->isCurrent('index');

        $this->assertTrue($result);
    }

    public function test_is_current_index_page_when_route_is_index(): void
    {
        $GLOBALS['current_route'] = 'index';

        $pageData = new PageData($this->config, $this->helpers, []);

        // Index page should be current when route is 'index'
        $result = $pageData->isCurrent('index');

        $this->assertTrue($result);
    }

    public function test_is_current_index_page_not_current_when_on_other_page(): void
    {
        $GLOBALS['current_route'] = 'projects';

        $pageData = new PageData($this->config, $this->helpers, []);

        // Index page should NOT be current when on another page
        $result = $pageData->isCurrent('index');

        $this->assertFalse($result);
    }

    public function test_is_current_defaults_to_empty_route_when_not_set(): void
    {
        unset($GLOBALS['current_route']);

        $pageData = new PageData($this->config, $this->helpers, []);

        // When current_route is not set, defaults to empty (root)
        $result = $pageData->isCurrent('index');

        $this->assertTrue($result);
    }

    public function test_is_current_with_complex_nested_path(): void
    {
        $GLOBALS['current_route'] = 'blog/2024/january/my-post';

        $pageData = new PageData($this->config, $this->helpers, []);

        $result = $pageData->isCurrent('blog/2024/january/my-post');

        $this->assertTrue($result);
    }

    public function test_is_current_is_case_sensitive(): void
    {
        $GLOBALS['current_route'] = 'Projects';

        $pageData = new PageData($this->config, $this->helpers, []);

        // Route comparison should be case-sensitive
        $result = $pageData->isCurrent('projects');

        $this->assertFalse($result);
    }

    public function test_is_current_empty_permalink_on_root(): void
    {
        $GLOBALS['current_route'] = '';

        $pageData = new PageData($this->config, $this->helpers, []);

        // Empty permalink should be considered current on root
        $result = $pageData->isCurrent('');

        $this->assertTrue($result);
    }

    public function test_is_current_empty_permalink_not_current_on_other_page(): void
    {
        $GLOBALS['current_route'] = 'projects';

        $pageData = new PageData($this->config, $this->helpers, []);

        // Empty permalink should NOT be current on non-root page
        $result = $pageData->isCurrent('');

        $this->assertFalse($result);
    }
}
