<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Repository;

use BuwlOpenAI\OenyHrsz\Cache\CacheInterface;
use BuwlOpenAI\OenyHrsz\Cache\NullCache;
use BuwlOpenAI\OenyHrsz\Entity\Locality;
use BuwlOpenAI\OenyHrsz\Http\CurlHttpClient;
use BuwlOpenAI\OenyHrsz\Http\HttpClientInterface;

class LocalityRepository
{
    public function __construct(
        protected readonly HttpClientInterface $http = new CurlHttpClient(),
        protected readonly CacheInterface      $cache = new NullCache(),
        protected readonly ?int                $cache_ttl = 3600,
        protected readonly string              $base_url = BUWL_OENY_API_LOCALITIES_ENDPOINT,
    )
    {
    }

    /** @return Locality[] */
    public function search(string $searchString): array
    {
        $key = 'localities.search.' . hash('sha256', $searchString);
        $cached = $this->cache->get($key);
        if (is_array($cached)) return $this->makeLocalities($cached);
        $data = $this->http->get($this->base_url . '/search', ['searchString' => $searchString]);
        $this->cache->set($key, $data, $this->cache_ttl);
        return $this->makeLocalities($data);
    }

    public function getBoundingBox(Locality $locality): ?array
    {
        $key = 'localities.bounding-box.' . $locality->getCode();
        $cached = $this->cache->get($key);
        if (is_array($cached)) return $cached;
        $data = $this->http->get($this->base_url . '/bounding-box', ['kshCode' => $locality->getCode()]);
        $this->cache->set($key, $data, $this->cache_ttl);
        return $data;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return Locality[]
     */
    protected function makeLocalities(array $items): array
    {
        return array_map(static fn(array $item): Locality => Locality::fromArray($item), $items);
    }
}
