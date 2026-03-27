<?php

use Bale\Core\Controllers\DashboardSelector;
use Bale\Core\Controllers\MediaController;
use Bale\Core\Livewire\Pages\UserManagement\Index as UserManagementIndex;
use Bale\Core\Livewire\Pages\UserManagement\Create;
use Bale\Core\Livewire\Pages\UserManagement\Edit;
use Bale\Core\Livewire\Pages\PermissionManagement\Index as PermissionIndex;
use Bale\Core\Livewire\Pages\PermissionManagement\Create as PermissionCreate;
use Bale\Core\Livewire\Pages\PermissionManagement\Edit as PermissionEdit;
use Bale\Core\Livewire\Pages\RoleManagement\Index as RoleIndex;
use Bale\Core\Livewire\Pages\RoleManagement\Create as RoleCreate;
use Bale\Core\Livewire\Pages\RoleManagement\Edit as RoleEdit;
use Bale\Core\Livewire\Pages\AuthenticationLog\Index as AuthLogIndex;
use Bale\Core\Livewire\Pages\AuthenticationLog\Edit as AuthLogEdit;

use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'web'
])->group(function () {
    Route::get('/dashboard', [DashboardSelector::class, 'resolve'])->name('dashboard');
});

// User Management - restricted to users with 'user management' permission
Route::middleware(['auth', 'web', 'permission:user-management.read'])->group(function () {
    Route::get('/user-management', UserManagementIndex::class)->name('user-management');
    Route::get('/user-management/create', Create::class)->name('user-management.create');
    Route::get('/user-management/{id}/edit', Edit::class)->name('user-management.edit');
});

// Permission Management
Route::middleware(['auth', 'web', 'permission:permission.read'])->group(function () {
    Route::get('/permissions', PermissionIndex::class)->name('permission.index');
    Route::get('/permissions/create', PermissionCreate::class)->name('permission.create');
    Route::get('/permissions/{id}/edit', PermissionEdit::class)->name('permission.edit');
});

// Role Management
Route::middleware(['auth', 'web', 'permission:role.read'])->group(function () {
    Route::get('/roles', RoleIndex::class)->name('role.index');
    Route::get('/roles/create', RoleCreate::class)->name('role.create');
    Route::get('/roles/{id}/edit', RoleEdit::class)->name('role.edit');
});

// Authentication Log
Route::middleware(['auth', 'web', 'permission:authentication-log.read'])->group(function () {
    Route::get('/authentication-logs', AuthLogIndex::class)->name('authentication-log');
    Route::get('/authentication-logs/{id}/edit', AuthLogEdit::class)->name('authentication-log.edit');
});


Route::middleware([
    'web'
])->group(function () {
    Route::get('/media/{path}', [MediaController::class, 'show'])
        ->where('path', '.*')
        ->name('media.show');
});