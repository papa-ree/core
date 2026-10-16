<x-core::page-header
    gradient
    :title="__('Login Allowlist')"
    :subtitle="__('Batasi akses halaman login (entrance.gate) hanya dari IP terdaftar')"
>
    <x-slot name="action">
        @can('entrance-gate.create')
            <x-core::button link href="{{ route('entrance-gate.create') }}" label="Tambah IP">
                <x-slot name="icon">
                    <x-lucide-plus class="w-5 h-5" />
                </x-slot>
            </x-core::button>
        @endcan
    </x-slot>
</x-core::page-header>
