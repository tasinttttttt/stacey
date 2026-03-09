# Issues Log

This file documents major issues encountered during development and their solutions.

## Format Template:

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

### Prevention
How to avoid this in the future?
```

---

## Issue Log

## Issue #1: Website Pages Show Empty Content

**Date**: 2026-03-07
**Severity**: High
**Component**: Helpers, Page Rendering

### Problem
The website's work page displayed an empty article section with no project listings, despite the content files being present and correctly structured. Individual project pages also failed to load, returning 404 errors.

### Root Cause
Multiple issues were discovered:

1. **YAML Syntax Errors**: Content files (info.yml and all 11 project.yml files) contained colons in unquoted string values, causing the YAML parser to fail.

2. **Missing +++ Syntax Support**: The `preparseText()` method only handled `----` delimiters for multiline content, but content files used `+++` syntax.

3. **PageData::create() Missing Config**: The static wrapper created a Config instance without parameters, causing all file paths to be relative to the current directory instead of the project root.

4. **File Path to URL Conversion**: The regex pattern `/(\.+/)*content//` only matched relative paths (like `./content/`), but the application was receiving absolute paths (like `/Users/tbayri/STUDIO/stacey/content/1.work`).

5. **Missing Asset Conversions**: The `Page.parseTemplate()` method wasn't converting `children`, `siblings`, and `siblings_and_self` arrays to Page objects, preventing templates from accessing nested content.

### Solution

1. **Fixed YAML syntax** in all content files by quoting values containing colons
2. **Added +++ syntax support** in `PageData.preparseText()`
3. **Updated PageData::create()** to use LegacyConfig paths
4. **Fixed filePathToUrl()** regex to handle absolute paths: changed from `/(\.+/)*content//` to `/.*content\//`
5. **Added missing assets** to conversion list in `Page.parseTemplate()`
6. **Added recursive conversion** in `Helpers.toAssets()` to handle nested children

### Code Changes

**app/Helpers.php:71-76** - Fixed regex to handle absolute paths
```php
// Before
$url = preg_replace(['/\d+?\./', '/(\.+\/)*content\//'], '', $filePath);

// After  
$url = preg_replace(['/\d+?\./', '/.*content\//'], '', $filePath);
```

**app/PageData.php:432-446** - Use LegacyConfig in static wrapper
```php
// Before
$config = new Config();

// After
$config = new Config(
    rootFolder: \Stacey\Extension\Config::$root_folder,
    contentFolder: \Stacey\Extension\Config::$content_folder,
    templatesFolder: \Stacey\Extension\Config::$templates_folder,
    cacheFolder: \Stacey\Extension\Config::$cache_folder,
);
```

**app/asset/Page.php:68** - Added missing assets
```php
$assets = ['next_sibling', 'previous_sibling', 'images', 'video', 'videos', 
           'audio', 'root', 'children', 'siblings', 'siblings_and_self'];
```

**app/Helpers.php:455-475** - Added recursive conversion
```php
// Recursively convert children of each result
foreach ($result as $key => $item) {
    if (is_array($item) && isset($item['children']) && is_array($item['children'])) {
        $result[$key]['children'] = $this->toAssets($item['children']);
    }
}
```

### Prevention

1. **Validate YAML syntax** before committing content changes
2. **Use absolute paths consistently** throughout the codebase
3. **Test with both relative and absolute paths** in development
4. **Ensure static wrapper methods** use the same configuration as instance methods
5. **Add integration tests** that verify the full page rendering pipeline

---

## Issue #2: Asset URLs Show Absolute Filesystem Paths

**Date**: 2026-03-08
**Severity**: High
**Component**: Asset, Templates

### Problem
Content assets (thumbnails, images) displayed absolute filesystem paths instead of relative web URLs:
- Expected: `../content/1.work/project/thumb.jpg`
- Actual: `/Users/tbayri/STUDIO/stacey/content/1.work/project/thumb.jpg`

Additionally, inline images in project pages weren't loading because template used absolute file paths.

### Root Cause
1. **Asset URL generation**: `Asset::constructLinkPath()` only handled relative paths starting with `./`
2. **Template field missing**: No template-accessible field for content-relative paths
3. **Static file serving**: PHP built-in server didn't serve files from `/content/` prefix

### Solution
1. **Fixed Asset URL generation** - Strip root folder prefix and prepend relative root path
2. **Added content_path field** - Provides path relative to content folder
3. **Added content/ static serving** - Serve images from content folder in index.php
4. **Fixed root_path for home page** - Return `./` instead of empty string

### Code Changes

**app/asset/Asset.php:42-57** - Handle absolute paths
```php
protected function constructLinkPath(string $filePath): string
{
    $rootPath = $this->helpers->relativeRootPath();
    
    $relativePath = $filePath;
    if (isset(\Stacey\Extension\Config::$root_folder) && str_starts_with($filePath, \Stacey\Extension\Config::$root_folder)) {
        $relativePath = substr($filePath, strlen(\Stacey\Extension\Config::$root_folder));
    }
    
    $relativePath = ltrim($relativePath, '/');
    return $rootPath . $relativePath;
}
```

**app/PageData.php:178-184** - Add content_path field
```php
$contentPath = $page->filePath;
if (str_starts_with($contentPath, $this->config->contentFolder)) {
    $contentPath = substr($contentPath, strlen($this->config->contentFolder));
    $contentPath = ltrim($contentPath, '/');
}
$page->data['content_path'] = $contentPath;
```

**app/Helpers.php:344-348** - Fix home page root_path
```php
if ($depth === 0 || $parts[0] === 'index') {
    $result = './' . $this->modrewriteParse($url);
    return $result;
}
```

**index.php:31-54** - Add content/ static serving
```php
if (str_starts_with($uri, '/content/')) {
    $path = __DIR__ . $uri;
    if (file_exists($path) && is_file($path)) {
        return false;
    }
}
```

**templates/partials/footer.html:73** - Use content_path for images
```html
$(this).attr('data-original', '{{page.root_path}}content/{{ page.content_path }}/' +pastSrc);
```

### Prevention
1. Test asset loading on nested pages (/work/project/)
2. Test image paths from different page depths
3. Verify static file serving covers all asset locations
