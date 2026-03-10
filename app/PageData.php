<?php

declare(strict_types=1);

namespace Stacey\Core;

use Stacey\Core\Asset\Image;
use Stacey\Core\Parser\MarkdownParser;
use Symfony\Component\Yaml\Yaml;

/**
 * Page data generator and processor.
 *
 * Creates all dynamic data for page rendering.
 */
final class PageData
{
    /** @var array<string, mixed>|null */
    private static ?array $shared = null;

    public function __construct(
        private readonly Config $config,
        private readonly Helpers $helpers,
        private readonly array $serverParams = [],
    ) {
    }

    /**
     * Main entry point to create all page data.
     *
     * @param object $page Page object to populate
     * @param bool $content Whether to parse content file
     */
    public function generate(object $page, bool $content = false): void
    {
        $this->createTextfileVars($page, $content);
        $this->createCollections($page);
        $this->createVars($page);
        $this->createAssetCollections($page);
        $this->postProcess($page);
    }

    /**
     * Extract closest siblings (previous and next).
     *
     * @param array<string, string> $siblings
     * @return array{0: string|false, 1: string|false}
     */
    public function extractClosestSiblings(array $siblings, string $filePath): array
    {
        $neighbors = [];
        $siblings = array_flip($siblings);
        $keys = array_keys($siblings);
        $keyIndexes = array_flip($keys);

        if ($siblings !== [] && isset($siblings[$filePath])) {
            // Previous sibling
            $neighbors[] = $keys[$keyIndexes[$filePath] - 1] ?? false;
            // Next sibling
            $neighbors[] = $keys[$keyIndexes[$filePath] + 1] ?? false;
        }

        return empty($neighbors) ? [false, false] : $neighbors;
    }

    /**
     * Get parent directory path.
     *
     * @return array<int, string>
     */
    public function getParent(string $filePath): array
    {
        $splitPath = explode('/', $filePath);
        array_pop($splitPath);
        $parentPath = [implode('/', $splitPath)];

        return $parentPath[0] === $this->config->contentFolder ? [] : $parentPath;
    }

    /**
     * Get all parent directories.
     *
     * @return array<int, string>
     */
    public function getParents(string $filePath): array
    {
        $splitPath = explode('/', $filePath);
        $parents = [];

        while (count($splitPath) > 3) {
            array_pop($splitPath);
            $parents[] = implode('/', $splitPath);
        }

        return count($parents) < 1 ? [] : $parents;
    }

    /**
     * Get thumbnail for a page.
     *
     * @return array<string, mixed>|false
     */
    public function getThumbnail(string $filePath): array|false
    {
        $config = new Config(); // Temporary for BC
        $helpers = new Helpers($config);
        $thumbnails = array_keys($helpers->listFiles($filePath, '/thumb\.(gif|jpg|png|jpeg)$/i', false));

        if ($thumbnails !== []) {
            $thumb = new Image($filePath . '/' . $thumbnails[0], $helpers);

            return $thumb->getData();
        }

        return false;
    }

    /**
     * Get index position in siblings list.
     */
    public function getIndex(array $siblings, string $filePath): string
    {
        $count = 0;

        foreach ($siblings as $sibling) {
            $count++;
            if ($sibling === $filePath) {
                return (string) $count;
            }
        }

        return '0';
    }

    /**
     * Check if current page is active.
     */
    public function isCurrent(string $permalink): bool
    {
        // Get the clean route from Stacey (e.g., "about" or "projects/my-project")
        $currentRoute = $GLOBALS['current_route'] ?? '';

        // Normalize: remove trailing slashes for comparison
        $currentRoute = rtrim($currentRoute, '/');
        $permalink = rtrim($permalink, '/');

        // Index page is current when route is empty or 'index'
        if ($permalink === 'index') {
            return $currentRoute === '' || $currentRoute === 'index';
        }

        return $currentRoute === $permalink;
    }

