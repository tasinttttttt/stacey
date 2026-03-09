<?php

declare(strict_types=1);

/**
 * Stacey CMS Entry Point
 *
 * This file serves as the main entry point for the application.
 * It handles both production requests (via .htaccess) and development server.
 *
 * Usage:
 *   Production: Configure web server to use this as front controller
 *   Development: php -S localhost:8000 index.php
 */

// Define Markdown Extra constants before autoloading
// These are required by the MarkdownExtraParser class
if (! defined('MARKDOWN_FN_LINK_TITLE')) {
    define('MARKDOWN_FN_LINK_TITLE', '');
}
if (! defined('MARKDOWN_FN_BACKLINK_TITLE')) {
    define('MARKDOWN_FN_BACKLINK_TITLE', '');
}
if (! defined('MARKDOWN_FN_LINK_CLASS')) {
    define('MARKDOWN_FN_LINK_CLASS', '');
}
if (! defined('MARKDOWN_FN_BACKLINK_CLASS')) {
    define('MARKDOWN_FN_BACKLINK_CLASS', '');
}

// For PHP built-in server: serve static files from public/ and content/ directories
if (PHP_SAPI === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Check for /public/ prefix
    if (str_starts_with($uri, '/public/')) {
        $path = __DIR__ . $uri;
        if (file_exists($path) && is_file($path)) {
            return false; // Serve file directly
        }
    }
    
    // Check for /content/ prefix (for images, PDFs, etc.)
    if (str_starts_with($uri, '/content/')) {
        $path = __DIR__ . $uri;
        if (file_exists($path) && is_file($path)) {
            return false; // Serve file directly
        }
    }
    
    // Default public path
    $path = __DIR__ . '/public' . $uri;
    if (file_exists($path) && is_file($path)) {
        return false; // Serve file directly
    }
}

// Bootstrap application
require_once __DIR__ . '/vendor/autoload.php';

// Initialize LegacyConfig with absolute paths for compatibility
$staceyRoot = __DIR__ . '/';
\Stacey\Extension\Config::$root_folder = $staceyRoot;
\Stacey\Extension\Config::$app_folder = $staceyRoot . 'app';
\Stacey\Extension\Config::$content_folder = $staceyRoot . 'content';
\Stacey\Extension\Config::$templates_folder = $staceyRoot . 'templates';
\Stacey\Extension\Config::$cache_folder = $staceyRoot . 'app/_cache';
\Stacey\Extension\Config::$extensions_folder = $staceyRoot . 'extension';

use Stacey\Core\Config;
use Stacey\Core\Container;
use Stacey\Core\Stacey;

// Initialize configuration
$config = new Config(
    rootFolder: __DIR__ . '/',
    appFolder: __DIR__ . '/app',
    contentFolder: __DIR__ . '/content',
    templatesFolder: __DIR__ . '/templates',
    cacheFolder: __DIR__ . '/app/_cache',
    publicFolder: __DIR__ . '/public',
    extensionsFolder: __DIR__ . '/extension',
);

// Create DI container and run application
$container = new Container($config, $_SERVER);
$app = $container->get(Stacey::class);
$app->run($_SERVER['REQUEST_URI'] ?? '/');
