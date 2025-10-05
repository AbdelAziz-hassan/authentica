<?php

namespace AbdelAzizHassan\Authentica\Support;

use InvalidArgumentException;

class CircuitStoreFactory
{
    public static function make(array $cfg): CircuitStore
    {
        $driver = $cfg['driver'] ?? 'cache';

        return match ($driver) {
            'array'  => new CircuitArrayStore(),
            'cache'  => new CircuitCacheStore(),
            'custom' => self::makeCustom($cfg['store'] ?? null),
            default  => throw new InvalidArgumentException("Unknown circuit driver: {$driver}"),
        };
    }

    protected static function makeCustom(?string $class): CircuitStore
    {
        if (!$class || !class_exists($class)) {
            throw new InvalidArgumentException('Custom circuit store class not found');
        }
        $store = app($class);
        if (!$store instanceof CircuitStore) {
            throw new InvalidArgumentException('Custom circuit store must implement CircuitStore');
        }
        return $store;
    }
}
