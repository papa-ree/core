<?php

/*
 | ---------------------------------------------------------------------------
 | Pembatasan IP halaman form login (`entrance.gate`)
 | ---------------------------------------------------------------------------
 |
 | Menguji middleware `entrance_gate` (RestrictEntranceGate) dan halaman
 | konfigurasi "Login Allowlist".
 |
 | Catatan: request test default memakai REMOTE_ADDR 127.0.0.1 yang SELALU
 | diizinkan saat fitur aktif (jalur darurat anti-lockout). Untuk menguji
 | penolakan, IP disimulasikan lewat `withServerVariables(['REMOTE_ADDR' => ...])`.
 |
 */

use App\Models\User;
use Bale\Core\Livewire\Pages\EntranceGate\Form as EntranceGateForm;
use Bale\Core\Livewire\Pages\EntranceGate\Section\EntranceGateTable;
use Bale\Core\Models\EntranceGateAllowlist;
use Bale\Core\Services\EntranceGateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'core.entrance_gate.enabled' => false,
        'core.entrance_gate.allow' => '',
    ]);

    foreach (['read', 'create', 'update', 'delete'] as $action) {
        Permission::firstOrCreate(['name' => "entrance-gate.{$action}", 'guard_name' => 'web']);
    }
});

function entranceGateEntry(string $ip, bool $active = true): EntranceGateAllowlist
{
    return EntranceGateAllowlist::create([
        'ip_address' => $ip,
        'label' => null,
        'is_active' => $active,
    ]);
}

function entranceGateAllowedUser(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('entrance-gate.read');

    return $user;
}

it('mengizinkan form login saat fitur nonaktif', function () {
    $this->get('/entrance.gate')->assertStatus(200);
});

it('fail-open saat fitur aktif tetapi belum ada entri', function () {
    config(['core.entrance_gate.enabled' => true]);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->get('/entrance.gate')
        ->assertStatus(200);
});

it('mengizinkan IP yang terdaftar', function () {
    config(['core.entrance_gate.enabled' => true]);
    entranceGateEntry('203.0.113.9');

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->get('/entrance.gate')
        ->assertStatus(200);
});

it('menolak dengan 404 IP di luar allowlist', function () {
    config(['core.entrance_gate.enabled' => true]);
    entranceGateEntry('198.51.100.7');

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->get('/entrance.gate')
        ->assertStatus(404);
});

it('selalu mengizinkan localhost saat fitur aktif', function () {
    config(['core.entrance_gate.enabled' => true]);
    entranceGateEntry('198.51.100.7');

    // REMOTE_ADDR default test = 127.0.0.1
    $this->get('/entrance.gate')->assertStatus(200);
});

it('mencocokkan entri CIDR', function () {
    config(['core.entrance_gate.enabled' => true]);
    entranceGateEntry('10.0.0.0/8');

    $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
        ->get('/entrance.gate')->assertStatus(200);

    $this->withServerVariables(['REMOTE_ADDR' => '11.1.2.3'])
        ->get('/entrance.gate')->assertStatus(404);
});

it('mengizinkan IP dari daftar statis env', function () {
    config([
        'core.entrance_gate.enabled' => true,
        'core.entrance_gate.allow' => '203.0.113.9',
    ]);
    entranceGateEntry('198.51.100.7');

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->get('/entrance.gate')->assertStatus(200);
});

it('mengabaikan entri yang nonaktif', function () {
    config(['core.entrance_gate.enabled' => true]);
    entranceGateEntry('198.51.100.7', active: true);
    entranceGateEntry('203.0.113.9', active: false);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->get('/entrance.gate')->assertStatus(404);
});

it('membatasi POST login juga', function () {
    config(['core.entrance_gate.enabled' => true]);
    entranceGateEntry('198.51.100.7');

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->post('/entrance.gate', [])
        ->assertStatus(404);
});

it('membatasi halaman lockout', function () {
    config(['core.entrance_gate.enabled' => true]);
    entranceGateEntry('198.51.100.7');

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->get('/entrance.gate/blocked')
        ->assertStatus(404);
});

it('memvalidasi format entri IP/CIDR', function () {
    $service = app(EntranceGateService::class);

    expect($service->isValidEntry('192.168.1.10'))->toBeTrue()
        ->and($service->isValidEntry('10.0.0.0/8'))->toBeTrue()
        ->and($service->isValidEntry('::1'))->toBeTrue()
        ->and($service->isValidEntry('2001:db8::/32'))->toBeTrue()
        ->and($service->isValidEntry('999.1.1.1'))->toBeFalse()
        ->and($service->isValidEntry('10.0.0.0/99'))->toBeFalse()
        ->and($service->isValidEntry('bukan-ip'))->toBeFalse()
        ->and($service->isValidEntry(''))->toBeFalse();
});

