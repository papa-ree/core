<?php

namespace Bale\Core\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardSelector extends Controller
{
    public static function resolve()
    {
        $user = Auth::user();

        // check rakaca package
        if (
            class_exists(\Paparee\Rakaca\Livewire\Pages\Guest\Index::class)
            && $user?->hasRole('guest')
        ) {
            return redirect()->route('rakaca.overview');
        }

        return redirect()->route('rakaca.landlord-dashboard.index');
    }
}
