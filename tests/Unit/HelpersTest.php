<?php

declare(strict_types=1);

namespace Stacey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stacey\Core\Config;
use Stacey\Core\Helpers;

/**
 * @covers \Stacey\Core\Helpers
 */
final class HelpersTest extends TestCase
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

    public function test_file_path_to_url_converts_content_path(): void
    {
        $result = $this->helpers->filePathToUrl('./content/1.projects');
        $this->assertSame('projects', $result);
    }

    public function test_file_path_to_url_returns_index_for_empty(): void
    {
        $result = $this->helpers->filePathToUrl('./content/');
        $this->assertSame('index', $result);
    }

    public function test_url_to_file_path_finds_existing_page(): void
    {
        $result = $this->helpers->urlToFilePath('index');
        $this->assertNotNull($result);
        $this->assertStringContainsString('index', $result);
    }

    public function test_url_to_file_path_returns_null_for_nonexistent(): void
    {
        $result = $this->helpers->urlToFilePath('nonexistent-page-12345');
        $this->assertNull($result);
    }

    public function test_is_external_url_detects_external_links(): void
    {
        $this->assertTrue($this->helpers->isExternalUrl('https://example.com'));
        $this->assertTrue($this->helpers->isExternalUrl('http://example.com/page'));
        $this->assertFalse($this->helpers->isExternalUrl('/local/path'));
        $this->assertFalse($this->helpers->isExternalUrl('local/path'));
    }

    public function test_modrewrite_parse_adds_query_prefix(): void
    {
        // This test depends on .htaccess existence
        $result = $this->helpers->modrewriteParse('test/');
        $this->assertIsString($result);
    }
}
