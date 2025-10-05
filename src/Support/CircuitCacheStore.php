<?php

namespace AbdelAzizHassan\Authentica\Support;

use Illuminate\Support\Facades\Cache;

class CircuitCacheStore implements CircuitStore
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        if ($ttlSeconds && $ttlSeconds > 0) {
            Cache::put($key, $value, $ttlSeconds);
        } else {
            Cache::put($key, $value);
        }
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    public function increment(string $key, int $by = 1): int
    {
        return Cache::increment($key, $by);
    }
}
