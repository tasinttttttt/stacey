<?php

declare(strict_types=1);

namespace Stacey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stacey\Composer\Setup;

final class SetupTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/stacey-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        $this->recursiveRemoveDirectory($this->tempDir);
    }

    private function recursiveRemoveDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    $path = $dir . '/' . $object;
                    if (is_dir($path)) {
                        $this->recursiveRemoveDirectory($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public function test_directory_creation_creates_all_required_directories(): void
    {
        // Create a mock event
        $event = $this->createMock(\Composer\Script\Event::class);
        $composer = $this->createMock(\Composer\Composer::class);
        $package = $this->createMock(\Composer\Package\RootPackage::class);
        $config = $this->createMock(\Composer\Config::class);
        $io = $this->createMock(\Composer\IO\IOInterface::class);

        $package->method('getTargetDir')->willReturn($this->tempDir);
        $config->method('get')->with('vendor-dir')->willReturn($this->tempDir . '/vendor');
        $composer->method('getPackage')->willReturn($package);
        $composer->method('getConfig')->willReturn($config);
        $event->method('getComposer')->willReturn($composer);
        $event->method('getIO')->willReturn($io);

        // Create vendor directory and mock index.php
        mkdir($this->tempDir . '/vendor/tasinttttttt/stacey', 0755, true);
        file_put_contents($this->tempDir . '/vendor/tasinttttttt/stacey/index.php', '<?php // test');

        // Run postInstall
        Setup::postInstall($event);

        // Assert directories were created
        $this->assertDirectoryExists($this->tempDir . '/content');
        $this->assertDirectoryExists($this->tempDir . '/templates');
        $this->assertDirectoryExists($this->tempDir . '/public');
        $this->assertDirectoryExists($this->tempDir . '/app/_cache');
    }

    public function test_index_php_is_copied_to_project_root(): void
    {
        // Create a mock event
        $event = $this->createMock(\Composer\Script\Event::class);
        $composer = $this->createMock(\Composer\Composer::class);
        $package = $this->createMock(\Composer\Package\RootPackage::class);
        $config = $this->createMock(\Composer\Config::class);
        $io = $this->createMock(\Composer\IO\IOInterface::class);

        $package->method('getTargetDir')->willReturn($this->tempDir);
        $config->method('get')->with('vendor-dir')->willReturn($this->tempDir . '/vendor');
        $composer->method('getPackage')->willReturn($package);
        $composer->method('getConfig')->willReturn($config);
        $event->method('getComposer')->willReturn($composer);
        $event->method('getIO')->willReturn($io);

        // Create vendor directory and mock index.php
        mkdir($this->tempDir . '/vendor/tasinttttttt/stacey', 0755, true);
        file_put_contents($this->tempDir . '/vendor/tasinttttttt/stacey/index.php', '<?php // test index');

        // Run postInstall
        Setup::postInstall($event);

        // Assert index.php was copied
        $this->assertFileExists($this->tempDir . '/index.php');
        $this->assertStringEqualsFile($this->tempDir . '/index.php', '<?php // test index');
    }

    public function test_existing_index_php_is_not_overwritten(): void
    {
        // Create a mock event
        $event = $this->createMock(\Composer\Script\Event::class);
        $composer = $this->createMock(\Composer\Composer::class);
        $package = $this->createMock(\Composer\Package\RootPackage::class);
        $config = $this->createMock(\Composer\Config::class);
        $io = $this->createMock(\Composer\IO\IOInterface::class);

        $package->method('getTargetDir')->willReturn($this->tempDir);
        $config->method('get')->with('vendor-dir')->willReturn($this->tempDir . '/vendor');
        $composer->method('getPackage')->willReturn($package);
        $composer->method('getConfig')->willReturn($config);
        $event->method('getComposer')->willReturn($composer);
        $event->method('getIO')->willReturn($io);

        // Create existing index.php
        file_put_contents($this->tempDir . '/index.php', '<?php // existing');

        // Create vendor directory and mock index.php
        mkdir($this->tempDir . '/vendor/tasinttttttt/stacey', 0755, true);
        file_put_contents($this->tempDir . '/vendor/tasinttttttt/stacey/index.php', '<?php // new index');

        // Run postInstall
        Setup::postInstall($event);

        // Assert existing index.php was not overwritten
        $this->assertStringEqualsFile($this->tempDir . '/index.php', '<?php // existing');
    }

    public function test_existing_directories_are_not_recreated(): void
    {
        // Create a mock event
        $event = $this->createMock(\Composer\Script\Event::class);
        $composer = $this->createMock(\Composer\Composer::class);
        $package = $this->createMock(\Composer\Package\RootPackage::class);
        $config = $this->createMock(\Composer\Config::class);
        $io = $this->createMock(\Composer\IO\IOInterface::class);

        $package->method('getTargetDir')->willReturn($this->tempDir);
        $config->method('get')->with('vendor-dir')->willReturn($this->tempDir . '/vendor');
        $composer->method('getPackage')->willReturn($package);
        $composer->method('getConfig')->willReturn($config);
        $event->method('getComposer')->willReturn($composer);
        $event->method('getIO')->willReturn($io);

        // Create existing directories with files
        mkdir($this->tempDir . '/content', 0755, true);
        file_put_contents($this->tempDir . '/content/existing.txt', 'test');

        mkdir($this->tempDir . '/vendor/tasinttttttt/stacey', 0755, true);
        file_put_contents($this->tempDir . '/vendor/tasinttttttt/stacey/index.php', '<?php // test');

        // Run postInstall
        Setup::postInstall($event);

        // Assert existing files are still there
        $this->assertFileExists($this->tempDir . '/content/existing.txt');
    }

    public function test_post_update_does_not_overwrite_index_php(): void
    {
        // Create a mock event
        $event = $this->createMock(\Composer\Script\Event::class);
        $composer = $this->createMock(\Composer\Composer::class);
        $package = $this->createMock(\Composer\Package\RootPackage::class);
        $config = $this->createMock(\Composer\Config::class);
        $io = $this->createMock(\Composer\IO\IOInterface::class);

        $package->method('getTargetDir')->willReturn($this->tempDir);
        $config->method('get')->with('vendor-dir')->willReturn($this->tempDir . '/vendor');
        $composer->method('getPackage')->willReturn($package);
        $composer->method('getConfig')->willReturn($config);
        $event->method('getComposer')->willReturn($composer);
        $event->method('getIO')->willReturn($io);

        // Create existing index.php and directories
        file_put_contents($this->tempDir . '/index.php', '<?php // existing index');

        // Run postUpdate
        Setup::postUpdate($event);

        // Assert existing index.php was not overwritten
        $this->assertStringEqualsFile($this->tempDir . '/index.php', '<?php // existing index');
    }
}
