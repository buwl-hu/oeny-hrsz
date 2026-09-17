<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Cache;

class ArrayCache implements CacheInterface
{
    /** @var array<string, array{value:mixed, expires_at:int|null}> */
    protected array $items = [];

    public function get(string $key): mixed
    {
        if (!isset($this->items[$key])) return null;

        $item = $this->items[$key];
        if ($item['expires_at'] !== null && $item['expires_at'] <= time()) {
            unset($this->items[$key]);
            return null;
        }

        return $item['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->items[$key] = [
            'value' => $value,
            'expires_at' => $ttl === null ? null : time() + max(0, $ttl),
        ];
    }

    public function delete(string $key): void
    {
        unset($this->items[$key]);
    }
}
