<?php

declare(strict_types=1);

namespace Core;

/**
 * Package Auto-Discovery
 * 
 * Discovers and registers packages from composer.json.
 * Packages can define boot methods and service providers.
 * 
 * Usage in composer.json:
 *   "extra": {
 *       "intent": {
 *           "providers": ["Vendor\\Package\\ServiceProvider"]
 *       }
 *   }
 */
final class Package
{
    /** @var array<string, mixed> Discovered packages */
    private static array $packages = [];

    /** @var bool Whether discovery has run */
    private static bool $discovered = false;

    /**
     * Discover and boot all packages.
     */
    public static function boot(): void
    {
        if (self::$discovered) {
            return;
        }

        self::discover();
        self::bootPackages();
        self::$discovered = true;
    }

    /**
     * Discover packages from composer.json files.
     */
    private static function discover(): void
    {
        $composerPath = (defined('BASE_PATH') ? BASE_PATH : getcwd()) . '/vendor/composer/installed.json';

        if (!file_exists($composerPath)) {
            return;
        }

        $installed = json_decode(file_get_contents($composerPath), true);
        
        // Handle both Composer 1.x and 2.x formats
        $packages = $installed['packages'] ?? $installed;

        if (!is_array($packages)) {
            return;
        }

        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }

            $extra = $package['extra']['intent'] ?? null;
            
            if ($extra !== null) {
                self::$packages[$package['name']] = [
                    'providers' => $extra['providers'] ?? [],
                    'aliases' => $extra['aliases'] ?? [],
                    'config' => $extra['config'] ?? [],
                ];
            }
        }
    }

    /**
     * Boot discovered packages.
     */
    private static function bootPackages(): void
    {
        foreach (self::$packages as $name => $config) {
            self::bootProviders($config['providers'] ?? []);
        }
    }

    /**
     * Boot service providers.
     */
    private static function bootProviders(array $providers): void
    {
        foreach ($providers as $providerClass) {
            if (!class_exists($providerClass)) {
                continue;
            }

            $provider = new $providerClass();

            // Call register() if exists
            if (method_exists($provider, 'register')) {
                $provider->register();
            }

            // Call boot() if exists
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    /**
     * Get all discovered packages.
     */
    public static function all(): array
    {
        return self::$packages;
    }

    /**
     * Check if a package is discovered.
     */
    public static function has(string $name): bool
    {
        return isset(self::$packages[$name]);
    }

    /**
     * Get a specific package's configuration.
     */
    public static function get(string $name): ?array
    {
        return self::$packages[$name] ?? null;
    }

    /**
     * Manually register a package provider.
     */
    public static function register(string $providerClass): void
    {
        self::bootProviders([$providerClass]);
    }

    /**
     * Reset discovered packages (for testing).
     */
    public static function reset(): void
    {
        self::$packages = [];
        self::$discovered = false;
    }
}
