<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Cache;

interface CacheInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, ?int $ttl = null): void;

    public function delete(string $key): void;
}
