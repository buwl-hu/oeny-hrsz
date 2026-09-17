<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Repository;

use BuwlOpenAI\OenyHrsz\Cache\CacheInterface;
use BuwlOpenAI\OenyHrsz\Cache\NullCache;
use BuwlOpenAI\OenyHrsz\Entity\Address;
use BuwlOpenAI\OenyHrsz\Entity\SubParcel;
use BuwlOpenAI\OenyHrsz\Http\CurlHttpClient;
use BuwlOpenAI\OenyHrsz\Http\HttpClientInterface;

class AddressRepository
{
    public function __construct(
        protected readonly HttpClientInterface $http = new CurlHttpClient(),
        protected readonly CacheInterface      $cache = new NullCache(),
        protected readonly ?int                $cache_ttl = 3600,
        protected readonly string              $base_url = BUWL_OENY_API_ADDRESSES_ENDPOINT,
    )
    {
    }

    /** @return Address[] */
    public function search(string $locality_code, string $search_string): array
    {
        $key = 'addresses.search.' . hash('sha256', $locality_code . "\0" . $search_string);
        $cached = $this->cache->get($key);
        if (is_array($cached)) return $this->makeAddresses($cached);
        $data = $this->http->get($this->base_url . '/search', ['kshCode' => $locality_code, 'searchString' => $search_string]);
        $this->cache->set($key, $data, $this->cache_ttl);
        return $this->makeAddresses($data);
    }

    public function hydrate(Address $address): void
    {
        $key = 'addresses.position.' . $address->getId();
        $data = $this->cache->get($key);
        if (!is_array($data)) {
            $data = $this->http->get($this->base_url . '/position', ['id' => $address->getId()]);
            $this->cache->set($key, $data, $this->cache_ttl);
        }
        $address->hydrate($data, $this);
    }

    /**
     * @return SubParcel[]
     */
    public function getSubParcels(int $id): array
    {
        $key = 'addresses.sub-parcels.' . $id;
        $cached = $this->cache->get($key);
        if (is_array($cached)) return self::makeSubParcels($cached);
        $data = $this->http->get($this->base_url . '/sub-parcels', ['id' => $id]);
        $this->cache->set($key, $data, $this->cache_ttl);
        return self::makeSubParcels($data);
    }

    /** @return string[] */
    public function getFloors(int $id): array
    {
        $key = 'addresses.floors.' . $id;
        $cached = $this->cache->get($key);
        if (is_array($cached)) return array_values(array_filter(array_map(fn($item) => $item['floor'] ?? null, $cached), 'is_string'));
        $data = $this->http->get($this->base_url . '/floors', ['id' => $id]);
        $this->cache->set($key, $data, $this->cache_ttl);
        return array_values(array_filter(array_map(fn($item) => $item['floor'] ?? null, $data), 'is_string'));
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return Address[]
     */
    protected function makeAddresses(array $items): array
    {
        $repo = $this;
        return array_map(static fn(array $item): Address => Address::fromArray($item, $repo), $items);
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return SubParcel[]
     */
    protected function makeSubParcels(array $items): array
    {
        return array_map(static fn(array $item): SubParcel => SubParcel::fromArray($item), $items);
    }
}
