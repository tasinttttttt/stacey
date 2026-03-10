# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **PageData is_current tests** - Comprehensive test suite for `PageData::isCurrent()` method
  - Created `tests/Unit/PageDataIsCurrentTest.php` with 13 test cases
  - Tests root index page detection (`is_current` true when permalink is 'index' and REQUEST_URI is '/')
  - Tests matching permalink detection for regular pages
  - Tests nested permalink handling (e.g., 'projects/my-project')
  - Tests base URL with path segments (e.g., 'localhost/subdir')
  - Documents edge cases: empty permalinks, protocol URLs, trailing slashes
  - **Key findings**:
    - Empty string permalink on root page is considered current (returns true)
    - URLs with protocols (e.g., 'https://example.com') leave '//' in basePath
    - Trailing slashes in REQUEST_URI require exact matching

### Fixed
- **Fixed `is_current` functionality for navigation templates** - The `is_current` property now works correctly when iterating through pages in navigation menus
  - **Root cause**: Child pages created via `AssetFactory` didn't have access to the current request context, so `is_current` was always false for navigation items
  - **Solution**: Store the clean route from Stacey in `$GLOBALS['current_route']` and compare against page URL paths
    - Added `$GLOBALS['current_route']` in `Stacey::createPage()` - stores the parsed route (e.g., "about" or "projects/my-project")
    - Simplified `PageData::isCurrent()` to compare `$GLOBALS['current_route']` against page permalink
    - Removed dependency on unreliable `REQUEST_URI` server parameter
    - Handles trailing slashes automatically (normalized during comparison)
    - Index page matches when route is empty or 'index'
  - **Benefits**:
    - Works with any URL rewriting setup (mod_rewrite, nginx, proxies)
    - Immune to query strings and domain variations
    - Same logic works for main page and child pages in navigation
    - Simpler code - no complex baseUrl/permalink construction
  - **Files changed**: `app/Stacey.php`, `app/PageData.php`
  - **New test suite**: `tests/Integration/IsCurrentTemplateTest.php` with 7 integration tests verifying navigation highlighting works correctly
  - **Test fixtures created**:
    - `tests/Fixtures/templates/home-nav.html`, `about-nav.html`, `projects-nav.html` - Page templates
    - `tests/Fixtures/content/1.home-nav/`, `2.about-nav/`, `3.projects-nav/` - Test content pages

- **Fixed page slug to use folder name instead of YAML** - The `slug` and `page_name` properties now correctly derive from the folder name (without number prefix) rather than YAML data
  - **Root cause**: `page_name` calculation was using `$slug` variable from `$page->data['slug']` (YAML) instead of `$page->slug` (computed from folder name)
  - **Solution**: Changed line 216 in `app/PageData.php` to use `$page->slug` instead of `$slug`
  - **Example**: Folder `1.home-nav/` → slug: `home-nav`, page_name: `Home Nav`
  - **Note**: YAML `slug:` field is now ignored - folder name always takes precedence
  - **Files changed**: `app/PageData.php`

### Added
- Coding agent guidelines in `AGENTS.md` - 2025-03-07
- Issues tracking file `ISSUES.md` - 2025-03-07
- This CHANGELOG.md - 2025-03-07
- **Composer Library Support** - Package now installable via `composer require tasinttttttt/stacey`
  - Changed package name from `kolber/stacey` to `tasinttttttt/stacey`
  - Changed package type from `project` to `library`
  - Created `composer/Setup.php` for automated post-install setup
  - Modified `index.php` to auto-detect vendor vs project root context
  - Added `post-install-cmd` and `post-update-cmd` scripts
  - Created `.gitattributes` to exclude demo content from distribution
  - Added minimal "Hello World" quick start example in README

### Fixed
- Fixed YAML parsing errors in content files (colons in unquoted values)
- Fixed +++ multiline syntax support in preparseText()
- Fixed PageData::create() using empty Config
- Fixed relative root path calculation for nested pages
- Fixed asset URL generation (absolute paths to relative URLs)
- Added content/ folder static file serving for images
- Added content_path field for template image references
- Removed "---" YAML delimiters from content files and README examples
- Fixed test assertion for nonexistent template default behavior
- Updated AGENTS.md with correct entry point (index.php)
- Updated README.md with content editing documentation

### Fixed (Debug Session 2026-03-08)
- **YAML Syntax**: Content files with colons in values now parse correctly
- **PageData.php**: Fixed Config initialization in static wrapper methods
- **PageData.php**: Added `content_path` field (relative to content folder)
- **Asset.php**: Fixed `constructLinkPath()` to handle absolute paths
- **Helpers.php**: Fixed `relativeRootPath()` for home page (returns `./`)
- **index.php**: Added `/content/` static file serving
- **Template**: Fixed inline image URLs using `content_path`
- **Template**: Fixed home page image path

### Changed
- Entry point command: `php -S localhost:8000 index.php` (updated in docs)

