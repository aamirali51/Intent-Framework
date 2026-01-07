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
    /**
     * Handle OAuth callback and fetch user data.
     * 
     * @param string $provider Provider name
     * @param string $code Authorization code from callback
     * @param string|null $state State parameter for CSRF verification
     * @return array{id: string, email: string|null, name: string|null, avatar: string|null, provider: string, raw: array<string, mixed>}
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
            $err = 'Unknown error';
            if (is_array($data)) {
                $err = $data['error'] ?? $data['error_description'] ?? 'Unknown error';
            }
            /** @var string $error */
            $error = is_scalar($err) ? (string) $err : 'Unknown error';
            throw new RuntimeException("OAuth token exchange failed: {$error}");
        }
        
        /** @var string $accessToken */
        $accessToken = $data['access_token'];
        return $accessToken;
    }

    /**
     * Fetch user profile from provider.
     * 
     * @return array{id: string, email: string|null, name: string|null, avatar: string|null, provider: string, raw: array<string, mixed>}
     */
    private static function fetchUser(string $provider, string $accessToken): array
    {
        $providerInfo = self::PROVIDERS[$provider];
        
        $response = self::httpGet($providerInfo['user_url'], [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            'User-Agent: Intent-Framework/1.0',
        ]);
        
        /** @var array<string, mixed>|null $raw */
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
     * @param array<string, mixed> $raw
     * @return array{id: string, email: string|null, name: string|null, avatar: string|null, provider: string, raw: array<string, mixed>}
     */
    private static function normalizeUser(string $provider, array $raw): array
    {
        return match ($provider) {
            'google' => [
                'id' => is_scalar($raw['id'] ?? '') ? (string) ($raw['id'] ?? '') : '',
                'email' => is_string($raw['email'] ?? null) ? $raw['email'] : null,
                'name' => is_string($raw['name'] ?? null) ? $raw['name'] : null,
                'avatar' => is_string($raw['picture'] ?? null) ? $raw['picture'] : null,
                'provider' => 'google',
                'raw' => $raw,
            ],
            'github' => [
                'id' => is_scalar($raw['id'] ?? '') ? (string) ($raw['id'] ?? '') : '',
                'email' => is_string($raw['email'] ?? null) ? $raw['email'] : null,
                'name' => isset($raw['name']) && is_string($raw['name']) ? $raw['name'] : (isset($raw['login']) && is_string($raw['login']) ? $raw['login'] : null),
                'avatar' => is_string($raw['avatar_url'] ?? null) ? $raw['avatar_url'] : null,
                'provider' => 'github',
                'raw' => $raw,
            ],
            'facebook' => [
                'id' => is_scalar($raw['id'] ?? '') ? (string) ($raw['id'] ?? '') : '',
                'email' => is_string($raw['email'] ?? null) ? $raw['email'] : null,
                'name' => is_string($raw['name'] ?? null) ? $raw['name'] : null,
                'avatar' => isset($raw['picture']) && is_array($raw['picture']) && isset($raw['picture']['data']) && is_array($raw['picture']['data']) && isset($raw['picture']['data']['url']) && is_string($raw['picture']['data']['url']) ? $raw['picture']['data']['url'] : null,
                'provider' => 'facebook',
                'raw' => $raw,
            ],
            default => [
                'id' => is_scalar($raw['id'] ?? $raw['sub'] ?? '') ? (string) ($raw['id'] ?? $raw['sub'] ?? '') : '',
                'email' => is_string($raw['email'] ?? null) ? $raw['email'] : null,
                'name' => is_string($raw['name'] ?? null) ? $raw['name'] : null,
                'avatar' => isset($raw['picture']) && is_string($raw['picture']) ? $raw['picture'] : (isset($raw['avatar_url']) && is_string($raw['avatar_url']) ? $raw['avatar_url'] : null),
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
        
        /** @var array{client_id: string, client_secret: string, redirect?: string} $typedConfig */
        $typedConfig = $config;
        return $typedConfig;
    }

    /**
     * Get redirect URI for a provider.
     * 
     * @param array{client_id: string, client_secret: string, redirect?: string} $config
     */
    private static function getRedirectUri(array $config): string
    {
        if (isset($config['redirect'])) {
            // If it's a relative path, make it absolute
            $redirect = $config['redirect'];
            if (is_string($redirect) && str_starts_with($redirect, '/')) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                /** @var string $host */
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                return "{$scheme}://{$host}{$redirect}";
            }
            return is_string($redirect) ? $redirect : '';
        }
        
        // Default: current host + /auth/{provider}/callback
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        /** @var string $host */
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        /** @var string $provider */
        $provider = Session::get('oauth_provider', 'oauth') ?? 'oauth';
        
        return "{$scheme}://{$host}/auth/{$provider}/callback";
    }

    /**
     * Make HTTP POST request.
     * 
     * @param array<string, string> $data
     * @param array<int, string> $headers
     */
    private static function httpPost(string $url, array $data, array $headers = []): string
    {
        if ($url === '') {
            throw new RuntimeException('HTTP request URL cannot be empty');
        }
        
        $ch = curl_init();
        
        /** @var array<int, mixed> $curlOpts */
        $curlOpts = [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/x-www-form-urlencoded',
            ], $headers),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ];
        curl_setopt_array($ch, $curlOpts);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!is_string($response)) {
            throw new RuntimeException("HTTP request failed: {$error}");
        }
        
        return $response;
    }

    /**
     * Make HTTP GET request.
     * 
     * @param array<int, string> $headers
     */
    private static function httpGet(string $url, array $headers = []): string
    {
        if ($url === '') {
            throw new RuntimeException('HTTP request URL cannot be empty');
        }
        
        $ch = curl_init();
        
        /** @var array<int, mixed> $curlOpts */
        $curlOpts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ];
        curl_setopt_array($ch, $curlOpts);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!is_string($response)) {
            throw new RuntimeException("HTTP request failed: {$error}");
        }
        
        return $response;
    }
}
