<?php

declare(strict_types=1);

namespace Stacey\Core\Asset;

use Stacey\Core\Helpers;

/**
 * Image asset with metadata support.
 */
final class Image extends Asset
{
    /** @var array<int, string> */
    public static array $identifiers = ['jpg', 'jpeg', 'gif', 'png'];

    public function __construct(
        string $filePath,
        Helpers $helpers,
    ) {
        parent::__construct($filePath, $helpers);
        $this->setExtendedData($filePath);
    }

    /**
     * Get the asset type.
     */
    #[\Override]
    public static function getType(): string
    {
        return 'image';
    }

    /**
     * Set extended data for images.
     */
    private function setExtendedData(string $filePath): void
    {
        $smallVersionPath = preg_replace('/(\.[\w\d]+?)$/', '_sml$1', $this->data['url'] ?? '');
        $largeVersionPath = preg_replace('/(\.[\w\d]+?)$/', '_lge$1', $this->data['url'] ?? '');

        // Check for small version
        $smallRelativePath = preg_replace('/(\.\.\/)+/', './', $smallVersionPath);
        if ($smallRelativePath !== null && file_exists($smallRelativePath) && ! is_dir($smallRelativePath)) {
            $this->data['small'] = $smallVersionPath;
        }

        // Check for large version
        $largeRelativePath = preg_replace('/(\.\.\/)+/', './', $largeVersionPath);
        if ($largeRelativePath !== null && file_exists($largeRelativePath) && ! is_dir($largeRelativePath)) {
            $this->data['large'] = $largeVersionPath;
        }

        // Get image dimensions
        $imgData = getimagesize($filePath, $info);
        if ($imgData !== false && isset($imgData[3])) {
            preg_match_all('/\d+/', $imgData[3], $dimensions);
            if (isset($dimensions[0][0]) && ($dimensions[0][0] !== '' && $dimensions[0][0] !== '0')) {
                $this->data['width'] = (int) $dimensions[0][0];
            }
            if (isset($dimensions[0][1]) && ($dimensions[0][1] !== '' && $dimensions[0][1] !== '0')) {
                $this->data['height'] = (int) $dimensions[0][1];
            }
        }

        // Extract IPTC data
        if (isset($info['APP13'])) {
            $iptc = iptcparse($info['APP13']);
            if (isset($iptc['2#005'][0])) {
                $this->data['title'] = $iptc['2#005'][0];
            }
            if (isset($iptc['2#120'][0])) {
                $this->data['description'] = $iptc['2#120'][0];
            }
            if (isset($iptc['2#025'][0])) {
                $this->data['keywords'] = $iptc['2#025'][0];
            }
        }
    }
}
