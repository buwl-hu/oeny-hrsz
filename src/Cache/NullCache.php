<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Cache;

class NullCache implements CacheInterface
{
    public function get(string $key): mixed
    {
        return null;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
    }

    public function delete(string $key): void
    {
    }
}