    /**
     * Get file types in a directory.
     *
     * @return array<string, array<string, string>>
     */
    public function getFileTypes(string $filePath): array
    {
        $fileTypes = [];
        $config = new Config(); // Temporary for BC
        $helpers = new Helpers($config);

        foreach ($helpers->listFiles($filePath, '/\.[\w\d]+?$/', false) as $filename => $path) {
            preg_match('/(?<!thumb|_lge|_sml)\.(?!yml)([\w\d]+?)$/', $filename, $ext);
            if (isset($ext[1]) && ! is_dir($path)) {
                $fileTypes[$ext[1]][$filename] = $path;
            }
        }

        return $fileTypes;
    }

    /**
     * Create page variables.
     */
    private function createVars(object $page): void
    {
        $page->data['file_path'] = $page->filePath;
        
        // Create content-relative path for use in templates (e.g., "1.work/04.A-portee-oreille")
        $contentPath = $page->filePath;
        if (str_starts_with($contentPath, $this->config->contentFolder)) {
            $contentPath = substr($contentPath, strlen($this->config->contentFolder));
            $contentPath = ltrim($contentPath, '/');
        }
        $page->data['content_path'] = $contentPath;
        
        $page->url = $this->helpers->relativeRootPath($page->urlPath . '/');
        $page->permalink = $this->helpers->modrewriteParse($page->urlPath . '/');

        // Ensure data array has required keys with default values
        /** @var string $slug */
        $slug = $page->data['slug'] ?? '';
        /** @var array<int, string> $siblingsAndSelf */
        $siblingsAndSelf = $page->data['siblings_and_self'] ?? [];
        /** @var array<int, string> $children */
        $children = $page->data['children'] ?? [];
        /** @var string $index */
        $index = $page->data['index'] ?? '0';
        /** @var string $siblingsCount */
        $siblingsCount = $page->data['siblings_count'] ?? '0';
        /** @var bool|string $bypassCache */
        $bypassCache = $page->data['bypass_cache'] ?? false;

        $splitUrl = explode('/', $page->urlPath);
        $slugValue = $splitUrl[count($splitUrl) - 1];
        // Index page should have empty slug, otherwise strip number prefix
        if ($slugValue === 'index') {
            $page->slug = '';
        } else {
            // Strip number prefix (e.g., "01." from "01.Pour-une-pluie-desordonnee")
            $page->slug = preg_replace('/^\d+\./', '', $slugValue);
        }

        $page->page_name = ucfirst((string) preg_replace_callback(
            '/[-_](.)/',
            fn (array $matches): string => ' ' . strtoupper($matches[1]),
            $page->slug
        ));

        $page->root_path = $this->helpers->relativeRootPath();
        $page->thumb = $this->getThumbnail($page->filePath);
        $page->current_year = date('Y');

        $page->stacey_version = Stacey::VERSION;
        $page->domain_name = $this->serverParams['HTTP_HOST'] ?? 'localhost';
        $page->base_url = ($this->serverParams['HTTP_HOST'] ?? 'localhost') . str_replace('/index.php', '', $this->serverParams['PHP_SELF'] ?? '');
        $page->site_updated = date('c', $this->helpers->siteLastModified());
        $page->updated = date('c', $this->helpers->lastModified($page->filePath));
        $page->id = 'p' . substr(md5(($this->serverParams['HTTP_HOST'] ?? 'localhost') . $page->permalink), 0, 6);

        $page->siblings_count = (string) count($siblingsAndSelf);
        $page->children_count = (string) count($children);
        $page->index = $this->getIndex($siblingsAndSelf, $page->filePath);

        // Use urlPath (clean URL format) for is_current comparison against the stored current route
        $page->is_current = $this->isCurrent($page->urlPath);
        $page->is_last = $index === $siblingsCount;
        $page->is_first = $index === '1';

        $page->data['template_name'] = $page->templateName;
        $page->bypass_cache = isset($bypassCache) && $bypassCache !== 'false' ? $bypassCache : false;
    }

