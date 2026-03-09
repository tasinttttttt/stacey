# Coding Agent Guidelines

This document provides essential guidelines for all coding agents working on this Stacey CMS modernization project.

## System Overview

Stacey is a **flat-file content management system** built on PHP 8.3. It uses a filesystem-based content structure with no database required.

### Content Organization

- `./content` holds all pages
- **Number-prefixed items** (`1.projects`, `01.jpg`) are visible in navigation and ordered by number
- **Non-prefixed items** (`feed`, `search`, `project-11`) are hidden from navigation but accessible via URL
- Each folder contains:
  - A YAML file (name determines template: `project.yml` → `project.html`)
  - Media files (images, videos, files) referenced in the YAML
  - `thumb.jpg` for page thumbnails
- Pages can have nested child pages (infinite depth)
- URLs never show number prefixes

### Key Concepts

- **Template Engine**: Twig (templates in `./templates/`)
- **Content Format**: YAML with Markdown support
- **Asset Types**: Image, Video, Html, Page (auto-detected by file extension)
- **Routing**: URL segments match folder names (with optional number prefix)
- **Shared Data**: `_shared.yml` provides global data to all pages

## Core Principles

Temp files MUST be inside the current folder

### 1. Testing is Mandatory
**Every feature MUST be tested.**

- All new functionality requires corresponding unit or integration tests
- Bug fixes must include a test that reproduces the bug
- Refactoring should be done with tests in place first
- Test files follow the naming convention: `ClassNameTest.php`
- Tests must be placed in `tests/Unit/` or `tests/Integration/` as appropriate
- Use PHPUnit 11+ features (attributes, typed properties, etc.)
- Aim for meaningful test coverage, not just line coverage

**Test Template:**
```php
<?php

declare(strict_types=1);

namespace Stacey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stacey\Core\ClassName;

final class ClassNameTest extends TestCase
{
    private ClassName $subject;
    
    protected function setUp(): void
    {
        $this->subject = new ClassName();
    }
    
    public function test_method_name_describes_behavior(): void
    {
        // Arrange
        $input = 'test data';
        
        // Act
        $result = $this->subject->method($input);
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### 2. Local Development Environment

**The project MUST be runnable locally using PHP's built-in server.**

logs must be located inside ./logs folder

#### Setup Command:

```bash
php -S localhost:8000 index.php
```

#### Router Script Requirements:

The entry point `index.php` must:
- Handle all incoming requests
- Support clean URLs (no .php extensions)
- Serve static files directly from `public/` and `content/` directories
- Route dynamic requests to the application
- Work identically in development and production

**Entry Point Structure (index.php):**

```php
<?php

declare(strict_types=1);

// Define Markdown constants
if (! defined('MARKDOWN_FN_LINK_TITLE')) {
    define('MARKDOWN_FN_LINK_TITLE', '');
}

// Serve static files from public/ and content/ directories
if (PHP_SAPI === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Check for /public/ prefix
    if (str_starts_with($uri, '/public/')) {
        $path = __DIR__ . $uri;
        if (file_exists($path) && is_file($path)) {
            return false;
        }
    }
    
    // Check for /content/ prefix (images, PDFs, etc.)
    if (str_starts_with($uri, '/content/')) {
        $path = __DIR__ . $uri;
        if (file_exists($path) && is_file($path)) {
            return false;
        }
    }
    
    // Default public path
    $path = __DIR__ . '/public' . $uri;
    if (file_exists($path) && is_file($path)) {
        return false;
    }
}

// Bootstrap application
require_once __DIR__ . '/vendor/autoload.php';

use Stacey\Core\Config;
use Stacey\Core\Container;
use Stacey\Core\Stacey;

// Initialize LegacyConfig
\Stacey\Extension\Config::$root_folder = __DIR__ . '/';
\Stacey\Extension\Config::$content_folder = __DIR__ . '/content';
\Stacey\Extension\Config::$templates_folder = __DIR__ . '/templates';
\Stacey\Extension\Config::$cache_folder = __DIR__ . '/app/_cache';

$config = new Config(
    rootFolder: __DIR__ . '/',
    appFolder: __DIR__ . '/app',
    contentFolder: __DIR__ . '/content',
    templatesFolder: __DIR__ . '/templates',
    cacheFolder: __DIR__ . '/app/_cache',
    publicFolder: __DIR__ . '/public',
    extensionsFolder: __DIR__ . '/extension',
);

$container = new Container($config, $_SERVER);
$app = $container->get(Stacey::class);
$app->run($_SERVER['REQUEST_URI']);
```

### 3. Issue Tracking

**Major issues and their solutions MUST be documented in `ISSUES.md`.**

This serves as:
- Knowledge base for future developers
- Reference for recurring problems
- Documentation of architectural decisions
- Debugging aid

**When to Add to ISSUES.md:**
- Complex bugs that took significant time to resolve
- Workarounds for library limitations
- Performance optimizations
- Security considerations
- Breaking changes and migration steps

**ISSUES.md Format:**
```markdown
## Issue #[Number]: [Brief Title]

