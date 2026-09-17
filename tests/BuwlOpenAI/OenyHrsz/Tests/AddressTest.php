<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Tests;

use BuwlOpenAI\OenyHrsz\Cache\ArrayCache;
use BuwlOpenAI\OenyHrsz\Entity\Address;
use BuwlOpenAI\OenyHrsz\Http\HttpClientInterface;
use BuwlOpenAI\OenyHrsz\Repository\AddressRepository;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    public function testPositionIsFetchedOnlyOncePerAddressInstance(): void
    {
        $http = new class implements HttpClientInterface {
            public int $calls = 0;

            public function get(string $url, array $query = []): array
            {
                $this->calls++;
                return [
                    'point' => ['x' => 716190.25, 'y' => 271183.34375],
                    'boundingBox' => null,
                    'outline' => ['type' => 'MultiPolygon', 'coordinates' => [[[]]]],
                    'settlement' => ['kshCode' => '05236', 'name' => 'Gyöngyös'],
                    'addresses' => [
                        [
                            'subParcelCount' => 14,
                            'isTechnical' => false,
                            'address' => ['id' => 1482741, 'districtPrefix' => null, 'address' => 'Egri út 6']
                        ]
                    ],
                    'lotNumber' => '1900/3',
                    'layment' => 'Belterület'
                ];
            }
        };

        $repository = new AddressRepository($http, new ArrayCache());
        $address = (new Address(1482741, null, 'Egri út 6'))->attachRepository($repository);

        self::assertSame('1900/3', $address->getLotNumber());
        self::assertSame('Belterület', $address->getLayment());
        self::assertSame(['x' => 716190.25, 'y' => 271183.34375], $address->getPoint());
        self::assertSame(1, $http->calls);
    }

    public function testAddressIsHydratedFromNestedAddressData(): void
    {
        $http = new class implements HttpClientInterface {
            public function get(string $url, array $query = []): array
            {
                return [
                    'point' => null,
                    'boundingBox' => null,
                    'outline' => null,
                    'settlement' => null,
                    'addresses' => [
                        [
                            'address' => [
                                'id' => 1,
                                'districtPrefix' => null,
                                'address' => 'Egri út 6',
                            ],
                        ],
                    ],
                    'lotNumber' => '1900/3',
                    'layment' => 'Belterület',
                ];
            }
        };

        $repository = new AddressRepository($http, new ArrayCache());

        $address = (new Address(
            1482741,
            null,
            null
        ))->attachRepository($repository);

        self::assertSame('Egri út 6', $address->getAddress());
    }
}
