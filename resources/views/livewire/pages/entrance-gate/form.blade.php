<div>
    <x-core::page-header
        :title="$entryId ? __('Edit Login Allowlist') : __('Tambah Login Allowlist')"
        :subtitle="__('Entri berupa alamat IP tunggal atau CIDR, mis. 192.168.1.10 atau 10.0.0.0/8')"
    />

    <div class="max-w-2xl mx-auto mt-6">
        <form wire:submit="save"
            class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm p-6 space-y-5">

            <div>
                <x-core::label value="Alamat IP / CIDR" for="ip_address" />
                <x-core::input id="ip_address" wire:model="ip_address" placeholder="192.168.1.10 atau 10.0.0.0/8" />
                @error('ip_address')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-core::label value="Label" for="label" />
                <x-core::input id="label" wire:model="label" placeholder="{{ __('Opsional, mis. Kantor Pusat') }}" />
                @error('label')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model="is_active"
                    class="size-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-slate-600 dark:bg-slate-800" />
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Aktifkan entri ini') }}</span>
            </label>

            <div class="flex items-center gap-3 pt-4 border-t border-gray-100 dark:border-slate-800">
                <x-core::button type="submit" variant="success" :label="$entryId ? __('Perbarui') : __('Simpan')" />
                <x-core::button link href="{{ route('entrance-gate') }}" variant="secondary" :label="__('Batal')" />
            </div>
        </form>
    </div>
</div>
