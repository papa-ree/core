<?php

namespace Bale\Core\Tests\Feature\Auth;

use App\Models\User;
use Lunaweb\RecaptchaV3\Facades\RecaptchaV3;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    // Mocking reCAPTCHA agar selalu sukses dalam pengetesan
    RecaptchaV3::shouldReceive('verify')
        ->andReturn(1.0); // Skor sempurna
    RecaptchaV3::shouldReceive('initJs')
        ->andReturn('<script>/* mock recaptcha */</script>');
    RecaptchaV3::shouldReceive('field')
        ->andReturn('<input type="hidden" name="g-recaptcha-response" value="test-token">');
});

it('menampilkan halaman login pada url entrance.gate', function () {
    $this->get('/entrance.gate')
        ->assertStatus(200)
        ->assertSee('Masuk ke Akun Anda')
        ->assertSee('username')
        ->assertSee('password');
});

it('gagal login dengan kredensial yang salah', function () {
    $this->from('/entrance.gate')
        ->post('/entrance.gate', [
            'username' => 'wronguser',
            'password' => 'wrongpassword',
            'g-recaptcha-response' => 'test-token',
        ])
        ->assertRedirect('/entrance.gate')
        ->assertSessionHasErrors(['username']);

    $this->assertFalse(Auth::check());
});

it('berhasil login dengan kredensial yang benar dan diarahkan ke dashboard', function () {
    $user = User::factory()->create([
        'password' => bcrypt($password = 'P@ssword123'),
    ]);

    $this->post('/entrance.gate', [
        'username' => $user->username,
        'password' => $password,
        'g-recaptcha-response' => 'test-token',
    ])
    ->assertRedirect(route('dashboard'));

    $this->assertTrue(Auth::check());
    $this->assertEquals($user->id, Auth::id());
});
