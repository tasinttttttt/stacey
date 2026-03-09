# Stacey CMS 4.0

A modern, file-based content management system built for PHP 8.3+.

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## Overview

Stacey is a lightweight, file-based CMS that generates websites from Markdown/YAML content files and Twig templates. No database required - content lives in simple files and folders.

### Key Features

- **File-based content** - No database needed, content lives in plain text files
- **Twig templating** - Powerful template engine with inheritance and macros
- **Markdown support** - Write content in Markdown with YAML front matter
- **Image processing** - Built-in image resizing and optimization (Intervention Image v3)
- **PHP 8.3+ ready** - Modern PHP with strict typing and readonly classes
- **Caching system** - Intelligent caching for fast page loads
- **Clean URLs** - SEO-friendly URLs without query strings

## Requirements

- PHP 8.3 or higher
- GD extension (for image processing)
- Composer

## Installation

```bash
# Clone the repository
git clone https://github.com/kolber/stacey.git
cd stacey

# Install dependencies
composer install --no-dev

# For development with all tools
composer install
```

## Project Structure

```
stacey/
├── app/                    # Core application files
│   ├── asset/             # Asset classes (Page, Image, Video, etc.)
│   ├── parsers/           # Content parsers (Markdown, Template)
│   ├── Config.php         # Application configuration
│   ├── Container.php      # Dependency injection container
│   ├── Cache.php          # Page caching system
│   ├── Helpers.php        # Utility functions
│   ├── PageData.php       # Page data generation
│   └── Stacey.php         # Main application class
├── content/               # Your content files (demo included)
│   ├── _shared.yml       # Global site data
│   ├── index/            # Homepage
│   ├── 1.work/           # Work section with demo project
│   └── 2.about/          # About page
├── extension/             # Extensions and custom code
├── public/                # Public web root (empty - add your CSS/JS)
├── templates/             # Twig templates
├── tests/                 # PHPUnit tests
├── index.php             # Application entry point
└── README.md             # This file
```

## Demo Content

The project includes minimal demo content showcasing core features:

- **Home** (/) - Welcome message
- **Work** (/work/) - Project listing with 1 demo project  
- **Demo Project** (/work/demo-project/) - Shows images, metadata, bilingual content
- **About** (/about/) - Simple text page

Replace this content with your own by editing files in `content/`.

## Development

### Running the Development Server

The easiest way to run Stacey locally is with PHP's built-in server:

```bash
php -S localhost:8000 index.php
```

Then open http://localhost:8000 in your browser.

### Alternative: Using a Web Server

For production, configure your web server to use `index.php` as the front controller:

**Apache (.htaccess):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Creating Content

### Content Organization

The `content/` folder holds all page content. Stacey uses a filesystem-based structure:

```
content/
├── _shared.yml           # Global data available to all pages
├── index/               # Homepage (optional)
│   └── index.yml
├── 1.projects/          # Visible in navigation, ordered by number
│   ├── project.yml
│   └── 01.first-project/
│       ├── project.yml
│       └── thumb.jpg
└── 2.info/              # "Info" page at /info/
    └── info.yml
```

### Number Prefix Convention

- **Number-prefixed items** (`1.projects`, `01.jpg`) are **visible** in navigation and **ordered** by number
- **Non-prefixed items** (`feed`, `search`) are **hidden** from navigation but **accessible** via URL
- The number prefix is **stripped** from URLs (e.g., `1.projects` → `/projects/`)

### Content File Format

Content files use **YAML front matter** with optional **Markdown** body:

```yaml
---
title: My Page
description: A short description for SEO
---
# Optional Markdown content below
This is the page content written in **Markdown**.
```

### Project/Collection Pages

For project pages or collections, create a folder with a `project.yml` file:

```yaml
---
meta:
    title: My Project
    date: 2024
    status: In Progress
    
infofr: Description in French
infoen: Description in English

contentfr: |
    Project description in French.
    Supports **Markdown** formatting.

contenten: |
    Project description in English.
    Supports **Markdown** formatting.
---

Optional additional Markdown content here.
```

### YAML Syntax Rules

1. **Colons in values** must be quoted:
   ```yaml
   # Wrong - will cause parsing error
   infofr: Description: with colon
   
   # Correct
   infofr: "Description: with colon"
   ```

2. **Multiline content** use literal block syntax (`|`):
   ```yaml
   contentfr: |
       Line breaks are preserved.
       This is useful for long text.
   ```