### Fixed
- Fixed 404 error when running `php -S localhost:8081 index.php` by:
  - Initializing LegacyConfig with absolute paths in index.php
  - Updated static wrapper methods in Helpers.php to use LegacyConfig paths instead of default relative paths

### Phase 1: Foundation Setup (Completed)
- Updated `composer.json` with PHP 8.3 requirement and modern dependencies
  - Added Intervention Image v3 (GD driver)
  - Updated Symfony YAML to ^7.0
  - Added dev dependencies: PHPUnit 11, PHPStan, PHP-CS-Fixer, Rector
- Created `phpunit.xml` test configuration
- Created `phpstan.neon` static analysis config (level 9)
- Created `rector.php` automated refactoring config
- Created `.php-cs-fixer.dist.php` PSR-12 code style config
- Created `tests/` directory structure with Unit and Integration suites
- Created new `Config` class (instance-based, readonly properties)
- Created `Container` class for dependency injection
- Created `ImageProcessor` class (Intervention Image integration, replaces SLIR)
- Created `public/router.php` for PHP built-in server and production
- Updated `index.php` entry point with modern bootstrap
- Modernized `app/Stacey.php` (readonly class, typed methods, match expressions)
- Modernized `app/Helpers.php` (instance methods, typed parameters, arrow functions)
- Modernized `app/Cache.php` (typed properties, extracted methods)
- Modernized `app/asset/Asset.php` (abstract base with type safety)
- Modernized `app/asset/Page.php` (typed properties, static factory methods)
- Modernized `app/asset/AssetFactory.php` (removed eval(), explicit registration)
- Modernized `app/asset/Image.php` (extended metadata support)
- Modernized `app/asset/Video.php` (modern syntax, type safety)
- Modernized `app/asset/Html.php` (modern syntax, type safety)

### Phase 2: Core Modernization (Completed)
- Modernized `app/parsers/TemplateParser.php` (instance-based, static wrapper for BC)
- Modernized `app/parsers/MarkdownParser.php` (cleaned up constants, static method)
- Modernized `app/BasicAuth.php` (typed properties, instance-based)
- Added backward-compatible static method wrappers in Helpers for migration period
- Created unit tests for Helpers class (11 tests, 18 assertions - all passing)
- Created unit tests for Cache class (hash generation, cache keys)
- All tests passing with PHPUnit 11
- Development server `php -S localhost:8000 public/router.php` functional

### Phase 3: PageData & Quality Tools (Completed)
- Modernized `app/PageData.php` (instance methods, dependency injection)
- Added static wrappers for backward compatibility
- Fixed Container to inject dependencies properly
- Applied PHP-CS-Fixer to 20 files (PSR-12 compliance)
- Verified all tests still pass after code style fixes
- Rector configured for PHP 8.3 upgrades

### Phase 4: Testing & Bug Fixes (Completed) - 2026-03-07
- Created comprehensive unit tests for `AssetFactory` (7 tests)
- Created integration tests for `PageRendering` (5 tests)
- Created integration tests for `AssetHandling` (9 tests)
- **Total tests**: 31 tests, 54 assertions (all passing)
- Fixed property name inconsistencies (camelCase vs snake_case)
- Fixed type issues in `Cache.php` (ob_get_clean() handling)
- Fixed type issues in `Helpers.php` (preg_replace returns)
- Fixed Container.php generic type annotations
- Added `Helpers::clearFileCache()` method for test isolation
- Fixed `AssetFactory::get()` to handle non-string paths gracefully
- Removed deprecated SLIR directory completely
- Added Markdown Extra constants to bootstrap and router
- Fixed bool type cast in `PageData::createTextfileVars()`
- All integration tests passing with real content

### Phase 5: PHPStan & Code Quality Improvements (Completed) - 2026-03-07
- Fixed PHPStan errors in `app/PageData.php` (variable types, null coalescing)
- Fixed PHPStan errors in `app/Stacey.php` (null template file handling)
- Fixed PHPStan errors in `app/asset/Asset.php` (preg_replace null returns)
- Fixed PHPStan errors in `app/asset/AssetFactory.php` (type annotations)
- Fixed PHPStan errors in `app/asset/Image.php` (null path handling)
- Added magic `__get()` method to `Page` class for data array access
- Applied Rector automated refactoring (13 files updated)
  - Converted to readonly classes where appropriate
  - Added typed constants
  - Applied early return patterns
  - Modernized array syntax
- Fixed code style with PHP-CS-Fixer (4 files updated)
- Reverted Rector changes to markdown parsers (external library compatibility)
- Verified all 31 tests still passing after all changes

### Phase 6: Single Entry Point Architecture (Completed) - 2026-03-07
- **Unified entry point**: `index.php` now serves as the single entry point
  - Added static file serving for PHP built-in server (serves files from `public/`)
  - Added Markdown Extra constants at the top of file
  - Maintains all existing bootstrap logic
- **Removed `public/router.php`**: Deleted obsolete router file
  - All routing now handled through `index.php`
  - Cleaner architecture with single entry point
