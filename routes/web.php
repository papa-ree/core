<?php

use Bale\Core\Controllers\DashboardSelector;
use Bale\Core\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'web'
])->group(function () {
    Route::get('/dashboard-selector', [DashboardSelector::class, 'resolve'])->name('dashboard');
});

Route::middleware([
    'web'
])->group(function () {
    Route::get('/media/{path}', [MediaController::class, 'show'])
        ->where('path', '.*')
        ->name('media.show');
});