<?php

use Bale\Core\Controllers\DashboardSelector;
use Bale\Core\Controllers\MediaController;
use Bale\Core\Livewire\Pages\UserManagement\Index as UserManagementIndex;
use Bale\Core\Livewire\Pages\UserManagement\Create;
use Bale\Core\Livewire\Pages\UserManagement\Edit;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'web'
])->group(function () {
    Route::get('/dashboard-selector', [DashboardSelector::class, 'resolve'])->name('dashboard');
});

// User Management - restricted to users with 'user management' permission
Route::middleware(['auth', 'web', 'permission:user management'])->group(function () {
    Route::get('/user-management', UserManagementIndex::class)->name('user-management');
    Route::get('/user-management/create', Create::class)->name('user-management.create');
    Route::get('/user-management/{id}/edit', Edit::class)->name('user-management.edit');
});

Route::middleware([
    'web'
])->group(function () {
    Route::get('/media/{path}', [MediaController::class, 'show'])
        ->where('path', '.*')
        ->name('media.show');
});