    /**
     * Create page collections (siblings, children, etc).
     */
    private function createCollections(object $page): void
    {
        $page->root = $this->helpers->listFiles($this->config->contentFolder, '/^\d+?\./', true);
        $page->query = $_GET;

        $parentPath = $this->getParent($page->filePath);
        // Convert parent paths to Page objects
        // Ensure AssetFactory has the correct config
        \Stacey\Core\Asset\AssetFactory::setConfig($this->config);
        $page->parent = array_map(
            fn($path) => is_string($path) ? \Stacey\Core\Asset\AssetFactory::get($path) : [],
            $parentPath
        );
        $page->parents = $this->getParents($page->filePath);

        $parentPath = empty($parentPath[0]) ? $this->config->contentFolder : $parentPath[0];
        $splitUrl = explode('/', $page->urlPath);

        $page->siblings = $this->helpers->listFiles($parentPath, '/^\d+?\.(?!' . $splitUrl[count($splitUrl) - 1] . ')/', true);
        $page->siblings_and_self = $this->helpers->listFiles($parentPath, '/^\d+?\./', true);

        $index = $this->getIndex($page->data['siblings_and_self'], $page->filePath);
        $page->previous_siblings = array_slice($page->data['siblings_and_self'], 0, (int) $index - 1, true);
        $page->next_siblings = array_slice($page->data['siblings_and_self'], (int) $index, count($page->data['siblings_and_self']), true);

        $neighboringSiblings = $this->extractClosestSiblings($page->data['siblings_and_self'], $page->filePath);
        $page->previous_sibling = [$neighboringSiblings[0]];
        $page->next_sibling = [$neighboringSiblings[1]];

        $page->children = $this->helpers->listFiles($page->filePath, '/^\d+?\./', true);
    }

    /**
     * Create asset collections.
     */
    private function createAssetCollections(object $page): void
    {
        $page->files = $this->helpers->listFiles($page->filePath, '/(?<!thumb|_lge|_sml)\.(?!yml)([\w\d]+?)$/i', false);
        $page->images = $this->helpers->listFiles($page->filePath, '/(?<!thumb|_lge|_sml)\.(gif|jpg|png|jpeg)$/i', false);
        $page->numbered_images = $this->helpers->listFiles($page->filePath, '/^\d+[^\/]*(?<!thumb|_lge|_sml)\.(gif|jpg|png|jpeg)$/i', false);
        $page->video = $this->helpers->listFiles($page->filePath, '/\.(mov|mp4|m4v|webm|ogv)$/i', false);

        $assets = $this->getFileTypes($page->filePath);
        foreach ($assets as $assetType => $assetFiles) {
            $page->$assetType = $assetFiles;
        }
    }

    /**
     * Get shared data from _shared.yml.
     *
     * @return array<string, mixed>
     */
    public function getSharedData(): array
    {
        if (self::$shared !== null) {
            return self::$shared;
        }

        $sharedFile = $this->config->contentFolder . '/_shared.yml';
        $sharedTxt = $this->config->contentFolder . '/_shared.txt';

        $sharedFilePath = file_exists($sharedFile) ? $sharedFile : $sharedTxt;

        self::$shared = file_exists($sharedFilePath) ? Yaml::parseFile($sharedFilePath) ?? [] : [];

        return self::$shared;
    }

    /**
     * Preparse text content.
     */
    public function preparseText(string $text): string
    {
        // Handle ---- syntax (4+ dashes)
        $result = preg_replace_callback('/:\s*(\n)?\-{4,}([\S\s]*?)\-{4,}/', function (array $match): string {
            $replaced = preg_replace("/\n/", "\n  ", $match[2]);

            return ": |\n  " . ($replaced ?? $match[2]);
        }, $text);

        // Handle +++ syntax (3+ plus signs)
        $result = preg_replace_callback('/:\s*(\n)?\+{3,}([\S\s]*?)\+{3,}/', function (array $match): string {
            $replaced = preg_replace("/\n/", "\n  ", $match[2]);

            return ": |\n  " . ($replaced ?? $match[2]);
        }, $result ?? $text);

        return $result ?? $text;
    }