- **Updated `.htaccess`**: Removed obsolete SLIR image parser reference
  - Cleaned up rewrite rules
  - All requests properly route to `index.php`
- **Updated `README.md`**: Changed all references from `public/router.php` to `index.php`
  - Development server command: `php -S localhost:8000 index.php`
  - Production configuration references updated
- **Tested**: All 31 tests still passing, application renders correctly

### Phase 7: Integration Test Suite Expansion - 2026-03-07
- **Added comprehensive rendering tests** to `tests/Integration/PageRenderingTest.php`:
  - `test_server_returns_rendered_index_page()` - Verifies server renders index page with content/index.yml and index.php template
  - `test_page_with_nonexistent_template_defaults_to_default_template()` - Confirms pages fall back to default.html when template not found
  - `test_page_renders_image_assets()` - Tests image asset rendering and HTML output
  - `test_nested_pages_render_correctly()` - Validates deep nesting (child and grandchild pages)
- **Created test content fixtures**:
  - `content/index/index.yml` - Index page content for testing
  - `templates/index.php` - Index page template (Twig syntax)
  - `content/1.projects/nonexistent/nonexistent.yml` - Page with non-existent template
  - `content/test-asset/test-asset.yml` + `test-asset.jpg` - Page with image asset
  - `content/1.projects/1.project-1/child-section/` - Nested child page
  - `content/1.projects/1.project-1/child-section/1.grandchild/` - Deeply nested grandchild page
- **Total test count**: 35 tests, 74 assertions (all passing)

### Phase 8: Page Looping and Navigation Tests - 2026-03-07
- **Added page iteration test**: `test_can_loop_through_pages_with_first_image()`
  - Tests looping through top-level pages via `page.root`
  - Verifies page titles are accessible in iteration
  - Tests conditional rendering of first image per page
  - Validates Twig template syntax for page loops
- **Created test fixtures**:
  - `content/99.page-loop-test/99.page-loop-test.yml` - Test page content
  - `templates/page-loop-test.html` - Template demonstrating page iteration with `{% for child in page.root %}`
  - `content/1.projects/01.jpg`, `content/2.about/01.jpg`, `content/3.contact-me/01.jpg`, `content/99.page-loop-test/01.jpg` - Test images with IPTC metadata (title and caption)
- **Template demonstrates**:
  - Iterating through `page.root` to access all top-level pages
  - Accessing page properties like `child.title` and `child.images`
  - Using `|first` filter to get first image from collection
  - Conditional rendering with `{% if child.images|length > 0 %}`
- **Updated test to include image captions**: `test_can_loop_through_pages_with_images_and_captions()`
  - Tests accessing image captions defined in YAML content files
  - YAML format: `01.jpg: { description: "Caption text" }`
  - Verifies captions render using `child['01.jpg'].description`
  - Tests YAML-based image metadata (title, description)
- **Added inline image test**: `test_inline_images_render_with_captions()`
  - Tests markdown inline image syntax: `![Caption](filename.jpg)`
  - Validates that captions render as alt text in HTML
  - Tests markdown content processing with image references
- **Updated content files with image captions**:
  - `content/1.projects/category.yml` - Added caption for 01.jpg
  - `content/2.about/page.yml` - Added caption for 01.jpg
  - `content/3.contact-me/page.yml` - Added caption for 01.jpg
  - `content/99.page-loop-test/99.page-loop-test.yml` - Added caption for 01.jpg
  - `content/98.inline-image-test/98.inline-image-test.yml` - Test page with inline markdown image
- **Total test count**: 37 tests, 91 assertions (all passing)

### Summary
- **Total files modernized**: 20+
- **Tests**: 37 tests, 91 assertions (100% passing)
- **PHP version**: 8.3+ (tested on 8.4.16)
- **Code style**: PSR-12 compliant
- **Architecture**: DI container, readonly classes, typed properties
- **Image processing**: Intervention Image v3 (GD) replaces SLIR
- **Quality tools**: PHPStan (level 9 target), PHP-CS-Fixer, Rector configured

### Breaking Changes
- Entry point now requires Container initialization
- Config changed from static to instance-based
- Static methods converted to instance methods (with BC wrappers)
- Removed SLIR image processing library
- Minimum PHP version: 8.3

### Migration Notes
- **New**: Use `php -S localhost:8000 index.php` for development (single entry point)
- `index.php` handles both routing and static file serving
- Static method wrappers provided for gradual migration
- All original content/ folder structure preserved
- Tests can be run with `vendor/bin/phpunit`

## [3.0.0] - Original Release

### Added
- Initial Stacey CMS implementation
- Twig template engine integration
- YAML front matter support
- Markdown parsing with PHP Markdown Extra
- File-based content management
- Basic caching system
- SLIR image processing
- Static site generation

### Notes
- This version was designed for PHP 5.3+
- Used static methods extensively
- No test coverage
- No type safety
