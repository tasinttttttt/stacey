<?php

declare(strict_types=1);

namespace Stacey\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Stacey\Core\Config;
use Stacey\Core\Container;
use Stacey\Core\Helpers;
use Stacey\Core\Stacey;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Stacey\Core\Stacey
 * @covers \Stacey\Core\Cache
 * @covers \Stacey\Core\Asset\Page
 */
final class HomepageConfigTest extends TestCase
{
    private string $contentFolder;

    protected function setUp(): void
    {
        Helpers::clearFileCache();
        $this->clearPageCache();

        $this->contentFolder = TEST_ROOT . '/Fixtures/content';
    }

    private function clearPageCache(): void
    {
        $cacheDir = __DIR__ . '/../../app/_cache/pages';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }

    private function createContainer(string $homepage): Container
    {
        $config = new Config(
            contentFolder: $this->contentFolder,
            templatesFolder: TEST_ROOT . '/Fixtures/templates',
            homepage: $homepage,
        );

        return new Container($config, [
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'off',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);
    }

    public function test_default_homepage_is_index(): void
    {
        $container = $this->createContainer('index');
        $stacey = $container->get(Stacey::class);

        ob_start();
        $stacey->run('/');
        $output = ob_get_clean();

        $this->assertStringContainsString('<title>Home</title>', $output);
    }

    public function test_custom_homepage_renders_when_configured(): void
    {
        $container = $this->createContainer('about');
        $stacey = $container->get(Stacey::class);

        ob_start();
        $stacey->run('/');
        $output = ob_get_clean();

        $this->assertStringContainsString('<h1>About</h1>', $output);
    }

    public function test_custom_homepage_falls_back_to_index_when_not_found(): void
    {
        $container = $this->createContainer('nonexistent-page');
        $stacey = $container->get(Stacey::class);

        ob_start();
        $stacey->run('/');
        $output = ob_get_clean();

        $this->assertStringContainsString('<title>Home</title>', $output);
    }

    public function test_root_url_renders_homepage(): void
    {
        $container = $this->createContainer('about');
        $stacey = $container->get(Stacey::class);

        ob_start();
        $stacey->run('/');
        $output = ob_get_clean();

        $this->assertStringContainsString('<h1>About</h1>', $output);
    }

    public function test_explicit_page_route_still_works(): void
    {
        $container = $this->createContainer('about');
        $stacey = $container->get(Stacey::class);

        $this->clearPageCache();

        ob_start();
        $stacey->run('/test-asset/');
        $output = ob_get_clean();

        $this->assertStringContainsString('Test Asset', $output);
    }

    public function test_homepage_loaded_from_shared_yaml(): void
    {
        $sharedFile = TEST_ROOT . '/Fixtures/content/_shared-with-homepage.yml';
        $sharedData = Yaml::parseFile($sharedFile);

        $homepage = $sharedData['homepage'] ?? 'index';

        $config = new Config(
            contentFolder: $this->contentFolder,
            templatesFolder: TEST_ROOT . '/Fixtures/templates',
            homepage: $homepage,
        );

        $container = new Container($config, [
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'off',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);

        $stacey = $container->get(Stacey::class);

        ob_start();
        $stacey->run('/');
        $output = ob_get_clean();

        $this->assertStringContainsString('<h1>About</h1>', $output);
    }
}
