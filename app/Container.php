<?php

declare(strict_types=1);

namespace Stacey\Core;

use Stacey\Core\Asset\AssetFactory;
use Stacey\Core\Parser\TemplateParser;
use Stacey\Extension\Config as LegacyConfig;

/**
 * Simple Dependency Injection Container.
 *
 * Provides lazy initialization of services and manages dependencies.
 */
final class Container
{
    /** @var array<string, object> */
    private array $instances = [];

    public function __construct(
        private readonly Config $config,
        private readonly array $serverParams = [],
    ) {
        // Initialize legacy config for backward compatibility during migration
        $this->initializeLegacyConfig();
    }

    /**
     * Get a service instance.
     *
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    public function get(string $className): object
    {
        if (! isset($this->instances[$className])) {
            $this->instances[$className] = $this->create($className);
        }

        /** @var T */
        return $this->instances[$className];
    }

    /**
     * Create a new instance of a class with dependencies.
     *
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    private function create(string $className): object
    {
        $instance = match ($className) {
            Stacey::class => new Stacey(
                $this->config,
                $this->get(Helpers::class),
                $this->get(Cache::class),
            ),
            Helpers::class => new Helpers($this->config),
            Cache::class => new Cache($this->config, $this->get(Helpers::class)),
            PageData::class => new PageData($this->config, $this->get(Helpers::class), $this->serverParams),
            AssetFactory::class => new AssetFactory(),
            TemplateParser::class => new TemplateParser($this->config),
            ImageProcessor::class => new ImageProcessor(),
            default => new $className(),
        };

        /** @var T $instance */
        return $instance;
    }

    /**
     * Initialize legacy static config for backward compatibility.
     * This will be removed once all code is migrated.
     */
    private function initializeLegacyConfig(): void
    {
        LegacyConfig::$root_folder = $this->config->rootFolder;
        LegacyConfig::$app_folder = $this->config->appFolder;
        LegacyConfig::$content_folder = $this->config->contentFolder;
        LegacyConfig::$templates_folder = $this->config->templatesFolder;
        LegacyConfig::$cache_folder = $this->config->cacheFolder;
        LegacyConfig::$extensions_folder = $this->config->extensionsFolder;
        LegacyConfig::$md_gfm_style_linebreaks = $this->config->mdGfmStyleLinebreaks;
    }
}
