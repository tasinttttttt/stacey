<?php

declare(strict_types=1);

namespace Stacey\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Composer plugin for Stacey CMS.
 *
 * Automatically sets up the folder structure when Stacey is installed
 * or updated as a dependency.
 */
final class Plugin implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;

    /**
     * Apply plugin modifications to Composer.
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Remove any hooks from Composer.
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * Prepare the plugin to be uninstalled.
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array<string, string|array{0: string, 1?: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'post-package-install' => 'onPackageInstall',
            'post-package-update' => 'onPackageUpdate',
        ];
    }

    /**
     * Handle package installation event.
     */
    public function onPackageInstall(PackageEvent $event): void
    {
        $package = $event->getOperation()->getPackage();

        // Only run for Stacey package
        if ($package->getName() !== 'tasinttttttt/stacey') {
            return;
        }

        $this->io->write('<info>Stacey CMS detected! Running setup...</info>');
        $this->runSetup(false);
    }

    /**
     * Handle package update event.
     */
    public function onPackageUpdate(PackageEvent $event): void
    {
        $package = $event->getOperation()->getInitialPackage();

        // Only run for Stacey package
        if ($package->getName() !== 'tasinttttttt/stacey') {
            return;
        }

        $this->io->write('<info>Stacey CMS update detected! Checking setup...</info>');
        $this->runSetup(true);
    }

    /**
     * Run the setup process.
     */
    private function runSetup(bool $isUpdate): void
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $projectRoot = dirname($vendorDir);
        $staceyRoot = $vendorDir . '/tasinttttttt/stacey';

        // Create directories
        $directories = ['content', 'templates', 'public', 'app/_cache'];
        foreach ($directories as $dir) {
            $this->createDirectory($projectRoot, $dir);
        }

        // Copy index.php on install (not update)
        if (!$isUpdate) {
            $this->copyIndexFile($projectRoot, $staceyRoot);
        }

        if (!$isUpdate) {
            $this->io->write('<info>Stacey CMS setup complete!</info>');
            $this->io->write('');
            $this->io->write('Next steps:');
            $this->io->write('  1. Create content files in the content/ directory');
            $this->io->write('  2. Create templates in the templates/ directory');
            $this->io->write('  3. Run: php -S localhost:8000 index.php');
            $this->io->write('');
            $this->io->write('See README.md for documentation.');
        }
    }

    /**
     * Create a directory if it doesn't exist.
     */
    private function createDirectory(string $projectRoot, string $dir): void
    {
        $path = $projectRoot . '/' . $dir;

        if (is_dir($path)) {
            $this->io->write("  <comment>Directory already exists: {$dir}</comment>");

            return;
        }

        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            $this->io->writeError("  <error>Failed to create directory: {$path}</error>");

            return;
        }

        $this->io->write("  <info>Created directory: {$dir}</info>");
    }

    /**
     * Copy index.php to project root if it doesn't exist.
     */
    private function copyIndexFile(string $projectRoot, string $staceyRoot): void
    {
        $indexPath = $projectRoot . '/index.php';
        $sourcePath = $staceyRoot . '/index.php';

        if (file_exists($indexPath)) {
            $this->io->write('  <comment>index.php already exists, skipping</comment>');

            return;
        }

        if (!file_exists($sourcePath)) {
            $this->io->writeError("  <error>Source index.php not found: {$sourcePath}</error>");

            return;
        }

        if (!copy($sourcePath, $indexPath)) {
            $this->io->writeError("  <error>Failed to copy index.php to: {$indexPath}</error>");

            return;
        }

        $this->io->write('  <info>Created: index.php</info>');
    }
}
