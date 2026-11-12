<?php

use App\Models\User;
use Bale\Core\Models\UserProfile;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['core.index_key' => str_repeat('a', 64)]);
});

it('menyimpan phone sebagai ciphertext dan mengembalikannya dalam bentuk 62', function () {
    $profile = UserProfile::create([
        'user_uuid' => User::factory()->create()->uuid,
        'phone' => '0812-3456-7890',
    ]);

    $raw = DB::table('user_profiles')->where('id', $profile->id)->first();

    expect($raw->phone)->not->toContain('08123456789')
        ->and($profile->fresh()->phone)->toBe('6281234567890');
});

it('mencari lewat wherePhone dengan normalisasi nomor', function () {
    $user = User::factory()->create();
    UserProfile::create(['user_uuid' => $user->uuid, 'phone' => '08123456789']);

    expect(UserProfile::wherePhone('+62 812-3456-789')->first()?->user_uuid)->toBe($user->uuid)
        ->and(UserProfile::wherePhone('0812 3456 789')->first()?->user_uuid)->toBe($user->uuid)
        ->and(UserProfile::wherePhone('08129999999')->first())->toBeNull()
        ->and(UserProfile::wherePhone('')->first())->toBeNull();
});

it('mempertahankan phone lama saat profil disimpan tanpa nomor', function () {
    $user = User::factory()->create();
    $profile = UserProfile::create(['user_uuid' => $user->uuid, 'phone' => '08123456789']);

    $profile->update(['asal_instansi' => 'Dinas Pendidikan']);

    expect($profile->fresh()->phone)->toBe('628123456789')
        ->and($profile->fresh()->asal_instansi)->toBe('Dinas Pendidikan');
});

it('menyediakan relasi profile dari package tanpa method di model User', function () {
    $user = User::factory()->create();
    $profile = UserProfile::create(['user_uuid' => $user->uuid, 'phone' => '08123456789']);

    expect($user->profile->is($profile))->toBeTrue()
        ->and($user->profile()->first()->is($profile))->toBeTrue();
});
