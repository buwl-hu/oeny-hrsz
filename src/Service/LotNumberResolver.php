<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Service;

use BuwlOpenAI\OenyHrsz\Entity\Address;
use BuwlOpenAI\OenyHrsz\Entity\Locality;
use BuwlOpenAI\OenyHrsz\Entity\SubParcel;
use BuwlOpenAI\OenyHrsz\Exception\AddressNotFoundException;
use BuwlOpenAI\OenyHrsz\Exception\LocalityNotFoundException;
use BuwlOpenAI\OenyHrsz\Exception\MultipleAddressesFoundException;
use BuwlOpenAI\OenyHrsz\Exception\MultipleLocalitiesFoundException;
use BuwlOpenAI\OenyHrsz\Exception\MultipleSubParcelsFoundException;
use BuwlOpenAI\OenyHrsz\Exception\SubParcelNotFoundException;
use BuwlOpenAI\OenyHrsz\Repository\AddressRepository;
use BuwlOpenAI\OenyHrsz\Repository\LocalityRepository;

class LotNumberResolver
{
    protected readonly LocalityRepository $locality_repository;
    protected readonly AddressRepository $address_repository;
    protected ?Locality $locality = null;
    protected ?Address $address = null;

    public function __construct(
        ?LocalityRepository $locality_repository = null,
        ?AddressRepository  $address_repository = null,
    )
    {
        $this->locality_repository = $locality_repository ?? new LocalityRepository();
        $this->address_repository = $address_repository ?? new AddressRepository();
    }

    public function resolve(
        string $locality,
        string $address,
        ?int   $floor = null,
        ?int   $door = null
    ): string
    {
        $this->locality = $this->findLocality($locality);
        $this->address = $this->findAddress($address);

        if (is_null($floor) && is_null($door)) {
            return $this->address->getLotNumber();
        }

        $parcel = $this->findSubParcel($floor, $door);
        return $parcel->getPlotNumber();
    }

    protected function findLocality(string $locality): Locality
    {
        $localities = array_values(array_filter(
            $this->locality_repository->search($locality),
            fn(Locality $l): bool => $l->getName() === $locality
        ));

        $count = count($localities);

        if ($count === 0) {
            throw new LocalityNotFoundException(
                sprintf('Locality "%s" was not found.', $locality)
            );
        }

        if ($count > 1) {
            throw new MultipleLocalitiesFoundException(
                sprintf('Multiple localities named "%s" were found.', $locality)
            );
        }

        return $localities[0];
    }

    protected function findAddress(string $address): Address
    {
        $addresses = array_values(array_filter(
            $this->address_repository->search($this->locality->getCode(), $address),
            fn(Address $a): bool => $a->getAddress() === $address
        ));

        $count = count($addresses);

        if ($count === 0) {
            throw new AddressNotFoundException(
                sprintf('Address "%s" was not found in locality "%s".', $address, $this->locality->getName())
            );
        }

        if ($count > 1) {
            throw new MultipleAddressesFoundException(
                sprintf('Multiple addresses matching "%s" were found in locality "%s".', $address, $this->locality->getName())
            );
        }

        return $addresses[0];
    }

    protected function findSubParcel(
        ?int $floor = null,
        ?int $door = null
    ): SubParcel
    {
        $parcels = array_values(array_filter(
            $this->address->getSubParcels(),
            fn(SubParcel $parcel): bool => (is_null($floor) || $parcel->getFloorNumber() === $floor)
                && (is_null($door) || $parcel->getDoorNumber() === $door)
        ));

        $count = count($parcels);

        if ($count === 0) {
            throw new SubParcelNotFoundException(
                sprintf(
                    'No sub-parcel was found for address "%s" with floor %s and door %s.',
                    $this->address->getAddress(),
                    $floor === null ? 'any' : (string)$floor,
                    $door === null ? 'any' : (string)$door
                )
            );
        }

        if ($count > 1) {
            throw new MultipleSubParcelsFoundException(
                sprintf(
                    'Multiple sub-parcels were found for address "%s" with the specified criteria.',
                    $this->address->getAddress()
                )
            );
        }

        return $parcels[0];
    }
}
