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

        // prioritize has dashboard permissions
        if ($user?->can('dashboard')) {
            return redirect()->route('rakaca.landlord-dashboard.index');
        }

        // check rakaca package
        if (
            class_exists(\Paparee\Rakaca\Livewire\Pages\Guest\Dashboard\Index::class)
            && $user?->can('guest.dashboard')
        ) {
            return redirect()->route('rakaca.guest-dashboard.index');
        }

        // Fallback for users with no dashboard permissions
        return redirect('/');
    }
}
