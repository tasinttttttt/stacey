<?php

declare(strict_types=1);

namespace Stacey\Core;

/**
 * Modern configuration class using readonly properties.
 *
 * This class replaces the static Config class from the extension namespace.
 * All configuration values are set at construction time and cannot be modified.
 */
final readonly class Config
{
    /**
     * @param string $rootFolder Root directory of the application
     * @param string $appFolder Application directory
     * @param string $contentFolder Content directory
     * @param string $templatesFolder Templates directory
     * @param string $cacheFolder Cache directory
     * @param string $publicFolder Public directory
     * @param string $extensionsFolder Extensions directory
     * @param bool $mdGfmStyleLinebreaks Use GitHub Flavored Markdown line breaks
     * @param string $homepage Homepage page (default: 'index')
     */
    public function __construct(
        public string $rootFolder = './',
        public string $appFolder = './app',
        public string $contentFolder = './content',
        public string $templatesFolder = './templates',
        public string $cacheFolder = './app/_cache',
        public string $publicFolder = './public',
        public string $extensionsFolder = './extension',
        public bool $mdGfmStyleLinebreaks = true,
        public string $homepage = 'index',
    ) {
    }
}
