<?php

namespace Bale\Core\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class KeycloakService
{
    protected $baseUrl;
    protected $realm;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->baseUrl = env('KEYCLOAK_BASE_URL');
        $this->realm = env('KEYCLOAK_REALM');
        $this->clientId = env('KEYCLOAK_CLIENT_ID');
        $this->clientSecret = env('KEYCLOAK_CLIENT_SECRET');
    }

    /**
     * Ambil access token admin Keycloak
     */
    protected function getAdminToken()
    {
        return Cache::remember('keycloak_admin_token', 55, function () {
            $response = Http::asForm()->post("{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->failed()) {
                throw new \Exception('Gagal mendapatkan admin token dari Keycloak');
            }

            return $response->json()['access_token'];
        });
    }

    /**
     * Get user detail by userId
     */
    public function getUser($userId)
    {
        $token = $this->getAdminToken();

        $response = Http::withToken($token)->get("{$this->baseUrl}/admin/realms/{$this->realm}/users/{$userId}");

        if ($response->failed()) {
            throw new \Exception('Gagal mengambil data user dari Keycloak');
        }

        return $response->json();
    }

    /**
     * Get user by username
     */
    public function getUserByUsername(string $username)
    {
        $token = $this->getAdminToken();

        // Gunakan parameter search agar fleksibel (tidak case-sensitive)
        $response = Http::withToken($token)->get("{$this->baseUrl}/admin/realms/{$this->realm}/users", [
            'username' => $username,
            'exact' => true, // hanya user dengan username persis sama
        ]);

        if ($response->failed()) {
            throw new \Exception('Gagal mencari user berdasarkan username di Keycloak');
        }

        $users = $response->json();

        // Keycloak bisa mengembalikan array kosong jika user tidak ditemukan
        if (empty($users)) {
            return null;
        }

        // Ambil user pertama karena username seharusnya unik
        return $users[0];
    }

    /**
     * Get WhatsApp Number dari user profile
     */
    public function getWhatsappNumber($userId)
    {
        $user = $this->getUser($userId);

        return $user['attributes']['whatsapp_number'][0] ?? null;
    }

    /**
     * Update WhatsApp Number user
     */
    public function updateWhatsappNumber($userId, $whatsappNumber)
    {
        $token = $this->getAdminToken();

        // Ambil data user saat ini
        $userResponse = Http::withToken($token)
            ->get("{$this->baseUrl}/admin/realms/{$this->realm}/users/{$userId}");

        if ($userResponse->failed()) {
            throw new \Exception('Gagal mengambil data user dari Keycloak');
        }

        $userData = $userResponse->json();

        // Pastikan attributes tidak null
        $attributes = $userData['attributes'] ?? [];

        // Update / tambah whatsapp_number
        $attributes['whatsapp_number'] = [$whatsappNumber];
        $attributes['whatsapp_verified_at'] = [now()->toDateTimeString()];

        // Merge kembali ke data user
        $userData['attributes'] = $attributes;

        // Kirim update ke Keycloak
        $response = Http::withToken($token)
            ->put("{$this->baseUrl}/admin/realms/{$this->realm}/users/{$userId}", $userData);

        if ($response->failed()) {
            throw new \Exception('Gagal update nomor WhatsApp di Keycloak');
        }

        return true;
    }

    /**
     * Cek status login user berdasarkan access token 
     */
    public function checkLoginStatus(string $accessToken): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token/introspect", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'token' => $accessToken,
        ]);

        if ($response->failed()) {
            throw new \Exception('Gagal cek status login di Keycloak');
        }

        return $response->json();
    }

    /**
     * Reset OTP (MFA) user di Keycloak
     */
    public function resetOtp(string $userId): bool
    {
        $token = $this->getAdminToken();

        // Ambil semua credential user
        $response = Http::withToken($token)->get("{$this->baseUrl}/admin/realms/{$this->realm}/users/{$userId}/credentials");

        if ($response->failed()) {
            throw new \Exception('Gagal mengambil credential user');
        }

        $credentials = $response->json();

        // Cari credential bertipe OTP (totp atau hotp)
        foreach ($credentials as $credential) {
            if (isset($credential['type']) && $credential['type'] === 'otp') {
                $credentialId = $credential['id'];

                // Hapus credential OTP
                $delete = Http::withToken($token)->delete("{$this->baseUrl}/admin/realms/{$this->realm}/users/{$userId}/credentials/{$credentialId}");

                if ($delete->failed()) {
                    throw new \Exception('Gagal reset OTP untuk user');
                }
            }
        }

        return true;
    }

    /* logout keycloak url */
    public function logout()
    {
        $keycloakIdToken = Session::get('keycloak_id_token');
        if (!$keycloakIdToken)
            return;

        // The URL the user is redirected to after logout.
        $redirectUri = Config::get('app.url');
        $url = Socialite::driver('keycloak')->getLogoutUrl();
        $params = [
            'id_token_hint' => $keycloakIdToken, // Ambil id_token dari session
            'post_logout_redirect_uri' => $redirectUri, // URL redirect setelah logout
        ];

        $url .= '?' . http_build_query($params);

        return $url;
    }
}
