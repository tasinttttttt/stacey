<?php

declare(strict_types=1);

namespace Stacey\Core\Parser;

use Stacey\Core\Config;
use Stacey\Extension\StaceyTwigExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Template parser using Twig engine.
 */
final readonly class TemplateParser
{
    public function __construct(
        private Config $config,
    ) {
    }

    /**
     * Find and validate template file.
     *
     * @throws \RuntimeException If template not found
     */
    private function findTemplate(string $template): string
    {
        if (! file_exists($template)) {
            throw new \RuntimeException("'{$template}' template not found.");
        }

        return preg_replace('/.+\//', '', $template);
    }

    /**
     * Parse template with data.
     *
     * @param array<string, mixed> $data
     * @throws \RuntimeException If template not found or parsing fails
     */
    public function parse(array $data, string $template): string
    {
        $template = $this->findTemplate($template);

        $cachePath = $this->config->cacheFolder . '/templates';
        $cache = is_writable($cachePath) ? $cachePath : false;

        $loader = new FilesystemLoader($this->config->templatesFolder);
        $twig = new Environment($loader, [
            'cache' => $cache,
            'auto_reload' => true,
            'autoescape' => false,
        ]);

        $twig->addExtension(new StaceyTwigExtension());

        return $twig->render($template, ['page' => $data]);
    }

    /**
     * Static wrapper for backward compatibility.
     *
     * @deprecated Use instance method parse() instead
     * @param array<string, mixed> $data
     * @throws \RuntimeException
     */
    public static function render(array $data, string $template): string
    {
        $config = new Config(
            templatesFolder: \Stacey\Extension\Config::$templates_folder,
            cacheFolder: \Stacey\Extension\Config::$cache_folder,
        );
        $parser = new self($config);

        return $parser->parse($data, $template);
    }
}
