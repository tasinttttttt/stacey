<?php

declare(strict_types=1);

namespace Stacey\Core\Asset;

use Stacey\Core\Helpers;

/**
 * HTML asset class.
 */
final class Html extends Asset
{
    /** @var array<int, string> */
    public static array $identifiers = ['html', 'htm', 'php'];

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
        return 'html';
    }

    /**
     * Set extended data for HTML files.
     */
    private function setExtendedData(string $filePath): void
    {
        if (! is_readable($filePath)) {
            $this->data['content'] = '';

            return;
        }

        ob_start();
        include $filePath;
        $this->data['content'] = ob_get_contents() ?: '';
        ob_end_clean();
    }
}
