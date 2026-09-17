<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Tests;

use BuwlOpenAI\OenyHrsz\Cache\ArrayCache;
use BuwlOpenAI\OenyHrsz\Entity\Address;
use BuwlOpenAI\OenyHrsz\Entity\Locality;
use BuwlOpenAI\OenyHrsz\Entity\SubParcel;
use BuwlOpenAI\OenyHrsz\Exception\AddressNotFoundException;
use BuwlOpenAI\OenyHrsz\Exception\LocalityNotFoundException;
use BuwlOpenAI\OenyHrsz\Exception\MultipleAddressesFoundException;
use BuwlOpenAI\OenyHrsz\Exception\MultipleLocalitiesFoundException;
use BuwlOpenAI\OenyHrsz\Exception\MultipleSubParcelsFoundException;
use BuwlOpenAI\OenyHrsz\Exception\SubParcelNotFoundException;
use BuwlOpenAI\OenyHrsz\Http\HttpClientInterface;
use BuwlOpenAI\OenyHrsz\Repository\AddressRepository;
use BuwlOpenAI\OenyHrsz\Repository\LocalityRepository;
use BuwlOpenAI\OenyHrsz\Service\LotNumberResolver;
use PHPUnit\Framework\TestCase;

class LotNumberResolverTest extends TestCase
{
    public function testResolvesLotNumberFromAddress(): void
    {
        $http = $this->getHttp();
        $localityRepository = new LocalityRepository($http, new ArrayCache());
        $addressRepository = new AddressRepository($http, new ArrayCache());

        $resolver = new LotNumberResolver(
            $localityRepository,
            $addressRepository
        );

        self::assertSame(
            '1900/3',
            $resolver->resolve('Gyöngyös', 'Egri út 6')
        );
    }

    public function testResolvesLotNumberFromSubParcel(): void
    {
        $http = $this->getHttp();
        $localityRepository = new LocalityRepository($http, new ArrayCache());
        $addressRepository = new AddressRepository($http, new ArrayCache());

        $resolver = new LotNumberResolver(
            $localityRepository,
            $addressRepository
        );

        self::assertSame(
            '1900/3/A/33',
            $resolver->resolve('Gyöngyös', 'Egri út 6', 1, 3)
        );
    }

    public function testThrowsWhenLocalityIsNotFound(): void
    {
        $localityRepository = $this->createMock(LocalityRepository::class);
        $addressRepository = $this->createMock(AddressRepository::class);

        $localityRepository
            ->expects(self::once())
            ->method('search')
            ->with('Unknown')
            ->willReturn([]);

        $resolver = new LotNumberResolver(
            $localityRepository,
            $addressRepository
        );

        $this->expectException(LocalityNotFoundException::class);
        $this->expectExceptionMessage(
            'Locality "Unknown" was not found.'
        );

        $resolver->resolve('Unknown', 'Egri út 6');
    }

    public function testThrowsWhenMultipleLocalitiesAreFound(): void
    {
        $localityRepository = $this->createMock(LocalityRepository::class);
        $addressRepository = $this->createMock(AddressRepository::class);

        $localityRepository
            ->expects(self::once())
            ->method('search')
            ->with('Eger')
            ->willReturn([
                new Locality('3300', 'Eger'),
                new Locality('3301', 'Eger'),
            ]);

        $resolver = new LotNumberResolver(
            $localityRepository,
            $addressRepository
        );

        $this->expectException(MultipleLocalitiesFoundException::class);
        $this->expectExceptionMessage(
            'Multiple localities named "Eger" were found.'
        );

        $resolver->resolve('Eger', 'Egri út 6');
    }

    public function testThrowsWhenAddressIsNotFound(): void
    {
        $locality = new Locality('05236', 'Gyöngyös');

        $localityRepository = $this->createMock(LocalityRepository::class);
        $addressRepository = $this->createMock(AddressRepository::class);

        $localityRepository
            ->expects(self::once())
            ->method('search')
            ->with('Gyöngyös')
            ->willReturn([$locality]);

        $addressRepository
            ->expects(self::once())
            ->method('search')
            ->with('05236', 'Unknown street 1')
            ->willReturn([]);

        $resolver = new LotNumberResolver(
            $localityRepository,
            $addressRepository
        );

        $this->expectException(AddressNotFoundException::class);
        $this->expectExceptionMessage(
            'Address "Unknown street 1" was not found in locality "Gyöngyös".'
        );

        $resolver->resolve('Gyöngyös', 'Unknown street 1');
    }

    public function testThrowsWhenMultipleAddressesAreFound(): void
    {
        $locality = new Locality('05236', 'Gyöngyös');

        $localityRepository = $this->createMock(LocalityRepository::class);
        $addressRepository = $this->createMock(AddressRepository::class);

        $localityRepository
            ->expects(self::once())
            ->method('search')
            ->with('Gyöngyös')
            ->willReturn([$locality]);

        $addressRepository
            ->expects(self::once())
            ->method('search')
            ->with('05236', 'Egri út 6')
            ->willReturn([
                new Address(1, '', 'Egri út 6'),
                new Address(2, '', 'Egri út 6'),
            ]);

        $resolver = new LotNumberResolver(
            $localityRepository,
            $addressRepository
        );

        $this->expectException(MultipleAddressesFoundException::class);
        $this->expectExceptionMessage(
            'Multiple addresses matching "Egri út 6" were found in locality "Gyöngyös".'
        );

        $resolver->resolve('Gyöngyös', 'Egri út 6');
    }

