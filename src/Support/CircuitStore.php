<?php

namespace AbdelAzizHassan\Authentica\Support;

interface CircuitStore
{
    public function get(string $key, mixed $default = null): mixed;
    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void;
    public function forget(string $key): void;
    public function increment(string $key, int $by = 1): int;
}
