# Core for bale

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bale/core.svg?style=flat-square)](https://packagist.org/packages/bale/core)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bale/core/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bale/core/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/bale/core/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/bale/core/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bale/core.svg?style=flat-square)](https://packagist.org/packages/bale/core)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/core.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/core)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require bale/core
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="core-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="core-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="core-views"
```

## Usage

```php
$core = new Bale\Core();
echo $core->echoPhrase('Hello, Bale!');
```

### Table Improvements (Filtering & Sorting)

Komponen tabel di `bale-core` telah dilengkapi dengan fitur filtering dan sorting yang terintegrasi dengan Livewire.

#### 1. Sorting dengan `x-core::table-th`

Gunakan `x-core::table-th` di dalam slot `thead` untuk membuat header yang bisa diklik.

```blade
<x-slot name="thead">
    <tr>
        <x-core::table-th
            label="Nama"
            sortBy="name"
            :sortField="$sortField"
            :sortDirection="$sortDirection"
        />
    </tr>
</x-slot>
```

**Di Livewire Component:**

```php
public $sortField = 'name';
public $sortDirection = 'asc';

public function sort($field) {
    if ($this->sortField === $field) {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }
}
```

#### 2. Filtering dengan `x-core::table`

Gunakan slot `filters` dan prop `activeFilters` untuk menambahkan UI filter.

```blade
<x-core::table
    :links="$data"
    header
    :activeFilters="array_filter(['Status' => $filterStatus])"
>
    <x-slot name="filters">
        <x-core::select-dropdown label="Status" wire:model.live="filterStatus">
            <option value="">Semua</option>
            <option value="active">Aktif</option>
        </x-core::select-dropdown>
    </x-slot>
    ...
</x-core::table>
```

**Di Livewire Component (Untuk Reset):**

```php
public function resetFilter($field) {
    if ($field === 'Status') $this->reset('filterStatus');
}

public function resetAllFilters() {
    $this->reset(['filterStatus', 'query']);
}
```

#### 3. Item Actions (`core.shared-components.item-actions`)

Komponen untuk tombol Edit, Delete (dengan konfirmasi dropdown), dan aksi tambahan (slot).

```blade
<livewire:core.shared-components.item-actions
    :editUrl="route('items.edit', $item->id)"
    :deleteId="$item->id"
    confirmMessage="Yakin ingin menghapus data ini?"
>
    <!-- Slot Aksi Tambahan -->
</livewire:core.shared-components.item-actions>
```

**Integrasi dengan Deletion Trait:**
Komponen `item-actions` didesain untuk bekerja secara otomatis dengan trait penghapusan pada setiap package.

1.  **Core Package**: Gunakan `Bale\Core\Traits\HasDeleteOption`.
2.  **CMS Package**: Gunakan `Bale\Cms\Traits\HasSafeDelete` (untuk support tenant connection).

**Cara Penggunaan (Contoh di CMS):**

```php
use Bale\Cms\Traits\HasSafeDelete;

class YourComponent extends Component {
    use HasSafeDelete;
    protected string $modelClass = Post::class;
}
```

Komponen akan otomatis memicu method `performDelete()` pada trait tersebut saat konfirmasi ditekan.

> [!IMPORTANT] > **Gunakan `wire:key` di Loop**
> Saat menggunakan `item-actions` di dalam loop (misalnya tabel), **WAJIB** menambahkan `wire:key` yang unik:
>
> ```blade
> <livewire:core.shared-components.item-actions
>     :editUrl="route('posts.edit', $post->id)"
>     :deleteId="$post->id"
>     wire:key="item-actions-{{ $post->id }}"
>     confirmMessage="Yakin ingin menghapus?"
> />
> ```
>
> Tanpa `wire:key`, Livewire akan kehilangan tracking komponen setelah update (seperti setelah delete), menyebabkan error `Method [delete] not found`.

#### 4. Breadcrumb Component (`x-core::breadcrumb`)

Komponen breadcrumb yang unified dan reusable untuk navigasi di seluruh package. Mendukung full breadcrumb trail dan simple navigation.

**Props:**

- `items` (array): Array of breadcrumb items, setiap item berisi:
  - `label` (string): Label yang ditampilkan
  - `route` (string): Nama route Laravel
  - `params` (optional): Parameter untuk route
  - `icon` (optional): Nama icon Lucide (tanpa prefix `lucide-`)
- `active` (string): Label untuk item aktif/current page
- `back` (boolean): Mode simple back link
- `href` (string): URL untuk back link mode
- `label` (string): Label untuk back link mode

**Contoh 1: Full Breadcrumb Trail**

```blade
{{-- Static breadcrumb --}}
<x-core::breadcrumb
    :items="[
        ['label' => 'Posts', 'route' => 'posts.index'],
        ['label' => 'Category', 'route' => 'categories.show', 'params' => $categoryId]
    ]"
    :active="'Edit: ' . $title"
/>

{{-- Dynamic breadcrumb dengan PHP --}}
@php
    $breadcrumbs = [
        ['label' => 'Navigations', 'route' => 'navigations.index']
    ];
    if ($parent) {
        $breadcrumbs[] = [
            'label' => $parent['name'],
            'route' => 'navigations.edit',
            'params' => $parent['slug'],
            'icon' => 'menu'
        ];
    }
@endphp
<x-core::breadcrumb :items="$breadcrumbs" :active="'Edit: ' . $name" />
```

**Contoh 2: Simple Breadcrumb (satu level)**

```blade
<x-core::breadcrumb
    :items="[['label' => 'Posts', 'route' => 'posts.index']]"
    active="Create New Post"
/>
```

**Contoh 3: Back Link Mode** _(opsional, untuk kompatibilitas)_

```blade
<x-core::breadcrumb
    back
    :href="route('posts.index')"
    label="Post List"
    active="Create New Post"
/>
```

**Best Practices:**

- Gunakan breadcrumb di semua halaman create/edit untuk konsistensi navigasi
- Untuk edit pages, limit panjang title: `Illuminate\Support\Str::limit($title, 20)`
- Gunakan icon untuk parent items di nested navigation
- Breadcrumb akan otomatis support dark mode dan Livewire navigation

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Papa Ree](https://github.com/papa-ree)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