3. **Empty fields** use empty quotes:
   ```yaml
   contentfr: ""
   contenten: ""
   ```

### Image Assets

Place images in your content folders:

```
content/1.projects/01.my-project/
├── project.yml          # Project metadata
├── thumb.jpg            # Thumbnail for listings
├── image-01.jpg        # Inline images
├── image-02.jpg
└── video.mp4           # Video files
```

**Image naming:**
- `thumb.jpg` - Automatically used as thumbnail in listings
- Number-prefixed images (`01.image.jpg`) are sorted in listings
- Postfixes `_sml` and `_lge` create small/large variants

### Available Page Variables

In your templates, pages provide these variables:

| Variable | Description |
|----------|-------------|
| `page.name` | Page name from folder/file |
| `page.title` | Title from YAML meta |
| `page.slug` | URL-friendly slug (without number prefix) |
| `page.content` | Parsed Markdown content (HTML) |
| `page.meta.*` | Any field from meta section |
| `page.children` | Child pages (for parent pages) |
| `page.siblings` | Sibling pages in same folder |
| `page.parent` | Parent page object |
| `page.images` | Image files in folder |
| `page.files` | All files in folder |
| `page.root_path` | Relative path to site root (e.g., `../` or `./`) |
| `page.content_path` | Path relative to content folder (e.g., `1.projects/my-project`) |
| `page.thumb` | Thumbnail object with url, width, height |

### Global Shared Data

Create `_shared.yml` in the content folder for data available to all pages:

```yaml
---
site_name: My Website
site_url: https://example.com
---
```

Access in templates: `{{ page.root.site_name }}`

## Template Structure

Templates are located in `templates/` and use the filename convention:
- `page.yml` → `page.html` template
- `project.yml` → `project.html` template
- `index.yml` → `index.html` template

### Template Files

```
templates/
├── index.html           # Homepage template
├── work.html            # Work/projects listing template
├── project.html         # Individual project template
├── info.html           # Info page template
├── partials/           # Reusable template parts
│   ├── header.html
│   ├── footer.html
│   └── navigation/
└── _partials/          # Internal partials
```

### How Templates Work

1. Content file determines template: `project.yml` → `project.html`
2. Template receives `page` object with all variables
3. Use `{% extends "layout.html" %}` for master templates
4. Use `{% include "partials/header.html" %}` for reusable parts

### Root Path Usage

The `page.root_path` variable provides relative paths based on page depth:

| Page | root_path value |
|------|-----------------|
| Home (/) | `./` |
| /work/ | `../` |
| /work/project/ | `../../` |

Use this for linking to assets:
```html
<link rel="stylesheet" href="{{ page.root_path }}public/docs/css/style.css">
<img src="{{ page.root_path }}content/{{ page.content_path }}/image.jpg">
```

## Templating