    public function testThrowsWhenSubParcelIsNotFound(): void
    {
        $locality = new Locality('05236', 'Gyöngyös');
        $address = new Address(1482741, '', 'Egri út 6');

        $addressRepository = $this->createMock(AddressRepository::class);
        $localityRepository = $this->createMock(LocalityRepository::class);

        $localityRepository
            ->method('search')
            ->willReturn([$locality]);

        $addressRepository
            ->method('search')
            ->willReturn([$address]);

        $address->attachRepository($addressRepository);

        $addressRepository
            ->expects(self::once())
            ->method('getSubParcels')
            ->with(1482741)
            ->willReturn([]);

        $resolver = new LotNumberResolver(
            $localityRepository,
            $addressRepository
        );

        $this->expectException(SubParcelNotFoundException::class);

        $resolver->resolve('Gyöngyös', 'Egri út 6', 2, 13);
    }

    public function testThrowsWhenMultipleSubParcelsAreFound(): void
    {
        $locality = new Locality('05236', 'Gyöngyös');
        $address = new Address(1482741, '', 'Egri út 6');

        $addressRepository = $this->createMock(AddressRepository::class);
        $localityRepository = $this->createMock(LocalityRepository::class);

        $localityRepository
            ->method('search')
            ->willReturn([$locality]);

        $addressRepository
            ->method('search')
            ->willReturn([$address]);

        $address->attachRepository($addressRepository);

        $addressRepository
            ->expects(self::once())
            ->method('getSubParcels')
            ->with(1482741)
            ->willReturn([
                new SubParcel('2613/A/1', (int)'földszint', (int)'1', (int)'5'),
                new SubParcel('2613/A/2', (int)'földszint', (int)'1', (int)'5'),
            ]);

        $resolver = new LotNumberResolver(
            $localityRepository,
            $addressRepository
        );

        $this->expectException(MultipleSubParcelsFoundException::class);
        $this->expectExceptionMessage(
            'Multiple sub-parcels were found for address "Egri út 6" with the specified criteria.'
        );

        $resolver->resolve('Gyöngyös', 'Egri út 6', 0, 1);
    }

    protected function getHttp()
    {
        return new class implements HttpClientInterface {
            public function get(string $url, array $query = []): array
            {
                return match ($url) {
                    BUWL_OENY_API_LOCALITIES_ENDPOINT . '/search' => [
                        ["kshCode" => "05236", "name" => "Gyöngyös"],
                        ["kshCode" => "11943", "name" => "Gyöngyösfalu"],
                        ["kshCode" => "17534", "name" => "Gyöngyöshalász"],
                        ["kshCode" => "22664", "name" => "Gyöngyösmellék"],
                        ["kshCode" => "13338", "name" => "Gyöngyösoroszi"],
                        ["kshCode" => "08323", "name" => "Gyöngyöspata"],
                        ["kshCode" => "19123", "name" => "Gyöngyössolymos"],
                        ["kshCode" => "28088", "name" => "Gyöngyöstarján"]
                    ],
                    BUWL_OENY_API_ADDRESSES_ENDPOINT . '/search' => [
                        ['id' => 1482741, 'districtPrefix' => null, 'address' => 'Egri út 6',],
                    ],
                    BUWL_OENY_API_ADDRESSES_ENDPOINT . '/position' => [
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
                    ],
                    BUWL_OENY_API_ADDRESSES_ENDPOINT . '/sub-parcels' => [
                        ['plotNumber' => '1900/3/A/29', 'floor' => 'földszint', 'doorNumber' => '1', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/30', 'floor' => 'földszint', 'doorNumber' => '2', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/31', 'floor' => '1. emelet', 'doorNumber' => '1', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/32', 'floor' => '1. emelet', 'doorNumber' => '2', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/33', 'floor' => '1. emelet', 'doorNumber' => '3', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/34', 'floor' => '2. emelet', 'doorNumber' => '1', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/35', 'floor' => '2. emelet', 'doorNumber' => '2', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/36', 'floor' => '2. emelet', 'doorNumber' => '3', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/37', 'floor' => '3. emelet', 'doorNumber' => '1', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/38', 'floor' => '3. emelet', 'doorNumber' => '2', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/39', 'floor' => '3. emelet', 'doorNumber' => '3', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/40', 'floor' => '4. emelet', 'doorNumber' => '1', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/41', 'floor' => '4. emelet', 'doorNumber' => '2', 'houseNumber' => '6'],
                        ['plotNumber' => '1900/3/A/42', 'floor' => '4. emelet', 'doorNumber' => '3', 'houseNumber' => '6']
                    ],
                    default => [],
                };
            }
        };
    }
}
