<?php

namespace AbdelAzizHassan\Authentica\Support;

class CircuitArrayStore implements CircuitStore
{
    /** @var array<string,mixed> */
    protected static array $data = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, self::$data) ? self::$data[$key] : $default;
    }

    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        // TTL ignored in array store (process-local)
        self::$data[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset(self::$data[$key]);
    }

    public function increment(string $key, int $by = 1): int
    {
        $cur = (int) ($this->get($key, 0));
        $cur += $by;
        self::$data[$key] = $cur;
        return $cur;
    }
}
