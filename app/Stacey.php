<?php

declare(strict_types=1);

namespace Stacey\Core;

use Stacey\Core\Asset\Page;

/**
 * Main application class for Stacey CMS.
 *
 * Coordinates page rendering, caching, and HTTP response handling.
 */
final class Stacey
{
    public const string VERSION = '4.0.0';

    private string $route = '';

    public function __construct(
        private readonly Config $config,
        private readonly Helpers $helpers,
        private readonly Cache $cache,
        private readonly array $serverParams = [],
        private readonly array $getParams = [],
    ) {
    }

    /**
     * Main entry point to run the application.
     */
    public function run(string $requestUri): void
    {
        $this->applyPhpFixes();

        if ($this->handleRedirects($requestUri)) {
            return;
        }

        $this->route = $this->parseRoute($requestUri);
        $filePath = $this->helpers->urlToFilePath($this->route);

        try {
            if ($filePath === null) {
                throw new \Exception('404');
            }
            $this->createPage($filePath);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Handle URL redirects (trailing slashes, index/app paths).
     */
    private function handleRedirects(string $requestUri): bool
    {
        $uri = $requestUri;

        // Rewrite /index or /app to root
        if (preg_match('/^\/?(index|app)\/?$/', $uri)) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ../');

            return true;
        }

        // Add trailing slash if needed
        if (! str_ends_with($uri, '/') && ! preg_match('/[\.\?\&][^\/]+$/', $uri)) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location:' . $uri . '/');

            return true;
        }

        return false;
    }

    /**
     * Parse and clean the route from request URI.
     */
    private function parseRoute(string $requestUri): string
    {
        $route = $requestUri;

        // Ignore non-URL paths
        if (! str_contains($route, '/')) {
            $route = '';
        }

        // Remove leading/trailing slashes
        $route = trim($route, '/');

        // Strip file extensions
        $route = preg_replace('/\.[\w\d]+$/', '', $route);

        return $route ?: 'index';
    }

    /**
     * Apply PHP compatibility fixes.
     */
    private function applyPhpFixes(): void
    {
        date_default_timezone_set('Australia/Melbourne');
    }

    /**
     * Set appropriate Content-Type header based on template file extension.
     */
    private function setContentType(string $templateFile): void
    {
        if (! preg_match('/\.([\w\d]+)$/', $templateFile, $matches)) {
            header('Content-type: text/html; charset=utf-8');

            return;
        }

        $contentType = match ($matches[1]) {
            'txt' => 'text/plain; charset=utf-8',
            'atom' => 'application/atom+xml; charset=utf-8',
            'rss' => 'application/rss+xml; charset=utf-8',
            'rdf' => 'application/rdf+xml; charset=utf-8',
            'xml' => 'text/xml; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            default => 'text/html; charset=utf-8',
        };

        header('Content-type: ' . $contentType);
    }

    /**
     * Check if ETag matches client cache.
     */
    private function etagExpired(string $hash): bool
    {
        header('Etag: "' . $hash . '"');

        // Safari incorrectly caches 304s as empty pages
        $userAgent = $this->serverParams['HTTP_USER_AGENT'] ?? '';
        if (str_contains((string) $userAgent, 'Safari')) {
            return true;
        }

        // Check for matching ETag
        $ifNoneMatch = $this->serverParams['HTTP_IF_NONE_MATCH'] ?? null;
        if ($ifNoneMatch !== null && stripslashes((string) $ifNoneMatch) === '"' . $hash . '"') {
            header('HTTP/1.0 304 Not Modified');
            header('Content-Length: 0');

            return false;
        }

        return true;
    }

    /**
     * Render a page using the appropriate template.
     */
    private function render(string $filePath, string $templateFile): void
    {
        $cacheKey = $this->cache->generateCacheKey($filePath, $templateFile);
        $hash = $this->cache->generateHash($cacheKey);

        $this->setContentType($templateFile);
        header('Generator: stacey-v' . self::VERSION);

        // Return 304 if ETag matches
        if (! $this->etagExpired($hash)) {
            return;
        }

        // Check if cache needs refresh
        $cacheFile = $this->cache->getCacheFile($hash);
        if (! $this->cache->expired($cacheFile)) {
            echo $this->cache->render($cacheFile);

            return;
        }

        // Render and cache
        $content = $this->cache->create($this->route, $filePath, $templateFile);
        echo $content;
    }

    /**
     * Create and render a page.
     *
     * @throws \Exception If page not found (404)
     */
    private function createPage(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new \Exception('404');
        }

        $templateName = Page::templateName($filePath, $this->config);

        if ($templateName === null || $templateName === '' || $templateName === '0') {
            throw new \Exception('404');
        }

        $templateFile = Page::templateFile($templateName, $this->config);

        if ($templateFile === null) {
            throw new \Exception('404');
        }

        // Set globals for backward compatibility
        $GLOBALS['current_page_file_path'] = $filePath;
        $GLOBALS['current_page_template_file'] = $templateFile;

        $this->render($filePath, $templateFile);
    }

    /**
     * Handle exceptions during page rendering.
     */
    private function handleException(\Throwable $e): void
    {
        if ($e->getMessage() === '404') {
            header('HTTP/1.0 404 Not Found');

            // Try to load custom 404 page
            $notFoundPath = $this->config->contentFolder . '/404';
            if (file_exists($notFoundPath)) {
                $this->route = '404';

                try {
                    $this->createPage($notFoundPath);

                    return;
                } catch (\Exception) {
                    // Fall through to default 404
                }
            }

            // Try static 404.html
            $static404 = $this->config->publicFolder . '/404.html';
            if (file_exists($static404)) {
                echo file_get_contents($static404);

                return;
            }

            // Default 404 message
            echo '<h1>404</h1><h2>Page could not be found.</h2><p>Unfortunately, the page you were looking for does not exist here.</p>';
        } else {
            echo '<h3>' . htmlspecialchars($e->getMessage()) . '</h3>';
        }
    }
}
