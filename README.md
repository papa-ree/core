# Bale Core Package

Core functionality and utilities for the Bale application ecosystem.

## Features

- 🔐 Authentication & Authorization
- 🌍 CDN Support with Multiple Access Methods
- 🎨 Blade Components
- 🔧 Utilities & Helpers

## Installation

```bash
composer require bale/core
```

## CDN Asset Management

The Core package provides flexible CDN support with **three different methods** to access CDN functionality.

### Configuration

Add to your `.env` file:

```env
CORE_CDN_ENABLED=true
CORE_CDN_URL=https://cdn.example.com
CORE_CDN_PREFIX=bale
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag=bale-core:config
```

---

### Method 1: CDN Facade (Recommended) ⭐

**Usage in Blade Views:**

```blade
{{-- Organization-specific path --}}
<img src="{{ CDN::asset('thumbnails/image.jpg') }}">
{{-- Output: https://cdn.example.com/bale/org-slug/thumbnails/image.jpg --}}

{{-- Shared path (no organization_slug) --}}
<img src="{{ CDN::asset('shared/logo.png') }}">
{{-- Output: https://cdn.example.com/bale/shared/logo.png --}}

{{-- Custom directory --}}
<img src="{{ CDN::asset('banner.jpg', 'marketing') }}">
{{-- Output: https://cdn.example.com/bale/marketing/banner.jpg --}}
```

**Usage in Controllers/Classes:**

```php
use CDN;

class PostController
{
    public function show($id)
    {
        $post = Post::find($id);
        $thumbnailUrl = CDN::asset('thumbnails/' . $post->thumbnail);

        return view('posts.show', compact('post', 'thumbnailUrl'));
    }
}
```

**Available Methods:**

```php
CDN::asset('path/to/file.jpg');           // Generate CDN URL
CDN::url('path/to/file.jpg');             // Alias for asset()
CDN::enabled();                            // Check if CDN is enabled
CDN::baseUrl();                           // Get CDN base URL
CDN::prefix();                            // Get CDN prefix
```

---

### Method 2: View Composer

The `$cdn` variable is available in **all** Blade views automatically.

**Usage:**

```blade
<img src="{{ $cdn->asset('thumbnails/image.jpg') }}">
<img src="{{ $cdn->url('shared/logo.png') }}">

@if($cdn->enabled())
    {{-- CDN is active --}}
@endif
```

**Advantages:**

- No need to import anything
- Object-oriented approach
- Available everywhere

---

### Method 3: Model Accessors

For model-based images, use accessors for automatic URL generation.

**Example in Post Model:**

```php
use Bale\Core\Support\Cdn;
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function thumbnailUrl(): Attribute
{
    return Attribute::make(
        get: fn() => $this->thumbnail
            ? Cdn::asset('thumbnails/' . $this->thumbnail)
            : null,
    );
}
```

**Usage in Views:**

```blade
@foreach($posts as $post)
    <img src="{{ $post->thumbnail_url }}" alt="{{ $post->title }}">
@endforeach
```

**Advantages:**

- Clean view code
- Encapsulation in model
- No repetition

---

## Helper Functions (Alternative)

If you prefer traditional helper functions:

```php
// These are also available globally
cdn_asset('thumbnails/image.jpg');
cdn_url('thumbnails/image.jpg');
cdn_enabled();
```

---

## CDN Modes

### 1. Organization-Specific (Default)

Automatically adds `organization_slug` from the options table (CMS package required).

```php
CDN::asset('thumbnails/logo.jpg');
// → https://cdn.example.com/bale/dinas-pendidikan/thumbnails/logo.jpg
```

### 2. Shared Assets

Paths starting with `shared/` skip the organization_slug.

```php
CDN::asset('shared/default-avatar.png');
// → https://cdn.example.com/bale/shared/default-avatar.png
```

### 3. Custom Directory

Override the organization_slug with a custom directory.

```php
CDN::asset('hero.jpg', 'landing-pages');
// → https://cdn.example.com/bale/landing-pages/hero.jpg
```

---

## Fallback Behavior

When CDN is **disabled** (`CORE_CDN_ENABLED=false`):

```php
CDN::asset('thumbnails/image.jpg');
// → /thumbnails/image.jpg (local path)
```

---

## Best Practices

### ✅ Recommended

```blade
{{-- Use Facade for simplicity --}}
<img src="{{ CDN::asset('thumbnails/' . $post->thumbnail) }}">

{{-- Use Model Accessor for cleaner views --}}
<img src="{{ $post->thumbnail_url }}">
```

### ⚠️ Avoid

```blade
{{-- Don't hardcode CDN URLs --}}
<img src="https://cdn.example.com/bale/org/thumbnails/image.jpg">

{{-- Don't mix methods unnecessarily --}}
<img src="{{ cdn_asset('thumbnails/' . $post->thumbnail) }}"> {{-- Use CDN::asset() instead --}}
```

---

## Production Deployment

After updating CDN configuration, clear caches:

```bash
composer dump-autoload --optimize
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Troubleshooting

### Issue: CDN URLs not generated

**Check:**

1. `CORE_CDN_ENABLED` is `true` in `.env`
2. `CORE_CDN_URL` is set correctly
3. Config cache cleared: `php artisan config:clear`

### Issue: Organization slug missing

**Check:**

1. CMS package is installed
2. `organization_slug` exists in options table
3. Helper `organization_slug()` is available

### Issue: Facade not found

**Check:**

1. `composer dump-autoload` has been run
2. Facade alias registered in `composer.json`
3. Laravel service discovery is working

---

## License

MIT License
