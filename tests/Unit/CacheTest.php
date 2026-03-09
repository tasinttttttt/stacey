<?php

declare(strict_types=1);

namespace Stacey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stacey\Core\Cache;
use Stacey\Core\Config;
use Stacey\Core\Helpers;

/**
 * @covers \Stacey\Core\Cache
 */
final class CacheTest extends TestCase
{
    private Config $config;
    private Helpers $helpers;
    private Cache $cache;

    protected function setUp(): void
    {
        $this->config = new Config(
            rootFolder: TEST_ROOT . '/../',
            cacheFolder: TEST_ROOT . '/../app/_cache',
        );

        $this->helpers = new Helpers($this->config, []);
        $this->cache = new Cache($this->config, $this->helpers);
    }

    public function test_generate_hash_returns_ten_character_string(): void
    {
        $hash = $this->cache->generateHash('test string');
        $this->assertSame(10, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $hash);
    }

    public function test_generate_hash_is_consistent(): void
    {
        $hash1 = $this->cache->generateHash('test string');
        $hash2 = $this->cache->generateHash('test string');
        $this->assertSame($hash1, $hash2);
    }

    public function test_generate_hash_produces_different_hashes_for_different_inputs(): void
    {
        $hash1 = $this->cache->generateHash('input1');
        $hash2 = $this->cache->generateHash('input2');
        $this->assertNotSame($hash1, $hash2);
    }

    public function test_generate_cache_key_combines_file_and_template(): void
    {
        $key = $this->cache->generateCacheKey('/path/to/file', 'template.html');
        $this->assertStringContainsString('/path/to/file', $key);
        $this->assertStringContainsString('template.html', $key);
    }

    public function test_get_cache_file_returns_valid_path(): void
    {
        $hash = 'abc123';
        $path = $this->cache->getCacheFile($hash);
        $this->assertStringContainsString('abc123', $path);
        $this->assertStringContainsString('app/_cache/pages', $path);
    }
}
