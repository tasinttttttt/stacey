<?php

declare(strict_types=1);

namespace Stacey\Core\Asset;

use Stacey\Core\Helpers;

/**
 * Video asset class.
 */
final class Video extends Asset
{
    /** @var array<int, string> */
    public static array $identifiers = ['mov', 'mp4', 'm4v', 'swf'];

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
        return 'video';
    }

    /**
     * Set extended data for videos.
     */
    private function setExtendedData(string $filePath): void
    {
        if (preg_match('/(\d+?)x(\d+?)\./', $this->data['file_name'] ?? '', $matches)) {
            $this->data['width'] = $matches[1];
            $this->data['height'] = $matches[2];
        } else {
            $this->data['width'] = '';
            $this->data['height'] = '';
        }
    }
}
