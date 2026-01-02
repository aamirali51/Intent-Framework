<?php

declare(strict_types=1);

namespace Core;

use RuntimeException;

/**
 * OAuth 2.0 Client for Social Login.
 * 
 * Simple provider-based OAuth without heavy dependencies.
 * Supports: Google, GitHub, Facebook.
 * 
 * Usage:
 *   // In login controller
 *   return redirect(OAuth::redirect('google'));
 * 
 *   // In callback controller
 *   $userData = OAuth::callback('google', $request->get('code'));
 *   // Returns: ['id', 'email', 'name', 'avatar', 'provider', 'raw']
 */
final class OAuth
{
    /**
     * Provider configurations (endpoints, scopes).
     */
    private const PROVIDERS = [
        'google' => [
            'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'user_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
            'scope' => 'openid email profile',
        ],
        'github' => [
            'auth_url' => 'https://github.com/login/oauth/authorize',
            'token_url' => 'https://github.com/login/oauth/access_token',
            'user_url' => 'https://api.github.com/user',
            'scope' => 'user:email',
        ],
        'facebook' => [
            'auth_url' => 'https://www.facebook.com/v18.0/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/v18.0/oauth/access_token',
            'user_url' => 'https://graph.facebook.com/v18.0/me?fields=id,name,email,picture',
            'scope' => 'email,public_profile',
        ],
    ];

    /**
     * Generate OAuth authorization URL.
     * 
     * @param string $provider Provider name (google, github, facebook)
     * @param array<string, string> $options Override scope, state, etc.
     * @return string Full authorization URL to redirect user to
     */
    public static function redirect(string $provider, array $options = []): string
    {
        $config = self::getProviderConfig($provider);
        $providerInfo = self::PROVIDERS[$provider];
        
        // Generate and store state for CSRF protection
        $state = bin2hex(random_bytes(16));
        Session::set('oauth_state', $state);
        Session::set('oauth_provider', $provider);
        
        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => self::getRedirectUri($config),
            'response_type' => 'code',
            'scope' => $options['scope'] ?? $providerInfo['scope'],
            'state' => $state,
        ];
        
        // Provider-specific parameters
        if ($provider === 'google') {
            $params['access_type'] = 'offline';
            $params['prompt'] = 'select_account';
        }
        
