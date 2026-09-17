# OÉNY HRSZ PHP Client

Lightweight PHP 8.3+ client for the publicly accessible endpoints used by the OÉNY Helyrajziszám-kereső.

> **Important:** this package is based on observed endpoints used by the public website. OÉNY does not document these endpoints here as a public developer API. Endpoints, response formats and availability may change without notice.

## Installation

```bash
composer require buwl-hu/oeny-hrsz
```

## Basic usage

For the simplest use case, LotNumberResolver can resolve a lot number directly from a locality and address:
```php
use BuwlOpenAI\OenyHrsz\Service\LotNumberResolver;

$resolver = new LotNumberResolver();

$lotNumber = $resolver->resolve('Gyöngyös', 'Egri út 6');

echo $lotNumber; // 1900/3
```

For a sub-parcel, provide the floor and/or door number:
```php
$lotNumber = $resolver->resolve('Gyöngyös', 'Egri út 6', floor: 5, door: 34);

echo $lotNumber; // e.g. 2613/A/35
```
The resolver performs the required locality, address and sub-parcel lookups automatically.

For lower-level access to the API entities and repositories:
```php
use BuwlOpenAI\OenyHrsz\Repository\AddressRepository;
use BuwlOpenAI\OenyHrsz\Repository\LocalityRepository;

$localities = new LocalityRepository();
$addresses = new AddressRepository();

$results = $localities->search('Gyöngyös');
$locality = $results[0];

$found = $addresses->search($locality->getCode(), 'Egri út 6');
$address = $found[0];

echo $address->getAddress();

echo $address->getLotNumber(); // first call fetches /addresses/position

echo $address->getLayment();   // no second position request
```

The address search only creates lightweight `Address` objects from `id`, `districtPrefix` and `address`. Detailed parcel data is loaded lazily when needed.

## Cache

Caching is injectable and has no cache-library dependency:

```php
use BuwlOpenAI\OenyHrsz\Cache\ArrayCache;
use BuwlOpenAI\OenyHrsz\Repository\AddressRepository;

$cache = new ArrayCache();
$addresses = new AddressRepository(cache: $cache, cache_ttl: 3600);
```

Implement `CacheInterface` to connect Redis, Symfony Cache, PSR-16, WordPress transients, etc.

## Namespaces

The package uses the `BuwlOpenAI\\OenyHrsz\\` namespace and PSR-4 autoloading.

## License

MIT
