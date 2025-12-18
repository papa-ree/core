<?php

use Bale\Core\Controllers\DashboardSelector;
use Bale\Core\Controllers\MediaController;
use Bale\Core\Livewire\Pages\Dashboard\Index;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'verified',
    'web'
])->group(function () {
    Route::get('/dashboard-selector', [DashboardSelector::class, 'resolve'])->name('dashboard');

    // Route::get('/dashboard', Index::class);

    Route::get('/media/{path}', [MediaController::class, 'show'])
        ->where('path', '.*')
        ->name('media.show');
});