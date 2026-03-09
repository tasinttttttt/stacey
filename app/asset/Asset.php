<?php

declare(strict_types=1);

namespace Stacey\Core\Asset;

use Stacey\Core\Helpers;

/**
 * Base Asset class for all content assets.
 */
abstract class Asset
{
    /** @var array<string, mixed> */
    protected array $data = [];

    public function __construct(
        protected string $filePath,
        protected Helpers $helpers,
    ) {
        $this->setDefaultData();
    }

    /**
     * Get the asset type identifier.
     */
    abstract public static function getType(): string;

    /**
     * Get asset data array.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Construct link path from file path.
     */
    protected function constructLinkPath(string $filePath): string
    {
        $rootPath = $this->helpers->relativeRootPath();

        // Get the project root to strip absolute path
        $projectRoot = getcwd() ?: dirname(__DIR__, 2);
        $relativePath = $filePath;

        // Strip project root if present
        if (str_starts_with($filePath, $projectRoot)) {
            $relativePath = substr($filePath, strlen($projectRoot));
        }

        // Remove leading slash if present
        $relativePath = ltrim($relativePath, '/');

        // Prepend relative root path
        return $rootPath . $relativePath;
    }

    /**
     * Set default data for the asset.
     */
    protected function setDefaultData(): void
    {
        $this->data['url'] = $this->constructLinkPath($this->filePath);

        $splitPath = explode('/', $this->filePath);
        $fileName = array_pop($splitPath);
        $this->data['file_name'] = $fileName;

        $replaced = preg_replace(
            ['/[-_]/', '/\.[\w\d]+?$/', '/^\d+?\./'],
            [' ', '', ''],
            $fileName
        );
        $this->data['name'] = ucfirst($replaced ?? $fileName);

        if (class_exists('finfo') && file_exists($this->filePath)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($this->filePath);
            if ($mimeType !== false) {
                $this->data['mime_type'] = $mimeType;
            }
        }
    }
}
