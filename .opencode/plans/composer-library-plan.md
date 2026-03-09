# Plan: Make Stacey CMS Installable via Composer

## Overview
Convert Stacey from a project-type to a library-type Composer package that can be installed via `composer require tasinttttttt/stacey`. The installation will set up the necessary folder structure without adding default content.

## Changes Required

### 1. Update composer.json
**File**: `composer.json`

**Changes**:
- Change `name` from `kolber/stacey` to `tasinttttttt/stacey`
- Change `type` from `project` to `library`
- Add `scripts` section for post-install hooks
- Remove `app/` from autoload PSR-4 (will use classmap for non-namespaced files)

**New composer.json structure**:
```json
{
  "name": "tasinttttttt/stacey",
  "type": "library",
  "scripts": {
    "post-install-cmd": "Stacey\\Composer\\Setup::postInstall",
    "post-update-cmd": "Stacey\\Composer\\Setup::postUpdate"
  },
  "autoload": {
    "psr-4": {
      "Stacey\\Core\\": "app/",
      "Stacey\\Core\\Asset\\": "app/asset/",
      "Stacey\\Core\\Parser\\": "app/parsers/",
      "Stacey\\Core\\Lib\\": "app/lib/",
      "Stacey\\Extension\\": "extension/",
      "Stacey\\Composer\\": "composer/"
    }
  }
}
```

### 2. Create Composer Setup Script
**File**: `composer/Setup.php`

**Purpose**: Handle post-install/update tasks:
- Create `content/` directory (empty)
- Create `templates/` directory (empty)
- Create `public/` directory (empty)
- Copy `index.php` to project root (if doesn't exist)

**Implementation**:
```php
<?php
namespace Stacey\Composer;

use Composer\Script\Event;

class Setup {
    public static function postInstall(Event $event) {
        $projectRoot = dirname($event->getComposer()->getPackage()->getTargetDir());
        if (!$projectRoot || $projectRoot === '.') {
            $projectRoot = getcwd();
        }
        
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $staceyRoot = $vendorDir . '/tasinttttttt/stacey';
        
        // Create directories
        foreach (['content', 'templates', 'public'] as $dir) {
            $path = $projectRoot . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
        
        // Copy index.php if it doesn't exist
        $indexPath = $projectRoot . '/index.php';
        if (!file_exists($indexPath)) {
            copy($staceyRoot . '/index.php', $indexPath);
        }
    }
    
    public static function postUpdate(Event $event) {
        // Same as post-install, or could be used for migrations
        self::postInstall($event);
    }
}
```

### 3. Modify index.php for Vendor Context
**File**: `index.php`

**Changes**:
- Detect if running from vendor/ or project root
- Calculate paths relative to detected project root
- Handle autoloader location (vendor/autoload.php vs ../vendor/autoload.php)

**Implementation approach**:
```php
<?php
declare(strict_types=1);

// Detect project root
$scriptDir = __DIR__;
$vendorDir = null;
$projectRoot = null;

// Check if we're in vendor directory
if (strpos($scriptDir, '/vendor/tasinttttttt/stacey') !== false ||
    strpos($scriptDir, '\\vendor\\tasinttttttt\\stacey') !== false) {
    // Running from vendor, go up to find project root
    $vendorDir = dirname($scriptDir, 3); // vendor/tasinttttttt/stacey -> vendor -> project root
    $projectRoot = $vendorDir;
} else {
    // Running from project root
    $projectRoot = $scriptDir;
    $vendorDir = $scriptDir . '/vendor';
}

// Define Markdown Extra constants
if (!defined('MARKDOWN_FN_LINK_TITLE')) {
    define('MARKDOWN_FN_LINK_TITLE', '');
}
// ... other constants

// Serve static files
if (PHP_SAPI === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    if (str_starts_with($uri, '/public/')) {
        $path = $projectRoot . $uri;
        if (file_exists($path) && is_file($path)) {
            return false;
        }
    }
    
    if (str_starts_with($uri, '/content/')) {
        $path = $projectRoot . $uri;
        if (file_exists($path) && is_file($path)) {
            return false;
        }
    }
    
    $path = $projectRoot . '/public' . $uri;
    if (file_exists($path) && is_file($path)) {
        return false;
    }
}

// Bootstrap application
require_once $vendorDir . '/autoload.php';

// Initialize LegacyConfig
\Stacey\Extension\Config::$root_folder = $projectRoot . '/';
\Stacey\Extension\Config::$app_folder = $projectRoot . '/app';
\Stacey\Extension\Config::$content_folder = $projectRoot . '/content';
\Stacey\Extension\Config::$templates_folder = $projectRoot . '/templates';
\Stacey\Extension\Config::$cache_folder = $projectRoot . '/app/_cache';
\Stacey\Extension\Config::$extensions_folder = $projectRoot . '/extension';

use Stacey\Core\Config;
use Stacey\Core\Container;
use Stacey\Core\Stacey;

$config = new Config(
    rootFolder: $projectRoot . '/',
    appFolder: $projectRoot . '/app',
    contentFolder: $projectRoot . '/content',
    templatesFolder: $projectRoot . '/templates',
    cacheFolder: $projectRoot . '/app/_cache',
    publicFolder: $projectRoot . '/public',
    extensionsFolder: $projectRoot . '/extension',
);

$container = new Container($config, $_SERVER);
$app = $container->get(Stacey::class);
$app->run($_SERVER['REQUEST_URI'] ?? '/');
```

### 4. Update .gitignore
**File**: `.gitignore`

**Changes**:
- Keep existing ignores
- The demo content/ folder should be excluded from package (using .gitattributes)

### 5. Create .gitattributes
**File**: `.gitattributes`

**Purpose**: Exclude demo content and development files from the distributed package

**Content**:
```
# Exclude from distribution
content/ export-ignore
app/_cache/ export-ignore
tests/ export-ignore
.gitignore export-ignore
.gitattributes export-ignore
.php-cs-fixer.dist.php export-ignore
phpstan.neon export-ignore
phpunit.xml export-ignore
rector.php export-ignore
plan/ export-ignore
ISSUES.md export-ignore
CHANGELOG.md export-ignore
AGENTS.md export-ignore
composer.lock export-ignore
```

### 6. Update README.md
**File**: `README.md`

**Changes**:
- Update installation instructions for Composer library
- Document folder structure setup
- Explain how to run the CMS after installation

### 7. Update CHANGELOG.md
**File**: `CHANGELOG.md`

**Changes**:
- Add entry for package rename and library conversion

## Implementation Order

1. Create `composer/Setup.php` with setup logic
2. Modify `index.php` for vendor context detection
3. Update `composer.json` with new name, type, and scripts
4. Create `.gitattributes` to exclude demo content
5. Update documentation (README, CHANGELOG)
6. Test installation locally

## Testing Plan

1. Create a test project directory
2. Run `composer init` to create composer.json
3. Add local repository pointing to this project
4. Run `composer require tasinttttttt/stacey`
5. Verify:
   - content/, templates/, public/ directories created
   - index.php copied to project root
   - CMS can be run with `php -S localhost:8000 index.php`
   - No demo content present

## Questions for User

1. Should we include a basic example in the README for the minimum required files (a simple template and content file)?
2. Do you want to keep the `extension/` folder creation or make it optional?
3. Should we include the cache folder creation in the setup or leave that to the app?
