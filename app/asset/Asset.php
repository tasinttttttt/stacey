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
        
        // If filePath starts with root folder, strip it to get relative path
        $relativePath = $filePath;
        // Access root folder through reflection or use a default approach
        // Since we can't easily access Config from helpers, we'll use a different approach
        // Strip any absolute path up to 'content/' to get relative path
        if (str_contains($filePath, 'content/')) {
            $relativePath = substr($filePath, strpos($filePath, 'content/') + 8);
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
