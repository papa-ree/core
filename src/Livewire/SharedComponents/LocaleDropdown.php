<?php

namespace Bale\Core\Livewire\SharedComponents;

use Livewire\Component;

class LocaleDropdown extends Component
{
    public $currentLocale;
    public $supportedLocales;

    public function mount()
    {
        $this->currentLocale = app()->getLocale();
        $this->supportedLocales = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getSupportedLocales();
    }

    public function changeLocale($locale)
    {
        if (array_key_exists($locale, $this->supportedLocales)) {
            session()->put('locale', $locale);
            $this->redirect(request()->header('Referer'));
        }
    }

    public function render()
    {
        return view('core::livewire.shared-components.locale-dropdown');
    }
}
