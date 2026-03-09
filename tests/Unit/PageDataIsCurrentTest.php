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
    }

    public function test_is_current_returns_true_for_root_index_page(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // When permalink is 'index' and request URI is '/', should be current
        $result = $pageData->isCurrent('localhost', 'index');

        $this->assertTrue($result);
    }

    public function test_is_current_returns_false_for_index_when_not_on_root(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/projects',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // When permalink is 'index' but request URI is '/projects', should NOT be current
        $result = $pageData->isCurrent('localhost', 'index');

        $this->assertFalse($result);
    }

    public function test_is_current_returns_true_for_matching_permalink(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/projects',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // When permalink is 'projects' and request URI is '/projects', should be current
        $result = $pageData->isCurrent('localhost', 'projects');

        $this->assertTrue($result);
    }

    public function test_is_current_returns_false_for_non_matching_permalink(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/about',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // When permalink is 'projects' but request URI is '/about', should NOT be current
        $result = $pageData->isCurrent('localhost', 'projects');

        $this->assertFalse($result);
    }

    public function test_is_current_handles_base_url_with_path(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/subdir/projects',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // Base URL 'localhost/subdir' should extract path '/subdir'
        // Then check if '/subdir' + '/' + 'projects' matches '/subdir/projects'
        $result = $pageData->isCurrent('localhost/subdir', 'projects');

        $this->assertTrue($result);
    }

    public function test_is_current_handles_nested_permalinks(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/projects/my-project',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // Should match nested permalink 'projects/my-project'
        $result = $pageData->isCurrent('localhost', 'projects/my-project');

        $this->assertTrue($result);
    }

    public function test_is_current_with_different_base_urls(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/blog/posts/hello-world',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // Should work with different base URLs
        $result = $pageData->isCurrent('example.com', 'blog/posts/hello-world');

        $this->assertTrue($result);
    }

    public function test_is_current_with_protocol_in_base_url_leaves_slashes(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '//example.com/path/to/page',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // When baseUrl has protocol like 'https://example.com', the regex /^[^\/]+/
        // matches 'https:' (everything before first /) and removes it, leaving '//example.com'
        // So the constructed path becomes '//example.com/path/to/page'
        $result = $pageData->isCurrent('https://example.com', 'path/to/page');

        // This reveals that the implementation keeps the '//' when there's a protocol
        $this->assertTrue($result);
    }

    public function test_is_current_with_simple_domain_base_url(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/path/to/page',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // When baseUrl has no slashes like 'example.com', the regex matches the entire string
        // So basePath becomes '', and the result is '/path/to/page'
        $result = $pageData->isCurrent('example.com', 'path/to/page');

        $this->assertTrue($result);
    }

    public function test_is_current_handles_trailing_slash_in_request_uri(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/projects/',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // Note: The comparison is exact string match
        // 'projects' (no trailing slash) vs '/projects/' (with trailing slash)
        $result = $pageData->isCurrent('localhost', 'projects');

        $this->assertFalse($result);
    }

    public function test_is_current_defaults_to_root_when_no_request_uri(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'localhost',
            // REQUEST_URI not set
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // When REQUEST_URI is not set, defaults to '/'
        $result = $pageData->isCurrent('localhost', 'index');

        $this->assertTrue($result);
    }

    public function test_is_current_with_empty_permalink_on_root_page(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // When permalink is empty string and on root page '/':
        // basePath = '' (localhost is removed), then '' + '/' + '' = '/'
        // This matches REQUEST_URI '/', so empty permalink is considered current on root
        // Note: This might be unintended behavior - empty permalink equals root
        $result = $pageData->isCurrent('localhost', '');

        $this->assertTrue($result);
    }

    public function test_is_current_with_empty_permalink_on_non_root_page(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/projects',
        ];

        $pageData = new PageData($this->config, $this->helpers, $serverParams);

        // Empty string permalink on non-root page:
        // basePath = '', result = '/', REQUEST_URI = '/projects'
        // '/' !== '/projects', so not current
        $result = $pageData->isCurrent('localhost', '');

        $this->assertFalse($result);
    }
}
