<?php

declare(strict_types=1);

namespace Stacey\Core;

use Stacey\Core\Asset\AssetFactory;
use Stacey\Core\Asset\Page;

/**
 * Page caching system.
 *
 * Manages file-based caching of rendered pages using MD5 hash-based filenames.
 */
final readonly class Cache
{
    private const string CACHE_PREFIX = 'c-';
    private const int HASH_LENGTH = 10;

    public function __construct(
        private Config $config,
        private Helpers $helpers,
    ) {
    }

    /**
     * Generate a cache key from file path and template.
     */
    public function generateCacheKey(string $filePath, string $templateFile): string
    {
        return $filePath . ':' . $templateFile;
    }

    /**
     * Generate a short MD5 hash.
     */
    public function generateHash(string $str): string
    {
        return substr(md5($str), 0, self::HASH_LENGTH);
    }

    /**
     * Get the cache file path for a given hash.
     */
    public function getCacheFile(string $hash): string
    {
        return $this->config->cacheFolder . '/pages/' . self::CACHE_PREFIX . $hash;
    }

    /**
     * Check if a cache file has expired.
     */
    public function expired(string $cacheFile): bool
    {
        return ! file_exists($cacheFile);
    }

    /**
     * Render cached content.
     */
    public function render(string $cacheFile): string
    {
        return file_get_contents($cacheFile) ?: '';
    }

    /**
     * Create a new cache entry.
     */
    public function create(string $route, string $filePath, string $templateFile): string
    {
        $this->deleteOldCaches($filePath, $templateFile);

        $page = new Page($route, false, $this->config);

        // Basic Authentication
        if (isset($page->data['password_protect'])) {
            new BasicAuth($page->data['password_protect']);
        }

        ob_start();
        echo $page->parseTemplate();
        $content = ob_get_clean();

        if ($content === false) {
            $content = '';
        }

        // Write cache if folder is writable and not bypassed
        $cacheFolder = $this->config->cacheFolder . '/pages';
        if (is_writable($cacheFolder) && empty($page->data['bypass_cache'])) {
            $cacheKey = $this->generateCacheKey($filePath, $templateFile);
            $hash = $this->generateHash($this->buildHashContent($cacheKey));
            $cacheFile = $this->getCacheFile($hash);
            $this->writeCache($cacheFile, $content);
        }

        return $content;
    }

    /**
     * Delete old cache files for a route.
     */
    private function deleteOldCaches(string $filePath, string $templateFile): void
    {
        $pathHash = $this->generateHash($filePath . ':' . $templateFile);
        $pattern = $this->config->cacheFolder . '/pages/' . self::CACHE_PREFIX . $pathHash . '-*';

        /** @var array<int, string>|false $oldCaches */
        $oldCaches = glob($pattern);
        if (is_array($oldCaches)) {
            foreach ($oldCaches as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Write content to cache file.
     */
    private function writeCache(string $cacheFile, string $content): void
    {
        file_put_contents($cacheFile, $content);
    }

    /**
     * Build hash content including site state.
     */
    private function buildHashContent(string $cacheKey): string
    {
        $htaccess = '';
        $htaccessPath = $this->config->rootFolder . '.htaccess';
        if (file_exists($htaccessPath)) {
            $htaccess = '.htaccess:' . filemtime($htaccessPath);
        }

        $fileCache = serialize($this->helpers->fileCache());

        return $cacheKey . ':' . $htaccess . $fileCache;
    }

    /**
     * Get full site cache.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFullCache(): array
    {
        $htaccessPath = $this->config->rootFolder . '.htaccess';
        $htaccess = file_exists($htaccessPath) ? '.htaccess:' . filemtime($htaccessPath) : '';
        $fileCache = serialize($this->helpers->fileCache());
        $contentHash = $this->generateHash($htaccess . $fileCache);

        $cacheFile = $this->config->cacheFolder . '/pages/all-content-' . $contentHash;

        if (file_exists($cacheFile)) {
            /** @var array<int, array<string, mixed>> */
            return json_decode(file_get_contents($cacheFile) ?: '[]', true) ?: [];
        }

        $this->deleteOldFullCaches();
        $fullCache = $this->createFullCache();
        $this->saveFullCache($fullCache, $contentHash);

        return $fullCache;
    }

    /**
     * Delete old full cache files.
     */
    private function deleteOldFullCaches(): void
    {
        $pattern = $this->config->cacheFolder . '/pages/all-content-*';
        /** @var array<int, string>|false $oldCaches */
        $oldCaches = glob($pattern);
        if (is_array($oldCaches)) {
            foreach ($oldCaches as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Save full cache to file.
     *
     * @param array<int, array<string, mixed>> $fullCache
     */
    private function saveFullCache(array $fullCache, string $hash): void
    {
        $cacheFolder = $this->config->cacheFolder . '/pages';
        $cacheFile = $cacheFolder . '/all-content-' . $hash;

        if (is_writable($cacheFolder)) {
            file_put_contents($cacheFile, json_encode($fullCache));
        }
    }

    /**
     * Create full site cache.
     *
     * @param array<int, array<string, mixed>>|null $pages
     * @return array<int, array<string, mixed>>
     */
    public function createFullCache(?array $pages = null): array
    {
        $searchFields = ['url', 'file_path', 'title', 'author', 'content'];
        $store = [];

        if ($pages === null) {
            $pages = $this->helpers->fileCache($this->config->contentFolder);
        }

        foreach ($pages as $page) {
            if (empty($page['is_folder'])) {
                continue;
            }

            $currentPage = AssetFactory::get($page['path']);
            // Skip password protected or hidden pages
            if (isset($currentPage['password_protect'])) {
                continue;
            }
            if (isset($currentPage['hide_from_search'])) {
                continue;
            }

            // Only keep search-relevant fields
            $filteredPage = [];
            foreach ($currentPage as $key => $value) {
                if (in_array($key, $searchFields, true)) {
                    $filteredPage[$key] = $value;
                }
            }

            $store[] = $filteredPage;

            // Recursively cache children
            $children = $this->createFullCache($this->helpers->fileCache($page['path']));
            $store = array_merge($store, $children);
        }

        return $store;
    }
}
