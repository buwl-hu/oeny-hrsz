# OÉNY HRSZ PHP Client

Lightweight PHP 8.3+ client for the publicly accessible endpoints used by the OÉNY Helyrajziszám-kereső.

> **Important:** this package is based on observed endpoints used by the public website. OÉNY does not document these endpoints here as a public developer API. Endpoints, response formats and availability may change without notice.

## Installation

```bash
composer require buwl-openai/oeny-hrsz
```

## Basic usage

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
$addresses = new AddressRepository(cache: $cache, cacheTtl: 3600);
```

Implement `CacheInterface` to connect Redis, Symfony Cache, PSR-16, WordPress transients, etc.

## Namespaces

The package uses the `BuwlOpenAI\\OenyHrsz\\` namespace and PSR-4 autoloading.

## License

MIT
