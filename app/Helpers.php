<?php

declare(strict_types=1);

namespace Stacey\Core;

use Stacey\Core\Asset\AssetFactory;

/**
 * Utility helper class.
 *
 * Provides file system operations, URL parsing, and content manipulation.
 */
final class Helpers
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private static ?array $fileCache = null;

    /**
     * Clear the file cache. Useful for testing.
     */
    public static function clearFileCache(): void
    {
        self::$fileCache = null;
    }

    /** @var array<string, mixed> */
    private array $serverParams;

    public function __construct(
        private readonly Config $config,
        array $serverParams = [],
    ) {
        $this->serverParams = $serverParams ?: $_SERVER;
    }

    /**
     * Recursive glob function.
     *
     * @return array<int, string>
     */
    public function rglob(string $pattern, int $flags = 0, string $path = ''): array
    {
        if ($path === '' && ($dir = dirname($pattern)) !== '.') {
            if ($dir === '\\' || $dir === '/') {
                $dir = '';
            }

            return $this->rglob(basename($pattern), $flags, $dir . '/');
        }

        /** @var array<int, string>|false $paths */
        $paths = glob($path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        /** @var array<int, string>|false $files */
        $files = glob($path . $pattern, $flags);

        if (! is_array($paths) || ! is_array($files)) {
            return [];
        }

        foreach ($paths as $p) {
            $files = array_merge($files, $this->rglob($pattern, $flags, $p . '/'));
        }

        return $files;
    }

    /**
     * Convert file path to URL.
     */
    public function filePathToUrl(string $filePath): string
    {
        $url = preg_replace(['/\d+?\./', '/.*content\//'], '', $filePath);

        return $url ?: 'index';
    }

    /**
     * Static wrapper for backward compatibility.
     *
     * @deprecated Use instance method filePathToUrl() instead
     */
    public static function file_path_to_url(string $filePath, Config $config): string
    {
        $helpers = new self($config);

        return $helpers->filePathToUrl($filePath);
    }

    /**
     * Convert URL to file path.
     */
    public function urlToFilePath(string $url): ?string
    {
        $url = $url === '' || $url === '0' ? 'index' : $url;
        $filePath = $this->config->contentFolder;
        $urlParts = explode('/', $url);

        foreach ($urlParts as $u) {
            if (str_starts_with($u, '_')) {
                continue;
            }

            $pattern = '/^(\d+?\.)?' . preg_quote($u, '/') . '$/';
            $matches = array_keys($this->listFiles($filePath, $pattern, true));

            if ($matches === []) {
                return null;
            }

            $filePath .= '/' . $matches[0];
        }

        return $filePath;
    }

    /**
     * Static wrapper for backward compatibility.
     *
     * @deprecated Use instance method urlToFilePath() instead
     */
    public static function url_to_file_path(string $url, Config $config): ?string
    {
        $helpers = new self($config);

        return $helpers->urlToFilePath($url);
    }

    /**
     * Convert relative path to absolute URL.
     */
    public function relativePathToAbsoluteUrl(string $relativePath): string
    {
        $scheme = ! empty($this->serverParams['HTTPS']) && $this->serverParams['HTTPS'] !== 'off'
            ? 'https://'
            : 'http://';
        $host = $this->serverParams['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(dirname($this->serverParams['SCRIPT_NAME'] ?? ''), '/\\');

        $relativePath = preg_replace(['/^\/content/', '/^(\.+\/)+/'], ['', ''], $relativePath);

        if ($relativePath === null) {
            $relativePath = '';
        }

        return $scheme . $host . $base . '/' . ltrim($relativePath, '/');
    }

    /**
     * Check if URL is external.
     */
    public function isExternalUrl(string $url, ?string $currentDomain = null): bool
    {
        if ($currentDomain === null) {
            $currentDomain = $this->serverParams['HTTP_HOST'] ?? '';
        }

        if (! preg_match('~^(?:https?:)?//~i', $url)) {
            return false;
        }

        $parsed = parse_url($url);
        $urlDomain = $parsed['host'] ?? '';

        return strcasecmp($urlDomain, (string) $currentDomain) !== 0;
    }

    /**
     * Check if directory has children.
     */
    public function hasChildren(string $dir): bool
    {
        $innerFolders = $this->listFiles($dir, '/.*/', true);

        return $innerFolders !== [];
    }

    /**
     * Get or build file cache.
     *
     * @return array<string, array<int, array<string, mixed>>>|array<int, array<string, mixed>>
     */
    public function fileCache(?string $dir = null): array
    {
        if (self::$fileCache === null) {
            self::$fileCache = [];
            $this->buildFileCache($this->config->appFolder);
            $this->buildFileCache($this->config->contentFolder);
            $this->buildFileCache($this->config->templatesFolder);
        }

        if ($dir !== null && ! isset(self::$fileCache[$dir])) {
            return [];
        }

        return $dir !== null ? self::$fileCache[$dir] : self::$fileCache;
    }

    /**
     * Build file cache for a directory.
     */
    private function buildFileCache(string $dir): void
    {
        /** @var array<int, string>|false $files */
        $files = glob($dir . '/*');
        $files = is_array($files) ? $files : [];

        foreach ($files as $path) {
            $file = basename($path);
            if (str_starts_with($file, '.')) {
                continue;
            }
            if ($file === '_cache') {
                continue;
            }

            if (is_dir($path)) {
                $this->buildFileCache($path);
            }

            if (is_readable($path)) {
                self::$fileCache[$dir][] = [
                    'path' => $path,
                    'file_name' => $file,
                    'is_folder' => is_dir($path) ? 1 : 0,
                    'mtime' => filemtime($path),
                ];
            }
        }
    }

    /**
     * List files matching a pattern.
     *
     * @return array<string, string>
     */
    public function listFiles(string $dir, string $regex, bool $foldersOnly = false): array
    {
        $files = [];
        $cache = $this->fileCache($dir);

        foreach ($cache as $file) {
            if (! isset($file['file_name'])) {
                continue;
            }

            if (! preg_match($regex, (string) $file['file_name'])) {
                continue;
            }

            if ($foldersOnly && empty($file['is_folder'])) {
                continue;
            }

            $files[(string) $file['file_name']] = (string) $file['path'];
        }

        natcasesort($files);

        return $files;
    }

    /**
     * Static wrapper for backward compatibility.
     *
     * @deprecated Use instance method listFiles() instead
     * @return array<string, string>
     */
    public static function list_files(string $dir, string $regex, bool $foldersOnly = false, Config $config): array
    {
        $helpers = new self($config);

        return $helpers->listFiles($dir, $regex, $foldersOnly);
    }

    /**
     * Parse mod_rewrite style URLs.
     */
    public function modrewriteParse(string $url): string
    {
        $htaccessPath = $this->config->rootFolder . '.htaccess';

        if (! file_exists($htaccessPath) && str_ends_with($url, '/')) {
            $url = '?/' . $url;
        }

        $result = preg_replace('/^(\?\/)?index\/$/', '', $url);

        return $result ?? '';
    }

    /**
     * Static wrapper for backward compatibility.
     *
     * @deprecated Use instance method modrewriteParse() instead
     */
    public static function modrewrite_parse(string $url, Config $config): string
    {
        $helpers = new self($config);

        return $helpers->modrewriteParse($url);
    }

    /**
     * Get relative root path.
     */
    public function relativeRootPath(string $url = ''): string
    {
        $requestUri = $this->serverParams['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        
        // Count depth by splitting on /
        $parts = array_filter(explode('/', trim($path, '/')));
        $depth = count($parts);
        
        // For root or index, return ./ for relative paths
        if ($depth === 0 || $parts[0] === 'index') {
            $result = './' . $this->modrewriteParse($url);
            return $result;
        }
        
        // Build relative path
        $linkPath = '';
        for ($i = 0; $i < $depth; $i++) {
            $linkPath .= '../';
        }
        
        return $linkPath . $this->modrewriteParse($url);
    }

    /**
     * Get last modified time for a directory.
     */
    public function lastModified(string $dir): int
    {
        $lastModified = 0;

        if (! is_dir($dir)) {
            return $lastModified;
        }

        foreach ($this->listFiles($dir, '/.*/', false) as $file) {
            if (! is_dir($file)) {
                $mtime = filemtime($file);
                if ($mtime > $lastModified) {
                    $lastModified = $mtime;
                }
            }
        }

        return $lastModified;
    }

    /**
     * Get site-wide last modified time.
     */
    public function siteLastModified(?string $dir = null): int
    {
        $dir ??= $this->config->contentFolder;
        $lastUpdated = 0;

        foreach ($this->listFiles($dir, '/.*/', false) as $file) {
            $mtime = filemtime($file);
            if ($mtime > $lastUpdated) {
                $lastUpdated = $mtime;
            }

            if (is_dir($file)) {
                $childUpdated = $this->siteLastModified($file);
                if ($childUpdated > $lastUpdated) {
                    $lastUpdated = $childUpdated;
                }
            }
        }

        return $lastUpdated;
    }

    /**
     * Translate named HTML entities to numbered entities.
     */
    public function translateNamedEntities(string $string): string
    {
        static $mapping = [
            '&' => '&#38;', '&apos;' => '&#39;', '&minus;' => '&#45;',
            '&circ;' => '&#94;', '&tilde;' => '&#126;', '&Scaron;' => '&#138;',
            '&lsaquo;' => '&#139;', '&OElig;' => '&#140;', '&lsquo;' => '&#145;',
            '&rsquo;' => '&#146;', '&ldquo;' => '&#147;', '&rdquo;' => '&#148;',
            '&bull;' => '&#149;', '&ndash;' => '&#150;', '&mdash;' => '&#151;',
            '&trade;' => '&#153;', '&scaron;' => '&#154;', '&rsaquo;' => '&#155;',
            '&oelig;' => '&#156;', '&Yuml;' => '&#159;', '&yuml;' => '&#255;',
            '&fnof;' => '&#402;', '&Alpha;' => '&#913;', '&Beta;' => '&#914;',
            '&Gamma;' => '&#915;', '&Delta;' => '&#916;', '&Epsilon;' => '&#917;',
            '&Zeta;' => '&#918;', '&Eta;' => '&#919;', '&Theta;' => '&#920;',
            '&Iota;' => '&#921;', '&Kappa;' => '&#922;', '&Lambda;' => '&#923;',
            '&Mu;' => '&#924;', '&Nu;' => '&#925;', '&Xi;' => '&#926;',
            '&Omicron;' => '&#927;', '&Pi;' => '&#928;', '&Rho;' => '&#929;',
            '&Sigma;' => '&#931;', '&Tau;' => '&#932;', '&Upsilon;' => '&#933;',
            '&Phi;' => '&#934;', '&Chi;' => '&#935;', '&Psi;' => '&#936;',
            '&Omega;' => '&#937;', '&alpha;' => '&#945;', '&beta;' => '&#946;',
            '&gamma;' => '&#947;', '&delta;' => '&#948;', '&epsilon;' => '&#949;',
            '&zeta;' => '&#950;', '&eta;' => '&#951;', '&theta;' => '&#952;',
            '&iota;' => '&#953;', '&kappa;' => '&#954;', '&lambda;' => '&#955;',
            '&mu;' => '&#956;', '&nu;' => '&#957;', '&xi;' => '&#958;',
            '&omicron;' => '&#959;', '&pi;' => '&#960;', '&rho;' => '&#961;',
            '&sigmaf;' => '&#962;', '&sigma;' => '&#963;', '&tau;' => '&#964;',
            '&upsilon;' => '&#965;', '&phi;' => '&#966;', '&chi;' => '&#967;',
            '&psi;' => '&#968;', '&omega;' => '&#969;', '&thetasym;' => '&#977;',
            '&upsih;' => '&#978;', '&piv;' => '&#982;', '&ensp;' => '&#8194;',
            '&emsp;' => '&#8195;', '&thinsp;' => '&#8201;', '&zwnj;' => '&#8204;',
            '&zwj;' => '&#8205;', '&lrm;' => '&#8206;', '&rlm;' => '&#8207;',
        ];

        foreach (get_html_translation_table(HTML_ENTITIES, ENT_QUOTES) as $char => $entity) {
            $mapping[$entity] = '&#' . ord($char) . ';';
        }

        return str_replace(array_keys($mapping), $mapping, $string);
    }

    /**
     * Convert paths to asset objects.
     *
     * @param array<int, string>|null $paths
     * @return array<int, mixed>
     */
    public function toAssets(?array $paths): array
    {
        if (! is_array($paths)) {
            return [];
        }

        $result = array_filter(
            array_map(
                fn ($path) => is_string($path) ? AssetFactory::get($path) : [],
                $paths
            )
        );

        // Recursively convert children of each result
        foreach ($result as $key => $item) {
            if (is_array($item) && isset($item['children']) && is_array($item['children'])) {
                $result[$key]['children'] = $this->toAssets($item['children']);
            }
        }

        return $result;
    }

    /**
     * Static wrapper for backward compatibility.
     *
     * @deprecated Use instance method toAssets() instead
     * @param array<int, string>|null $paths
     * @return array<int, mixed>
     */
    public static function to_assets(?array $paths, Config $config): array
    {
        $helpers = new self($config);

        return $helpers->toAssets($paths);
    }

    /**
     * Convert a path to an asset object.
     *
     * @return mixed
     */
    public function toAsset(?string $path): mixed
    {
        if (! is_string($path)) {
            return null;
        }

        return AssetFactory::get($path);
    }
}
