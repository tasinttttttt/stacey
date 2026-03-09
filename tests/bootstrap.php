<?php

declare(strict_types=1);

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

require_once __DIR__ . '/../vendor/autoload.php';

// Set up error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define test constants
if (! defined('TEST_ROOT')) {
    define('TEST_ROOT', __DIR__);
}

if (! defined('CONTENT_ROOT')) {
    define('CONTENT_ROOT', __DIR__ . '/Fixtures/content');
}