        return $providerInfo['auth_url'] . '?' . http_build_query($params);
    }

    /**
     * Handle OAuth callback and fetch user data.
     * 
     * @param string $provider Provider name
     * @param string $code Authorization code from callback
     * @param string|null $state State parameter for CSRF verification
     * @return array{id: string, email: string|null, name: string|null, avatar: string|null, provider: string, raw: array}
     * @throws RuntimeException If OAuth fails
     */
    public static function callback(string $provider, string $code, ?string $state = null): array
    {
        // Verify state (CSRF protection)
        $storedState = Session::get('oauth_state');
        if ($state !== null && $state !== $storedState) {
            throw new RuntimeException('Invalid OAuth state parameter');
        }
        
        // Clear state
        Session::forget('oauth_state');
        Session::forget('oauth_provider');
        
        // Exchange code for access token
        $accessToken = self::exchangeCode($provider, $code);
        
        // Fetch user profile
        return self::fetchUser($provider, $accessToken);
    }

    /**
     * Get supported providers list.
     * 
     * @return string[]
     */
    public static function providers(): array
    {
        return array_keys(self::PROVIDERS);
    }

    /**
     * Check if a provider is configured.
     */
    public static function hasProvider(string $provider): bool
    {
        try {
            self::getProviderConfig($provider);
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Internal Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Exchange authorization code for access token.
     */
    private static function exchangeCode(string $provider, string $code): string
    {
        $config = self::getProviderConfig($provider);
        $providerInfo = self::PROVIDERS[$provider];
        
        $params = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => self::getRedirectUri($config),
            'grant_type' => 'authorization_code',
        ];
        
        $response = self::httpPost($providerInfo['token_url'], $params, [
            'Accept: application/json',
        ]);
        
        $data = json_decode($response, true);
        
        if (!is_array($data) || !isset($data['access_token'])) {
            $error = $data['error'] ?? $data['error_description'] ?? 'Unknown error';
            throw new RuntimeException("OAuth token exchange failed: {$error}");
        }
        
        return $data['access_token'];
    }

    /**
     * Fetch user profile from provider.
     * 
     * @return array{id: string, email: string|null, name: string|null, avatar: string|null, provider: string, raw: array}
     */
    private static function fetchUser(string $provider, string $accessToken): array
    {
        $providerInfo = self::PROVIDERS[$provider];
        
        $response = self::httpGet($providerInfo['user_url'], [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            'User-Agent: Intent-Framework/1.0',
        ]);
        
        $raw = json_decode($response, true);
        
        if (!is_array($raw)) {
            throw new RuntimeException('Failed to parse user data from provider');
        }
        
        // Normalize user data across providers
        return self::normalizeUser($provider, $raw);
    }

    /**
     * Normalize user data from different providers.
     * 
     * @return array{id: string, email: string|null, name: string|null, avatar: string|null, provider: string, raw: array}
     */
    private static function normalizeUser(string $provider, array $raw): array
    {
        return match ($provider) {
            'google' => [
                'id' => (string) ($raw['id'] ?? ''),
                'email' => $raw['email'] ?? null,
                'name' => $raw['name'] ?? null,
                'avatar' => $raw['picture'] ?? null,
                'provider' => 'google',
                'raw' => $raw,
            ],
            'github' => [
                'id' => (string) ($raw['id'] ?? ''),
                'email' => $raw['email'] ?? null,
                'name' => $raw['name'] ?? $raw['login'] ?? null,
                'avatar' => $raw['avatar_url'] ?? null,
                'provider' => 'github',
                'raw' => $raw,
            ],
            'facebook' => [
                'id' => (string) ($raw['id'] ?? ''),
                'email' => $raw['email'] ?? null,
                'name' => $raw['name'] ?? null,
                'avatar' => $raw['picture']['data']['url'] ?? null,
                'provider' => 'facebook',
                'raw' => $raw,
            ],
            default => [
                'id' => (string) ($raw['id'] ?? $raw['sub'] ?? ''),
                'email' => $raw['email'] ?? null,
                'name' => $raw['name'] ?? null,
                'avatar' => $raw['picture'] ?? $raw['avatar_url'] ?? null,
                'provider' => $provider,
                'raw' => $raw,
            ],
        };
    }

    /**
     * Get provider configuration from config file.
     * 
     * @return array{client_id: string, client_secret: string, redirect?: string}
     */
    private static function getProviderConfig(string $provider): array
    {
        if (!isset(self::PROVIDERS[$provider])) {
            throw new RuntimeException("Unsupported OAuth provider: {$provider}");
        }
        
        $config = Config::get("auth.oauth.{$provider}");
        
        if (!is_array($config) || empty($config['client_id']) || empty($config['client_secret'])) {
            throw new RuntimeException(
                "OAuth provider '{$provider}' not configured. " .
                "Add client_id and client_secret to config/auth.php"
            );
        }
        
        return $config;
    }

    /**
     * Get redirect URI for a provider.
     */
    private static function getRedirectUri(array $config): string
    {
        if (isset($config['redirect'])) {
            // If it's a relative path, make it absolute
            $redirect = $config['redirect'];
            if (str_starts_with($redirect, '/')) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                return "{$scheme}://{$host}{$redirect}";
            }
            return $redirect;
        }
        
        // Default: current host + /auth/{provider}/callback
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $provider = Session::get('oauth_provider', 'oauth');
        
        return "{$scheme}://{$host}/auth/{$provider}/callback";
    }

    /**
     * Make HTTP POST request.
     */
    private static function httpPost(string $url, array $data, array $headers = []): string
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/x-www-form-urlencoded',
            ], $headers),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new RuntimeException("HTTP request failed: {$error}");
        }
        
        return $response;
    }

    /**
     * Make HTTP GET request.
     */
    private static function httpGet(string $url, array $headers = []): string
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new RuntimeException("HTTP request failed: {$error}");
        }
        
        return $response;
    }
}
