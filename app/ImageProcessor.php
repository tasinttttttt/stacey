<?php

declare(strict_types=1);

namespace Stacey\Core;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;

/**
 * Image processing service using Intervention Image library.
 *
 * Replaces the old SLIR image processor with a modern, type-safe implementation.
 */
final readonly class ImageProcessor
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Resize an image to specific dimensions.
     *
     * @param string $sourcePath Path to source image
     * @param string $targetPath Path for output image
     * @param int|null $width Target width (null for auto)
     * @param int|null $height Target height (null for auto)
     * @param bool $crop Whether to crop to exact dimensions
     * @throws \InvalidArgumentException If source file doesn't exist
     * @throws \RuntimeException If processing fails
     */
    public function resize(
        string $sourcePath,
        string $targetPath,
        ?int $width = null,
        ?int $height = null,
        bool $crop = false,
    ): void {
        if (! file_exists($sourcePath)) {
            throw new \InvalidArgumentException("Source file not found: {$sourcePath}");
        }

        try {
            $image = $this->manager->read($sourcePath);

            if ($crop && $width !== null && $height !== null) {
                $image->cover($width, $height);
            } else {
                $image->scale($width, $height);
            }

            $image->save($targetPath);
        } catch (DecoderException $e) {
            throw new \RuntimeException("Failed to decode image: {$sourcePath}", 0, $e);
        } catch (\Exception $e) {
            throw new \RuntimeException("Image processing failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get image dimensions.
     *
     * @param string $path Path to image
     * @return array{width: int, height: int}
     * @throws \InvalidArgumentException If file not found
     */
    public function getDimensions(string $path): array
    {
        if (! file_exists($path)) {
            throw new \InvalidArgumentException("Image not found: {$path}");
        }

        try {
            $image = $this->manager->read($path);

            return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to read image dimensions: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Create a thumbnail with smart cropping.
     *
     * @param string $sourcePath Source image path
     * @param string $targetPath Output thumbnail path
     * @param int $width Thumbnail width
     * @param int $height Thumbnail height
     * @throws \RuntimeException If processing fails
     */
    public function createThumbnail(
        string $sourcePath,
        string $targetPath,
        int $width,
        int $height,
    ): void {
        $this->resize($sourcePath, $targetPath, $width, $height, true);
    }
}
