<?php

declare(strict_types=1);

/**
 * Stacey CMS Entry Point
 *
 * This file serves as the main entry point for the application.
 * It works both when:
 *   - Copied to a project root (standalone installation)
 *   - Running from vendor/tasinttttttt/stacey/ (composer library)
 *
 * Usage:
 *   Production: Configure web server to use this as front controller
 *   Development: php -S localhost:8000 index.php
 */

// Detect project root based on script location
$scriptDir = __DIR__;
$projectRoot = null;
$vendorDir = null;

// Check if running from vendor directory
$vendorPattern = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'tasinttttttt' . DIRECTORY_SEPARATOR . 'stacey';
if (str_contains($scriptDir, $vendorPattern)) {
    // Running from vendor/tasinttttttt/stacey/, project root is 3 levels up
    $projectRoot = dirname($scriptDir, 3);
    $vendorDir = $projectRoot . DIRECTORY_SEPARATOR . 'vendor';
} else {
    // Running from project root
    $projectRoot = $scriptDir;
    $vendorDir = $scriptDir . DIRECTORY_SEPARATOR . 'vendor';
}

// Define Markdown Extra constants before autoloading
// These are required by the MarkdownExtraParser class
if (!defined('MARKDOWN_FN_LINK_TITLE')) {
    define('MARKDOWN_FN_LINK_TITLE', '');
}
if (!defined('MARKDOWN_FN_BACKLINK_TITLE')) {
    define('MARKDOWN_FN_BACKLINK_TITLE', '');
}
if (!defined('MARKDOWN_FN_LINK_CLASS')) {
    define('MARKDOWN_FN_LINK_CLASS', '');
}
if (!defined('MARKDOWN_FN_BACKLINK_CLASS')) {
    define('MARKDOWN_FN_BACKLINK_CLASS', '');
}

// For PHP built-in server: serve static files from public/ and content/ directories
if (PHP_SAPI === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Check for /public/ prefix
    if (str_starts_with($uri, '/public/')) {
        $path = $projectRoot . $uri;
        if (file_exists($path) && is_file($path)) {
            return false; // Serve file directly
        }
    }

    // Check for /content/ prefix (for images, PDFs, etc.)
    if (str_starts_with($uri, '/content/')) {
        $path = $projectRoot . $uri;
        if (file_exists($path) && is_file($path)) {
            return false; // Serve file directly
        }
    }

    // Default public path
    $path = $projectRoot . '/public' . $uri;
    if (file_exists($path) && is_file($path)) {
        return false; // Serve file directly
    }
}

// Bootstrap application
require_once $vendorDir . '/autoload.php';

use Stacey\Core\Config;
use Stacey\Core\Container;
use Stacey\Core\Stacey;

// Initialize configuration
$config = new Config(
    rootFolder: $projectRoot . '/',
    appFolder: $projectRoot . '/app',
    contentFolder: $projectRoot . '/content',
    templatesFolder: $projectRoot . '/templates',
    cacheFolder: $projectRoot . '/app/_cache',
    publicFolder: $projectRoot . '/public',
    extensionsFolder: $projectRoot . '/extension',
);

// Create DI container and run application
$container = new Container($config, $_SERVER);
$app = $container->get(Stacey::class);
$app->run($_SERVER['REQUEST_URI'] ?? '/');
