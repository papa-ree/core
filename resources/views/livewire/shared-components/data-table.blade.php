<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Computed, Url, On, Locked};

new class extends Component {
    use WithPagination;

    #[Locked]
    public string $model = '';
    public array  $columns            = [];
    public array  $with               = [];
    public array  $searchable         = [];
    public array  $constraints        = [];
    public string $connection         = '';
    public string $connectionResolver = '';
    public string $deleteEvent        = 'deleteItem';
    public string $rowView            = '';

    #[Url(history: true)]
    public string $query = '';
    public string $sortField     = 'created_at';
    public string $sortDirection = 'desc';
    public int    $perPage       = 20;
    public array  $activeFilters = [];

    public function boot(): void
    {
        if (! $this->connectionResolver) {
            return;
        }
        [$class, $method] = explode('::', $this->connectionResolver, 2);
        if (class_exists($class) && method_exists($class, $method)) {
            $resolved = $class::$method();
            if ($resolved) {
                $this->connection = $resolved;
            }
        }
    }

    public function mount(
        string $model,
        string $rowView,
        array  $columns            = [],
        array  $with               = [],
        array  $searchable         = [],
        array  $constraints        = [],
        string $connection         = '',
        string $connectionResolver = '',
        string $sortField          = 'created_at',
        string $sortDirection      = 'desc',
        int    $perPage            = 20,
        string $deleteEvent        = 'deleteItem',
    ): void {
        $this->model               = $model;
        $this->rowView             = $rowView;
        $this->columns             = $columns;
        $this->with                = $with;
        $this->searchable          = $searchable;
        $this->constraints         = $constraints;
        $this->connection          = $connection;
        $this->connectionResolver  = $connectionResolver;
        $this->sortField           = $sortField;
        $this->sortDirection       = $sortDirection;
        $this->perPage             = $perPage;
        $this->deleteEvent         = $deleteEvent;
    }

    public function sort(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatedQuery(): void   { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    public function resetFilter(string $field): void
    {
        unset($this->activeFilters[$field]);
        $this->resetPage();
        unset($this->records);
    }

    public function resetAllFilters(): void
    {
        $this->activeFilters = [];
        $this->resetPage();
        unset($this->records);
    }

    #[On('deleteItem')]
    public function deleteItem(int|string $id): void
    {
        $modelClass = $this->model;
        if (! class_exists($modelClass)) {
            return;
        }
        $instance = new $modelClass;
        $builder  = $this->connection
            ? $instance->setConnection($this->connection)->newQuery()
            : $instance->newQuery();
        $builder->where($instance->getKeyName(), $id)->delete();
        unset($this->records);
        $this->dispatch('toast', message: 'Item deleted successfully!', type: 'success');
    }

    #[Computed]
    public function records()
    {
        $modelClass = $this->model;
        if (! class_exists($modelClass)) {
            return collect();
        }
        $instance = new $modelClass;
        $builder  = $this->connection
            ? $instance->setConnection($this->connection)->newQuery()
            : $instance->newQuery();
        if ($this->with) {
            $builder->with($this->with);
        }
        foreach ($this->constraints as $col => $val) {
            if (is_array($val) && count($val) === 2) {
                $builder->where($col, $val[0], $val[1]);
            } else {
                $builder->where($col, $val);
            }
        }
        foreach ($this->activeFilters as $col => $val) {
            if ($val !== '' && $val !== null) {
                $builder->where($col, $val);
            }
        }
        if ($this->query && $this->searchable) {
            $builder->where(function ($q) {
                foreach ($this->searchable as $i => $col) {
                    $m = $i === 0 ? 'where' : 'orWhere';
                    $q->$m($col, 'like', '%' . $this->query . '%');
                }
            });
        }
        $builder->orderBy($this->sortField, $this->sortDirection);
        return $builder->paginate($this->perPage);
    }
};
?>
<div class="space-y-4">

    <div class="relative overflow-hidden bg-white border border-gray-200 shadow-sm dark:bg-gray-900 rounded-2xl dark:border-gray-700/60">

        {{-- PROGRESS BAR: tipis di atas card, muncul saat ada proses apapun --}}
        <div wire:loading
             wire:target="sort,nextPage,previousPage,gotoPage,updatedPerPage,deleteItem,resetFilter,resetAllFilters,query"
             class="absolute top-0 inset-x-0 z-20 h-[3px] overflow-hidden rounded-t-2xl">
            <div class="h-full w-full bg-linear-to-r from-indigo-500 via-purple-500 to-indigo-500 bg-size-[200%_100%] animate-shimmer"></div>
        </div>

        {{-- TOOLBAR --}}
        <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700/60 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                {{-- Left: Active filter badges --}}
                <div class="flex flex-wrap items-center gap-1.5 min-h-[32px]">
                    @if(count($activeFilters) > 0)
                        <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mr-1">Filters:</span>
                        @foreach($activeFilters as $filterKey => $filterVal)
                            @if($filterVal !== '' && $filterVal !== null)
                                <span class="inline-flex items-center gap-1 py-1 ps-2.5 pe-1.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-700/40">
                                    {{ is_array($filterVal) ? implode(', ', $filterVal) : $filterVal }}
                                    <button type="button" wire:click="resetFilter('{{ $filterKey }}')"
                                        class="shrink-0 size-3.5 inline-flex items-center justify-center rounded-full hover:bg-indigo-200 dark:hover:bg-indigo-700 transition-colors">
                                        <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                    </button>
                                </span>
                            @endif
                        @endforeach
                        <button type="button" wire:click="resetAllFilters"
                            class="text-[11px] font-semibold text-red-500 hover:text-red-600 dark:text-red-400 transition-colors ml-1 pl-2 border-l border-gray-200 dark:border-gray-700">
                            Clear All
                        </button>
                    @endif
                </div>

                {{-- Right: Controls --}}
                <div class="flex items-center gap-2 sm:gap-3 flex-wrap sm:flex-nowrap">

                    {{-- Filter Slot --}}
                    @if(isset($filters))
                        <div class="hs-dropdown relative inline-flex [--auto-close:inside] [--placement:bottom-right]">
                            <button id="hs-dt-filter-{{ $this->getId() }}" type="button"
                                class="hs-dropdown-toggle inline-flex items-center gap-x-2 py-2 px-3 text-sm font-medium rounded-xl border border-gray-200 bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none transition-all dark:bg-gray-800 dark:border-gray-600/60 dark:text-gray-300 dark:hover:bg-gray-700">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                Filter
                                @if(count($activeFilters) > 0)
                                    <span class="size-4 inline-flex items-center justify-center rounded-full bg-indigo-600 text-white text-[9px] font-bold">{{ count($activeFilters) }}</span>
                                @endif
                                <svg class="hs-dropdown-open:rotate-180 size-3.5 text-gray-400 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div class="hs-dropdown-menu transition-[opacity,margin] hs-dropdown-open:opacity-100 opacity-0 hidden z-50 mt-2 min-w-72 bg-white shadow-xl rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
                                <div class="space-y-3">{{ $filters }}</div>
                                <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                                    <button type="button" wire:click="resetAllFilters" class="py-1.5 px-3 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 transition-all">Reset Filters</button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Search Input --}}
                    <div class="relative flex-1 sm:flex-none sm:min-w-56">
                        {{-- Spinner (saat query loading) --}}
                        <div wire:loading.flex wire:target="query"
                            class="absolute inset-y-0 start-0 items-center ps-3 pointer-events-none z-10">
                            <svg class="size-4 animate-spin text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </div>
                        {{-- Search icon (normal) --}}
                        <div wire:loading.remove wire:target="query"
                            class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="size-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                        {{-- Clear button --}}
                        @if($query)
                            <button type="button" wire:click="$set('query', '')"
                                class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors z-10">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        @endif
                        {{-- Input --}}
                        <input
                            type="text"
                            wire:model.live.debounce.400ms="query"
                            placeholder="Search..."
                            class="block w-full py-2 ps-9 {{ $query ? 'pe-8' : 'pe-4' }} text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition-all dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-900/30"
                        />
                    </div>

                    {{-- Per-page Selector --}}
                    <div class="relative">
                        <select wire:model.live="perPage"
                            class="appearance-none py-2 ps-3 pe-8 text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition-all dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-indigo-500 cursor-pointer">
                            <option value="10">10 / page</option>
                            <option value="20">20 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                        <div class="absolute inset-y-0 end-2 flex items-center pointer-events-none">
                            <svg class="size-3.5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        {{-- END TOOLBAR --}}


        {{-- TABLE --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/60">
                <thead class="bg-gray-50/70 dark:bg-gray-800/50">
                    <tr>
                        @foreach($columns as $col)
                            @php
                                $hiddenClass = match($col['hidden'] ?? null) {
                                    'sm'  => 'hidden sm:table-cell',
                                    'md'  => 'hidden md:table-cell',
                                    'lg'  => 'hidden lg:table-cell',
                                    'xl'  => 'hidden xl:table-cell',
                                    default => '',
                                };
                                $isSortable = $col['sortable'] ?? false;
                                $isActive   = $sortField === $col['key'];
                            @endphp
                            <th scope="col" class="px-4 py-3 text-left whitespace-nowrap {{ $hiddenClass }} {{ $col['class'] ?? '' }}">
                                @if($isSortable)
                                    <button type="button" wire:click="sort('{{ $col['key'] }}')"
                                        class="group inline-flex items-center gap-x-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none transition-colors">
                                        {{ $col['label'] }}
                                        <span class="flex-none">
                                            @if($isActive && $sortDirection === 'asc')
                                                <svg class="size-3.5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m18 15-6-6-6 6"/></svg>
                                            @elseif($isActive && $sortDirection === 'desc')
                                                <svg class="size-3.5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                                            @else
                                                <svg class="size-3.5 opacity-0 group-hover:opacity-40 transition-opacity text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                                            @endif
                                        </span>
                                    </button>
                                @else
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $col['label'] }}</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody
                    wire:loading.class="opacity-40 pointer-events-none select-none"
                    wire:target="sort,nextPage,previousPage,gotoPage,updatedPerPage,deleteItem,resetFilter,resetAllFilters,query"
                    class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-900 transition-opacity duration-200">
                    @if($this->records->isEmpty())
                        <tr>
                            <td colspan="{{ count($columns) }}" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    @if($query)
                                        <div class="size-14 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                                            <svg class="size-7 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v4M11 16h.01"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No results found</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">No items matched "<span class="font-medium text-gray-600 dark:text-gray-400">{{ $query }}</span>"</p>
                                        </div>
                                        <button type="button" wire:click="$set('query', '')" class="mt-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 hover:underline transition-colors">Clear search</button>
                                    @else
                                        <div class="size-14 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                            <svg class="size-7 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No data yet</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Get started by adding your first item.</p>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @else
                        @foreach($this->records as $record)
                            @include($rowView, ['record' => $record])
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        {{-- END TABLE --}}


        {{-- PAGINATION FOOTER --}}
        @if($this->records instanceof \Illuminate\Pagination\LengthAwarePaginator && $this->records->total() > 0)
            <div class="px-4 py-3.5 border-t border-gray-100 dark:border-gray-700/60 sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Showing
                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $this->records->firstItem() }}</span>
                        &ndash;
                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $this->records->lastItem() }}</span>
                        of
                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($this->records->total()) }}</span>
                        items
                        @if($query)
                            <span class="text-indigo-500 font-medium ml-1">for "<em>{{ $query }}</em>"</span>
                        @endif
                    </p>

                    <div class="flex items-center gap-1">
                        @if($this->records->onFirstPage())
                            <span class="inline-flex items-center justify-center size-8 rounded-lg text-gray-300 dark:text-gray-600 cursor-not-allowed"><svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></span>
                        @else
                            <button wire:click="previousPage" type="button" class="inline-flex items-center justify-center size-8 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:text-gray-400 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-all"><svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></button>
                        @endif

                        @php
                            $currentPage = $this->records->currentPage();
                            $lastPage    = $this->records->lastPage();
                            $from        = max(1, $currentPage - 2);
                            $to          = min($lastPage, $currentPage + 2);
                        @endphp

                        @if($from > 1)
                            <button wire:click="gotoPage(1)" type="button" class="inline-flex items-center justify-center size-8 rounded-lg text-xs font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 dark:text-gray-400 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-all">1</button>
                            @if($from > 2)<span class="inline-flex items-center justify-center size-8 text-xs text-gray-400">...</span>@endif
                        @endif

                        @for($i = $from; $i <= $to; $i++)
                            @if($i === $currentPage)
                                <span class="inline-flex items-center justify-center size-8 rounded-lg text-xs font-semibold bg-indigo-600 text-white shadow-sm">{{ $i }}</span>
                            @else
                                <button wire:click="gotoPage({{ $i }})" type="button" class="inline-flex items-center justify-center size-8 rounded-lg text-xs font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 dark:text-gray-400 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-all">{{ $i }}</button>
                            @endif
                        @endfor

                        @if($to < $lastPage)
                            @if($to < $lastPage - 1)<span class="inline-flex items-center justify-center size-8 text-xs text-gray-400">...</span>@endif
                            <button wire:click="gotoPage({{ $lastPage }})" type="button" class="inline-flex items-center justify-center size-8 rounded-lg text-xs font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 dark:text-gray-400 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-all">{{ $lastPage }}</button>
                        @endif

                        @if($this->records->hasMorePages())
                            <button wire:click="nextPage" type="button" class="inline-flex items-center justify-center size-8 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:text-gray-400 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-all"><svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></button>
                        @else
                            <span class="inline-flex items-center justify-center size-8 rounded-lg text-gray-300 dark:text-gray-600 cursor-not-allowed"><svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        {{-- END PAGINATION --}}

    </div>

</div>

@script
<script>
    $wire.on('paginated', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    Livewire.hook('morph.added', ({ el }) => {
        if (window.HSStaticMethods) {
            window.HSStaticMethods.autoInit(['dropdown']);
        }
    });
</script>
@endscript