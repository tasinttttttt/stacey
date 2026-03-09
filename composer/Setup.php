<?php

declare(strict_types=1);

namespace Stacey\Composer;

use Composer\Script\Event;

/**
 * Composer setup script for Stacey CMS.
 *
 * Handles post-install and post-update tasks to set up the folder structure
 * when the package is installed as a library dependency.
 */
final class Setup
{
    /**
     * Directories to create during installation.
     *
     * @var array<string>
     */
    private const array DIRECTORIES = ['content', 'templates', 'public', 'app/_cache'];

    /**
     * Run post-installation setup.
     */
    public static function postInstall(Event $event): void
    {
        $io = $event->getIO();
        $projectRoot = self::getProjectRoot($event);
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $staceyRoot = $vendorDir . '/tasinttttttt/stacey';

        $io->write('<info>Setting up Stacey CMS...</info>');

        // Create directories
        foreach (self::DIRECTORIES as $dir) {
            self::createDirectory($projectRoot, $dir, $io);
        }

        // Copy index.php if it doesn't exist
        self::copyIndexFile($projectRoot, $staceyRoot, $io);

        $io->write('<info>Stacey CMS setup complete!</info>');
        $io->write('');
        $io->write('Next steps:');
        $io->write('  1. Create content files in the content/ directory');
        $io->write('  2. Create templates in the templates/ directory');
        $io->write('  3. Run: php -S localhost:8000 index.php');
        $io->write('');
        $io->write('See README.md for documentation.');
    }

    /**
     * Run post-update setup (same as install).
     */
    public static function postUpdate(Event $event): void
    {
        // On update, only create directories if they don't exist
        // Don't overwrite index.php
        $io = $event->getIO();
        $projectRoot = self::getProjectRoot($event);

        $io->write('<info>Checking Stacey CMS directories...</info>');

        foreach (self::DIRECTORIES as $dir) {
            self::createDirectory($projectRoot, $dir, $io);
        }
    }

    /**
     * Get the project root directory.
     */
    private static function getProjectRoot(Event $event): string
    {
        $composer = $event->getComposer();
        $targetDir = $composer->getPackage()->getTargetDir();

        if ($targetDir !== null && $targetDir !== '' && $targetDir !== '.') {
            return rtrim($targetDir, '/');
        }

        // Fallback to current working directory
        return getcwd() ?: __DIR__ . '/../../..';
    }

    /**
     * Create a directory if it doesn't exist.
     */
    private static function createDirectory(string $projectRoot, string $dir, $io): void
    {
        $path = $projectRoot . '/' . $dir;

        if (is_dir($path)) {
            $io->write("  <comment>Directory already exists: {$dir}</comment>");

            return;
        }

        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException("Failed to create directory: {$path}");
        }

        $io->write("  <info>Created directory: {$dir}</info>");
    }

    /**
     * Copy index.php to project root if it doesn't exist.
     */
    private static function copyIndexFile(string $projectRoot, string $staceyRoot, $io): void
    {
        $indexPath = $projectRoot . '/index.php';
        $sourcePath = $staceyRoot . '/index.php';

        if (file_exists($indexPath)) {
            $io->write('  <comment>index.php already exists, skipping</comment>');

            return;
        }

        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("Source index.php not found: {$sourcePath}");
        }

        if (!copy($sourcePath, $indexPath)) {
            throw new \RuntimeException("Failed to copy index.php to: {$indexPath}");
        }

        $io->write('  <info>Created: index.php</info>');
    }
}
