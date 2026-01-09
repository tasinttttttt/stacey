<?php

namespace Stacey\Core\Parser;

use Stacey\Core\Parser\markdown\MarkdownExtraParser;
#
# Markdown Extra  -  A text-to-HTML conversion tool for web writers
#
# PHP Markdown & Extra
# Copyright (c) 2004-2009 Michel Fortin  
# <http://michelf.com/projects/php-markdown/>
#
# Modified to preserve line breaks, line #675
# <http://stackoverflow.com/questions/2092966/how-to-treat-single-newline-as-real-line-break-in-php-markdown>
#
# Modified to allow user to choose preference (set in extensions/config.php
# file), line #674-#679
# <https://github.com/kolber/stacey/issues/82>
#
# Original Markdown
# Copyright (c) 2004-2006 John Gruber  
# <http://daringfireball.net/projects/markdown/>
#

define('MARKDOWN_VERSION',  "1.0.1n"); # Sat 10 Oct 2009
define('MARKDOWNEXTRA_VERSION',  "1.2.4"); # Sat 10 Oct 2009


#
# Global default settings:
#

# Change to ">" for HTML output
@define('MARKDOWN_EMPTY_ELEMENT_SUFFIX',  ">");

# Define the width of a tab for code blocks.
@define('MARKDOWN_TAB_WIDTH',     4);

# Optional title attribute for footnote links and backlinks.
@define('MARKDOWN_FN_LINK_TITLE',         "");
@define('MARKDOWN_FN_BACKLINK_TITLE',     "");

# Optional class attribute for footnote links and backlinks.
@define('MARKDOWN_FN_LINK_CLASS',         "");
@define('MARKDOWN_FN_BACKLINK_CLASS',     "");

### Standard Function Interface ###

@define('MARKDOWN_PARSER_CLASS',  MarkdownExtraParser::class);

final class MarkdownParser
{
    static $parser;

    public static function transform($text)
    {
        if (!isset($parser)) {
            $parser_class = MARKDOWN_PARSER_CLASS;
            $parser = new $parser_class;
        }

        # Transform text using parser.
        return $parser->transform($text);
    }
}
