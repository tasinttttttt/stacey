<?php

declare(strict_types=1);

namespace Stacey\Core\Parser;

use Stacey\Core\Parser\markdown\MarkdownExtraParser;

/**
 * Markdown parser wrapper.
 *
 * Provides a clean interface to PHP Markdown Extra.
 */
final class MarkdownParser
{
    private static ?MarkdownExtraParser $parser = null;

    /**
     * Transform Markdown text to HTML.
     */
    public static function transform(string $text): string
    {
        if (! self::$parser instanceof \Stacey\Core\Parser\markdown\MarkdownExtraParser) {
            self::$parser = new MarkdownExtraParser();
        }

        return self::$parser->transform($text);
    }

    /**
     * Clear the parser instance (useful for testing).
     */
    public static function clear(): void
    {
        self::$parser = null;
    }
}

// Define constants only if not already defined (for backward compatibility)
if (! defined('MARKDOWN_VERSION')) {
    define('MARKDOWN_VERSION', "1.0.1n");
}

if (! defined('MARKDOWNEXTRA_VERSION')) {
    define('MARKDOWNEXTRA_VERSION', "1.2.4");
}

if (! defined('MARKDOWN_EMPTY_ELEMENT_SUFFIX')) {
    define('MARKDOWN_EMPTY_ELEMENT_SUFFIX', ">");
}

if (! defined('MARKDOWN_TAB_WIDTH')) {
    define('MARKDOWN_TAB_WIDTH', 4);
}

if (! defined('MARKDOWN_PARSER_CLASS')) {
    define('MARKDOWN_PARSER_CLASS', MarkdownExtraParser::class);
}
