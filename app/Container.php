<?php

declare(strict_types=1);

namespace Stacey\Core;

use Stacey\Core\Asset\AssetFactory;
use Stacey\Core\Parser\TemplateParser;

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
        // Set config for AssetFactory
        AssetFactory::setConfig($config);
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
            Helpers::class => new Helpers($this->config, $this->serverParams),
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
}
