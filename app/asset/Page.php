<?php

declare(strict_types=1);

namespace Stacey\Core\Asset;

use Stacey\Core\Config;
use Stacey\Core\Helpers;
use Stacey\Core\Lib\JSMin;
use Stacey\Core\PageData;
use Stacey\Core\Parser\TemplateParser;

/**
 * Page asset representing a content page.
 */
final class Page
{
    public string $urlPath;
    public ?string $filePath;
    public string $templateName;
    public ?string $templateFile = null;
    public string $templateType;

    /** @var array<string, mixed> */
    public array $data = [];

    public function __construct(
        string $url,
        private readonly Config $config,
        private readonly bool $content = false,
    ) {
        $this->filePath = Helpers::url_to_file_path($url, $config);
        $this->urlPath = $url;

        if ($this->filePath === null) {
            throw new \RuntimeException('404');
        }

        $this->templateName = self::templateName($this->filePath, $config) ?? '';
        $this->templateFile = self::templateFile($this->templateName, $config);
        $this->templateType = self::templateType($this->templateFile);

        PageData::create($this, $this->content, $this->config);
    }

    /**
     * Clean JSON output.
     */
    public function cleanJson(string $data): string
    {
        // Strip trailing commas (run twice for nested matches)
        $data = preg_replace('/([}\]"][\s\n]*),([\s\n]*[}\]])/', '$1$2', $data);
        $data = preg_replace('/([}\]"][\s\n]*),([\s\n]*[}\]])/', '$1$2', (string) $data);

        // Remove newlines and minify
        $data = preg_replace('/\n/', '', (string) $data);

        return JSMin::minify($data);
    }

    /**
     * Parse and render the page template.
     */
    public function parseTemplate(): string
    {
        $assets = ['next_sibling', 'previous_sibling', 'images', 'video', 'videos', 'audio', 'root', 'children', 'siblings', 'siblings_and_self'];

        foreach ($assets as $asset) {
            if (isset($this->data[$asset])) {
                $this->data[$asset] = Helpers::to_assets($this->data[$asset], $this->config);
            }
        }

        $data = TemplateParser::render($this->data, $this->templateFile, $this->config);

        // Post-process JSON
        if (strcasecmp($this->templateType, 'json') === 0) {
            return $this->cleanJson($data);
        }

        return $data;
    }

    /**
     * Magic setter for data array.
     */
    public function __set(string $name, mixed $value): void
    {
        $this->data[strtolower($name)] = $value;
    }

    /**
     * Magic getter for data array.
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->data[strtolower($name)] ?? null;
    }

    /**
     * Get template type from file extension.
     */
    public static function templateType(?string $templateFile): string
    {
        if ($templateFile === null) {
            return 'html';
        }

        if (preg_match('/\.([\w\d]+)$/', $templateFile, $matches)) {
            return $matches[1];
        }

        return 'html';
    }

    /**
     * Get template name from directory.
     */
    public static function templateName(string $filePath, Config $config): ?string
    {
        $txts = array_keys(Helpers::list_files($filePath, '/\.(yml|txt)/', $config, false));

        return $txts === [] ? null : preg_replace('/\.(yml|txt)/', '', $txts[0]);
    }

    /**
     * Get template file path.
     */
    public static function templateFile(?string $templateName, Config $config): ?string
    {
        $templatesFolder = $config->templatesFolder;

        if ($templateName === null) {
            return $templatesFolder . '/default.html';
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        preg_match('/(\.[\w\d]+?)$/', (string) $requestUri, $ext);
        $extension = $ext[1] ?? '.*';

        $templateName = preg_replace('/([^.]*\.)?([^.]*)$/', '\\2', $templateName);
        $templateFile = glob($templatesFolder . '/' . $templateName . $extension);

        if ($templateFile === [] || $templateFile === false) {
            $templateFile = glob($templatesFolder . '/' . $templateName . '.*');
        }

        return $templateFile[0] ?? $templatesFolder . '/default.html';
    }
}
