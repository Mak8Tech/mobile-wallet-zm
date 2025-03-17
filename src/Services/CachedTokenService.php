<?php

namespace Mak8Tech\MobileWalletZm\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CachedTokenService
{
    /**
     * The cache store instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The token refresh callback.
     *
     * @var callable
     */
    protected $refreshCallback;

    /**
     * The token key prefix.
     *
     * @var string
     */
    protected $keyPrefix;

    /**
     * The cache TTL in seconds.
     *
     * @var int
     */
    protected $ttl;

    /**
     * Create a new cached token service.
     *
     * @param  string  $provider
     * @param  callable  $refreshCallback
     * @param  int  $ttl
     * @return void
     */
    public function __construct(string $provider, callable $refreshCallback, int $ttl = null)
    {
        $this->cache = Cache::store(config('mobile_wallet.cache.store', 'file'));
        $this->refreshCallback = $refreshCallback;
        $this->keyPrefix = "mobile_wallet_{$provider}_token";
        $this->ttl = $ttl ?? config('mobile_wallet.cache.ttl', 3600);
    }

    /**
     * Get a token for the given key.
     *
     * @param  string  $key
     * @return string
     */
    public function getToken(string $key = 'default'): string
    {
        $cacheKey = $this->getCacheKey($key);

        if ($this->cache->has($cacheKey)) {
            Log::debug("Using cached token for {$cacheKey}");
            return $this->cache->get($cacheKey);
        }

        Log::debug("Refreshing token for {$cacheKey}");
        return $this->refreshToken($key);
    }

    /**
     * Refresh and cache a token for the given key.
     *
     * @param  string  $key
     * @return string
     */
    public function refreshToken(string $key = 'default'): string
    {
        $token = call_user_func($this->refreshCallback);

        $this->cache->put(
            $this->getCacheKey($key),
            $token,
            $this->ttl
        );

        return $token;
    }

    /**
     * Forget a cached token for the given key.
     *
     * @param  string  $key
     * @return bool
     */
    public function forgetToken(string $key = 'default'): bool
    {
        return $this->cache->forget($this->getCacheKey($key));
    }

    /**
     * Clear all cached tokens.
     *
     * @return bool
     */
    public function clearTokens(): bool
    {
        $pattern = $this->keyPrefix . '_*';
        $keys = $this->cache->getStore()->getKeys($pattern);

        foreach ($keys as $key) {
            $this->cache->forget($key);
        }

        return true;
    }

    /**
     * Get the cache key for the given token key.
     *
     * @param  string  $key
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        return "{$this->keyPrefix}_{$key}";
    }
} 