Stacey uses [Twig](https://twig.symfony.com/) for templates. Place templates in the `templates/` folder.

### Basic Template (`templates/page.html`)

```html
<!DOCTYPE html>
<html>
<head>
    <title>{{ page.title }}</title>
</head>
<body>
    <h1>{{ page.title }}</h1>
    <div class="content">
        {{ page.content|raw }}
    </div>
</body>
</html>
```

### Available Variables

- `page.title` - Page title
- `page.content` - Markdown content (HTML)
- `page.url` - Full URL to page
- `page.permalink` - Clean URL path
- `page.children` - Child pages
- `page.siblings` - Sibling pages
- `page.images` - Image assets
- `page.files` - All files in folder

### Custom Twig Extensions

Stacey includes custom Twig filters and functions in `extension/StaceyTwigExtension.php`:

- `{{ path|absolute }}` - Convert relative path to absolute URL
- `{{ text|truncate(100) }}` - Truncate text to length
- `{{ items|sortby('date') }}` - Sort array by key

## Testing

Run the test suite with PHPUnit:

```bash
# Run all tests
vendor/bin/phpunit

# Run with code coverage
vendor/bin/phpunit --coverage-html coverage/

# Run specific test suite
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
```

### Test Structure

- **Unit Tests** (`tests/Unit/`): Test individual classes in isolation
- **Integration Tests** (`tests/Integration/`): Test full page rendering and asset handling

## Code Quality

### Running Static Analysis

```bash
# PHPStan (level 9)
vendor/bin/phpstan analyse

# With memory limit
vendor/bin/phpstan analyse --memory-limit=512M
```

### Code Style

```bash
# Check code style
vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style automatically
vendor/bin/php-cs-fixer fix
```

### Automated Refactoring

```bash
# Preview Rector changes
vendor/bin/rector process --dry-run

# Apply Rector changes
vendor/bin/rector process
```

### All Quality Checks

```bash
# Run tests, static analysis, and code style check
composer check
```

## Configuration

### Application Config (`app/Config.php`)

Edit paths and settings in the Config class:

```php
$config = new Config(
    rootFolder: './',
    contentFolder: './content',
    templatesFolder: './templates',
    cacheFolder: './app/_cache',
);
```

### Environment Variables

You can override config via environment variables in your web server configuration.

## Caching

Stacey automatically caches rendered pages in `app/_cache/pages/`. The cache is invalidated when:

- Content files are modified
- Template files are modified
- `.htaccess` is modified
- Cache files are older than content

To manually clear the cache:

```bash
rm -rf app/_cache/pages/*
```

## Image Processing

Images are automatically processed using Intervention Image v3:

```php
// In your templates
<img src="{{ image.url }}" width="{{ image.width }}" height="{{ image.height }}">
```

For custom image processing, use the ImageProcessor class:

```php
use Stacey\Core\ImageProcessor;

$processor = new ImageProcessor();
$processor->resize('input.jpg', 'output.jpg', 800, 600);
```

## Troubleshooting

### YAML Parsing Errors

If you see errors like "A colon cannot be used in an unquoted mapping value":

1. **Check for colons in values** - Use quotes around any value containing a colon:
   ```yaml
   # Wrong
   infofr: Description: with colon here
   
   # Correct
   infofr: "Description: with colon here"
   ```

2. **Check for multiline syntax** - Use `|` for literal blocks:
   ```yaml
   # Correct multiline
   contentfr: |
       Line one
       Line two
   ```

### 404 Errors

- Ensure content folder exists and has correct permissions
- Check that your web server is configured to use `index.php`
- Verify URLs have trailing slashes (e.g., `/projects/` not `/projects`)
- Content files must have `.yml` extension matching the template name

### Images Not Loading

1. **Check paths** - Images in content folder need to be referenced correctly:
   ```html
   <!-- For project images, use content_path -->
   <img src="{{ page.root_path }}content/{{ page.content_path }}/image.jpg">
   
   <!-- For thumbnails -->
   <img src="{{ page.thumb.url }}">
   ```

2. **Check static file serving** - For PHP built-in server, ensure `index.php` handles static files:
   - `/public/` files are served automatically
   - `/content/` files are served automatically

### Cache Issues

- Clear the cache folder: `rm -rf app/_cache/pages/*`
- Clear Twig cache: `rm -rf app/_cache/templates/*`
- Ensure `app/_cache/` is writable by the web server

### Empty Content on Pages

If pages show but content is empty:

1. Check that children/siblings are converted to Page objects (the system handles this automatically)
2. Verify your YAML front matter is valid
3. Check for parsing errors in page content

### Image Processing

- Verify GD extension is installed: `php -m | grep gd`
- Check image files have correct permissions

## Migration from v3.x

### Breaking Changes

- **PHP 8.3+ required** (was 5.3+)
- **Entry point changed** - Use `index.php` as the single entry point
- **Config is now instance-based** - See Configuration section
- **Static methods converted to instance methods** - Backward compatible wrappers provided
- **SLIR removed** - Replaced with Intervention Image v3

### Migration Steps

1. Update PHP to 8.3+
2. Run `composer install`
3. Update web server config to use `index.php`
4. Clear old cache files
5. Test your site thoroughly

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Run tests: `composer check`
4. Commit your changes
5. Push to the branch: `git push origin feature/my-feature`
6. Submit a pull request

### Code Standards

- Follow PSR-12 coding standards
- Write tests for new features
- Ensure PHPStan level 9 compliance
- Document breaking changes in CHANGELOG.md

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Credits

Stacey was created by [Anthony Kolber](https://github.com/kolber).

Modernization to PHP 8.3+ completed in 2026.

## Resources

- [Documentation](https://github.com/kolber/stacey/wiki)
- [Issue Tracker](https://github.com/kolber/stacey/issues)
- [Modernization Plan](plan/MODERNIZATION_PLAN.md)
- [Changelog](CHANGELOG.md)

---

**Note**: This is version 4.0 - a modernized fork with PHP 8.3+ support, comprehensive testing, and improved code quality tools.