    /**
     * Create variables from text file.
     */
    private function createTextfileVars(object $page, bool $content = false): void
    {
        if ($content) {
            // Content passed directly
            return;
        }

        $contentFile = sprintf('%s/%s', $page->filePath, $page->templateName);
        $contentFilePath = file_exists($contentFile . '.yml') ? $contentFile . '.yml' : $contentFile . '.txt';

        if (! file_exists($contentFilePath)) {
            return;
        }

        $text = file_get_contents($contentFilePath);
        if ($text === false) {
            return;
        }
        $text = $this->preparseText($text);
        $vars = Yaml::parse($text) ?? [];

        $vars = array_merge($this->getSharedData(), $vars);

        if ($vars === []) {
            return;
        }

        $currentTemplate = $GLOBALS['current_page_template_file'] ?? $page->templateFile;
        $markdownCompatible = (bool) preg_match('/\.(xml|html?|rss|rdf|atom|js|json)$/', (string) $currentTemplate);
        $relativePath = preg_replace('/^\.\//', $this->helpers->relativeRootPath(), $page->filePath);

        $vars = $this->processVars($vars, $markdownCompatible, $relativePath);

        foreach ($vars as $key => $value) {
            $page->$key = $value;
        }
    }

    /**
     * Parse and process variables.
     *
     * @param array<string, mixed> $vars
     * @return array<string, mixed>
     */
    public function processVars(array $vars, bool $markdownCompatible, string $relativePath): array
    {
        foreach ($vars as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $absolutePath = $this->helpers->relativePathToAbsoluteUrl($relativePath);
            $replaced = preg_replace('/{{\s*path\s*}}/', $absolutePath . '/', $value);
            $value = $replaced !== null ? trim($replaced) : $value;

            if ($markdownCompatible && str_contains($value, "\n")) {
                $value = MarkdownParser::transform($value);
            }

            $vars[$key] = $value;
        }

        return $vars;
    }

    /**
     * Convert HTML to XHTML.
     */
    public function htmlToXhtml(string $value): string
    {
        $value = $this->helpers->translateNamedEntities($value);
        $result = preg_replace('/<(br|hr|input|img)(.*?)\s?\/?>/', '<\\1\\2 />', $value);

        return $result ?? $value;
    }

    /**
     * Clean value for JSON output.
     */
    public function cleanJson(string $value): string
    {
        $result = preg_replace('/"/', '"', $value);

        return $result ?? $value;
    }

    /**
     * Post-process page data based on template type.
     */
    private function postProcess(object $page): void
    {
        $currentTemplate = $GLOBALS['current_page_template_file'] ?? '';

        if (preg_match('/\.(xml|rss|rdf|atom)$/', (string) $currentTemplate)) {
            foreach ($page->data as $key => $value) {
                if (is_string($value)) {
                    $page->data[$key] = $this->htmlToXhtml($value);
                }
            }
        } elseif (preg_match('/\.(js|json)$/', (string) $currentTemplate)) {
            foreach ($page->data as $key => $value) {
                if (is_string($value)) {
                    $page->data[$key] = $this->cleanJson($value);
                }
            }
        }
    }

    // Static wrappers for backward compatibility

    /**
     * @deprecated Use instance method generate() instead
     */
    public static function create(object $page, bool $content, Config $config): void
    {
        $helpers = new Helpers($config);
        $pageData = new self($config, $helpers);
        $pageData->generate($page, $content);
    }

    /**
     * @deprecated Use instance method processVars() instead
     * @param array<string, mixed> $vars
     * @return array<string, mixed>
     */
    public static function parseVars(array $vars, bool $markdownCompatible, string $relativePath, Config $config): array
    {
        $helpers = new Helpers($config);
        $pageData = new self($config, $helpers);

        return $pageData->processVars($vars, $markdownCompatible, $relativePath);
    }
}
