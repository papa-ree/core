<?php

namespace Bale\Core\Livewire\Pages\EntranceGate;

use Bale\Core\Models\EntranceGateAllowlist;
use Bale\Core\Services\EntranceGateService;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Form tambah/edit entri allowlist login (satu entri = satu IP atau CIDR).
 */
#[Layout('core::layouts.app')]
#[Title('Login Allowlist')]
class Form extends Component
{
    public ?int $entryId = null;

    public string $ip_address = '';

    public string $label = '';

    public bool $is_active = true;

    public function mount(?string $id = null): void
    {
        if (! auth()->user()?->can($this->permission())) {
            abort(403, 'Unauthorized action.');
        }

        if ($id) {
            $entry = EntranceGateAllowlist::findOrFail($id);

            $this->entryId = $entry->id;
            $this->ip_address = (string) $entry->ip_address;
            $this->label = (string) ($entry->label ?? '');
            $this->is_active = (bool) $entry->is_active;
        }
    }

    /**
     * Permission yang dibutuhkan: update saat edit, create saat tambah.
     */
    protected function permission(): string
    {
        return $this->entryId ? 'entrance-gate.update' : 'entrance-gate.create';
    }

    public function rules(): array
    {
        return [
            'ip_address' => [
                'required',
                'string',
                'max:64',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! app(EntranceGateService::class)->isValidEntry((string) $value)) {
                        $fail(__('Masukkan alamat IP atau CIDR yang valid.'));
                    }
                },
                Rule::unique('entrance_gate_allowlist', 'ip_address')->ignore($this->entryId),
            ],
            'label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        if (! auth()->user()?->can($this->permission())) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $this->validate();

        if ($this->entryId) {
            EntranceGateAllowlist::findOrFail($this->entryId)->update($validated);
        } else {
            EntranceGateAllowlist::create($validated);
        }

        $this->dispatch('toast', message: __('Allowlist login disimpan.'), type: 'success');
        $this->redirectRoute('entrance-gate', navigate: true);
    }

    public function render()
    {
        if (! auth()->user()?->can($this->permission())) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.entrance-gate.form');
    }
}