it('menampilkan halaman konfigurasi untuk user berizin', function () {
    $this->actingAs(entranceGateAllowedUser());

    $this->get(route('entrance-gate'))->assertStatus(200);
});

it('menolak user tanpa izin dari halaman konfigurasi', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('entrance-gate'))->assertStatus(403);
});

it('menambah entri baru lewat form', function () {
    $user = entranceGateAllowedUser();
    $user->givePermissionTo('entrance-gate.create');
    Livewire::actingAs($user);

    Livewire::test(EntranceGateForm::class)
        ->set('ip_address', '192.168.1.10')
        ->set('label', 'Kantor Pusat')
        ->call('save')
        ->assertHasNoErrors();

    expect(EntranceGateAllowlist::where('ip_address', '192.168.1.10')->exists())->toBeTrue();
});

it('menolak entri IP yang tidak valid lewat form', function () {
    $user = entranceGateAllowedUser();
    $user->givePermissionTo('entrance-gate.create');
    Livewire::actingAs($user);

    Livewire::test(EntranceGateForm::class)
        ->set('ip_address', 'bukan-ip')
        ->call('save')
        ->assertHasErrors(['ip_address']);
});

it('menolak entri IP duplikat lewat form', function () {
    entranceGateEntry('192.168.1.10');

    $user = entranceGateAllowedUser();
    $user->givePermissionTo('entrance-gate.create');
    Livewire::actingAs($user);

    Livewire::test(EntranceGateForm::class)
        ->set('ip_address', '192.168.1.10')
        ->call('save')
        ->assertHasErrors(['ip_address']);
});

it('menghapus entri lewat tabel', function () {
    $entry = entranceGateEntry('192.168.1.10');

    $user = entranceGateAllowedUser();
    $user->givePermissionTo('entrance-gate.delete');
    Livewire::actingAs($user);

    Livewire::test(EntranceGateTable::class)
        ->call('deleteEntranceGateIp', $entry->id)
        ->assertHasNoErrors();

    expect(EntranceGateAllowlist::find($entry->id))->toBeNull();
});

/*
 | ---------------------------------------------------------------------------
 | Trusted proxy + CF-Connecting-IP (Cloudflare Tunnel)
 | ---------------------------------------------------------------------------
 */

it('memuat rentang jaringan internal sebagai trusted proxy, bukan "*"', function () {
    $proxies = config('trustedproxy.proxies');

    expect($proxies)->toBeArray()
        ->toContain('172.16.0.0/12')
        ->toContain('127.0.0.1')
        ->not->toContain('*');
});

it('memakai CF-Connecting-IP bila request berasal dari proxy terpercaya', function () {
    config(['core.entrance_gate.enabled' => true]);
    entranceGateEntry('203.0.113.9');

    // REMOTE_ADDR = Traefik (jaringan internal) → CF-Connecting-IP dipercaya
    // sebagai IP klien sebenarnya (masuk allowlist) → 200.
    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
        ->withHeaders(['CF-Connecting-IP' => '203.0.113.9'])
        ->get('/entrance.gate')
        ->assertStatus(200);
});

it('mengabaikan CF-Connecting-IP dari peer yang tidak terpercaya', function () {
    config(['core.entrance_gate.enabled' => true]);
    entranceGateEntry('203.0.113.9');

    // Peer publik (bukan proxy internal) → header diabaikan; IP klien tetap
    // REMOTE_ADDR (di luar allowlist) → 404.
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.50'])
        ->withHeaders(['CF-Connecting-IP' => '203.0.113.9'])
        ->get('/entrance.gate')
        ->assertStatus(404);
});

it('mengembalikan IP klien asli & tetap mendeteksi HTTPS dari proxy terpercaya', function () {
    Route::get('/__test-ip', fn (Request $request) => response()->json([
        'ip' => $request->ip(),
        'secure' => $request->isSecure(),
    ]));

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
        ->withHeaders([
            'CF-Connecting-IP' => '203.0.113.9',
            'X-Forwarded-Proto' => 'https',
        ])
        ->get('/__test-ip')
        ->assertJson([
            'ip' => '203.0.113.9',
            'secure' => true,
        ]);
});

it('tidak mengizinkan localhost bila allow_localhost dimatikan', function () {
    config([
        'core.entrance_gate.enabled' => true,
        'core.entrance_gate.allow_localhost' => false,
    ]);
    entranceGateEntry('198.51.100.7');

    $this->get('/entrance.gate')->assertStatus(404);
});
