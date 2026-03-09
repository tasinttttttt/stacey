<?php

declare(strict_types=1);

namespace Stacey\Core\Asset;

use Stacey\Core\Helpers;
use Stacey\Core\PageData;

/**
 * Factory for creating asset instances.
 *
 * Replaces the old static factory with a proper instance-based approach.
 */
final class AssetFactory
{
    /** @var array<string, mixed> */
    private static array $store = [];

    /** @var array<string, array<int, string>> */
    private static array $assetSubclasses = [];

    private static bool $initialized = false;

    /**
     * Initialize asset subclass registry.
     */
    private static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Explicitly register asset types instead of using eval()
        self::$assetSubclasses = [
            Image::class => ['gif', 'jpg', 'jpeg', 'png'],
            Video::class => ['mov', 'mp4', 'm4v', 'webm', 'ogv'],
            Html::class => ['html', 'htm'],
        ];

        self::$initialized = true;
    }

    /**
     * Extract page data associated with an asset.
     *
     * @return array<string, mixed>
     */
    private static function extractPageData(string $assetPath): array
    {
        $path = explode('/', $assetPath);
        $fileName = array_pop($path);
        $pagePath = implode('/', $path);

        $pageData = self::get($pagePath);

        return $pageData[strtolower($fileName)] ?? [];
    }

    /**
     * Create an asset instance.
     *
     * @return array<string, mixed>
     */
    private static function create(string $filePath): array
    {
        self::initialize();

        if (! is_string($filePath)) {
            return [];
        }

        // Check for file extension
        if (! preg_match('/\.([\w\d]+)$/', $filePath, $matches)) {
            // No extension - treat as page
            return self::createPage($filePath);
        }

        $extension = strtolower($matches[1]);

        // Skip directories
        if (is_dir($filePath)) {
            return self::createPage($filePath);
        }

        // Find matching asset type
        $assetClass = Asset::class;
        foreach (self::$assetSubclasses as $className => $identifiers) {
            if (in_array($extension, $identifiers, true)) {
                $assetClass = $className;

                break;
            }
        }

        // Create asset and merge with page data
        $helpers = new Helpers(new \Stacey\Core\Config());
        /** @var Asset $asset */
        $asset = new $assetClass($filePath, $helpers);
        $pageData = self::extractPageData($filePath);
        $pageData = PageData::parseVars($pageData, true, '');

        return array_merge($asset->getData(), $pageData);
    }

    /**
     * Create a page asset.
     *
     * @return array<string, mixed>
     */
    private static function createPage(string $filePath): array
    {
        try {
            $page = new Page(Helpers::file_path_to_url($filePath));

            return $page->data;
        } catch (\RuntimeException) {
            // Page not found (404)
            return [];
        }
    }

    /**
     * Get an asset from cache or create new.
     *
     * @return array<string, mixed>
     */
    public static function get(string $key): array
    {
        if (! isset(self::$store[$key])) {
            self::$store[$key] = self::create($key);
        }

        return self::$store[$key];
    }

    /**
     * Clear the asset cache.
     */
    public static function clearCache(): void
    {
        self::$store = [];
    }
}