**Date**: YYYY-MM-DD
**Severity**: High/Medium/Low
**Component**: Core/Asset/Parser/etc.

### Problem
Description of the issue encountered.

### Root Cause
Why did this happen?

### Solution
How was it fixed?

### Code Changes
```php
// Relevant code snippet or reference to commit
```

### 4. Changelog

**A dated CHANGELOG.md MUST be maintained and updated with every significant change.**

**When to update CHANGELOG:**
- After completing new features or functionality
- After fixing bugs (document the fix)
- After refactoring significant code
- After adding or updating tests
- After any architectural changes
- Before finishing a work session

**Update immediately after the work is done** - don't leave it for later.

Follow [Keep a Changelog](https://keepachangelog.com/) format with these sections:
- **Added** - New features
- **Changed** - Changes to existing functionality
- **Deprecated** - Soon-to-be removed features
- **Removed** - Removed features
- **Fixed** - Bug fixes
- **Security** - Security improvements

**Format:**
```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- [Description] - YYYY-MM-DD

### Changed
- [Description] - YYYY-MM-DD

### Fixed
- [Description] - YYYY-MM-DD

## [4.0.0] - YYYY-MM-DD

```

### 5. Security Boundaries

**NEVER access folders or files outside the project's directory.**

**Forbidden Operations:**
- Do not read files outside project root
- Do not write files outside project root
- Do not execute system commands accessing external paths
- Do not follow symlinks pointing outside project
- Do not use `../` navigation beyond project root in file operations

**Allowed Operations:**
- Read/write within `./app/`, `./content/`, `./templates/`, `./public/`, `./tests/`, `./vendor/`
- Temporary files in system temp directory
- Cache files in `./app/_cache/`

**Validation Pattern:**
```php
// Always validate paths
$basePath = realpath(__DIR__ . '/../content');
$requestedPath = realpath($basePath . '/' . $userInput);

if ($requestedPath === false || !str_starts_with($requestedPath, $basePath)) {
    throw new InvalidArgumentException('Invalid path: outside allowed directory');
}
```

## Code Standards

### Namespace Standards

```
Stacey\Core\          - Core classes
Stacey\Core\Asset\    - Asset classes
Stacey\Core\Parser\   - Parser classes
Stacey\Extension\     - Extensions
Stacey\Tests\Unit\    - Unit tests
Stacey\Tests\Integration\ - Integration tests
```

### Documentation Standards

- Use PHPDoc for all public methods
- Type hint everything (no mixed types unless necessary)
- Document exceptions thrown
- Use @param, @return, @throws tags

```php
/**
 * Process an image file and create thumbnails.
 *
 * @param string $sourcePath Absolute path to source image
 * @param string $targetDir Directory for output files
 * @param array<int> $sizes Array of width sizes to generate
 * @return array<string> Paths to generated thumbnails
 * @throws InvalidArgumentException If source file doesn't exist
 * @throws RuntimeException If processing fails
 */
public function createThumbnails(
    string $sourcePath,
    string $targetDir,
    array $sizes = [800, 400, 200]
): array {
    // Implementation
}
```

## Workflow

1. **Before starting work:**
   - Check `ISSUES.md` for known issues
   - Run existing tests: `vendor/bin/phpunit`

2. **During development:**
   - Write tests first (TDD approach preferred)
   - Follow PSR-12 coding standards
   - Use PHP 8.3 features where appropriate
   - Keep functions small and focused
   - Document complex logic with comments

3. **Before committing:**
   - Run tests: `vendor/bin/phpunit`
   - Run static analysis: `vendor/bin/phpstan analyse`
   - Run code style check: `vendor/bin/php-cs-fixer fix --dry-run`
   - Update CHANGELOG.md with your changes
   - Add to ISSUES.md if you solved a major problem

4. **After making changes:**
   - Test with: `php -S localhost:8000 index.php`
   - Verify no files are accessed outside project
   - Check that all tests still pass
   - Ensure CHANGELOG.md is updated

## Common Pitfalls to Avoid

1. **Don't use globals** - Use dependency injection
2. **Don't use eval()** - It's removed for a reason
3. **Don't suppress errors with @** - Handle them properly
4. **Don't use extract()** - It's unclear what variables are created
5. **Don't use dynamic variable names** - Use arrays or objects
6. **Don't rely on loose typing** - Always use strict types
7. **Don't forget to validate file paths** - Security first
8. **Don't leave TODO comments** - Either do it or add to ISSUES.md

## Resources

- [PHP 8.3 Release Notes](https://www.php.net/releases/8.3/en.php)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHPUnit Documentation](https://phpunit.readthedocs.io/)
- [Intervention Image v3](https://image.intervention.io/v3)

## Questions?

If you're unsure about:
- Known issues → Check `ISSUES.md`
- Recent changes → Check `CHANGELOG.md`
- Testing patterns → Check existing tests in `tests/`
