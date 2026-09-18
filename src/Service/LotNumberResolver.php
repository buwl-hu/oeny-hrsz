<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Service;

use BuwlOpenAI\OenyHrsz\Entity\Address;
use BuwlOpenAI\OenyHrsz\Entity\Locality;
use BuwlOpenAI\OenyHrsz\Entity\LotNumberResult;
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

    public function __construct(
        ?LocalityRepository $locality_repository = null,
        ?AddressRepository  $address_repository = null,
    )
    {
        $this->locality_repository = $locality_repository ?? new LocalityRepository();
        $this->address_repository = $address_repository ?? new AddressRepository();
    }

    /**
     * @throws LocalityNotFoundException
     * @throws MultipleLocalitiesFoundException
     * @throws AddressNotFoundException
     * @throws MultipleAddressesFoundException
     * @throws SubParcelNotFoundException
     * @throws MultipleSubParcelsFoundException
     */
    public function resolve(
        string  $locality,
        string  $address,
        ?int    $floor = null,
        ?string $door = null,
        ?string $building = null,
        ?string $staircase = null
    ): string
    {
        $locality = $this->findLocality($locality);
        $address = $this->findAddress($locality, $address);

        if (
            is_null($floor)
            && is_null($door)
            && is_null($building)
            && is_null($staircase)
        ) {
            return $address->getLotNumber();
        }

        $parcel = $this->findSubParcel(
            $address,
            floor: $floor,
            door: $door,
            building: $building,
            staircase: $staircase
        );

        return $parcel->getPlotNumber();
    }

    /**
     * @return array<int, LotNumberResult>
     *
     * @throws LocalityNotFoundException
     * @throws MultipleLocalitiesFoundException
     */
    public function resolveMultiple(
        string  $locality,
        string  $address,
        ?int    $floor = null,
        ?string $door = null,
        ?string $building = null,
        ?string $staircase = null
    ): array
    {
        $locality = $this->findLocality($locality);
        $addresses = $this->findAddresses($locality, $address);
        if (empty($addresses)) return [];

        $results = [];

        foreach ($addresses as $address) {
            $results[] = new LotNumberResult($address);
            $parcels = $this->findSubParcels(
                $address,
                floor: $floor,
                door: $door,
                building: $building,
                staircase: $staircase
            );
            foreach ($parcels as $parcel) $results[] = new LotNumberResult($parcel);
        }

        return $results;
    }

    /**
     * @throws LocalityNotFoundException
     * @throws MultipleLocalitiesFoundException
     */
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

    protected function findAddress(Locality $locality, string $address): Address
    {
        $addresses = $this->findAddresses($locality, $address);
        $count = count($addresses);

        if ($count === 0) {
            throw new AddressNotFoundException(
                sprintf(
                    'Address "%s" was not found in locality "%s".',
                    $address,
                    $locality->getName()
                )
            );
        }

        if ($count > 1) {
            throw new MultipleAddressesFoundException(
                sprintf(
                    'Multiple addresses matching "%s" were found in locality "%s".',
                    $address,
                    $locality->getName()
                )
            );
        }

        return $addresses[0];
    }

    /**
     * @return array<int, Address>
     */
    protected function findAddresses(Locality $locality, string $address): array
    {
        return array_values(array_filter(
            $this->address_repository->search($locality->getCode(), $address),
            fn(Address $a): bool => $a->getAddress() === $address
        ));
    }

    /**
     * @throws SubParcelNotFoundException
     */
    protected function findSubParcel(
        Address $address,
        ?int    $floor = null,
        ?string $door = null,
        ?string $building = null,
        ?string $staircase = null
    ): SubParcel
    {
        $parcels = $this->findSubParcels(...func_get_args());
        $count = count($parcels);

        if ($count === 0) {
            throw new SubParcelNotFoundException(
                sprintf(
                    'No sub-parcel was found for address "%s" with the specified criteria.',
                    $address->getAddress()
                )
            );
        }

        if (count($parcels) > 1) {
            throw new MultipleSubParcelsFoundException(
                sprintf(
                    'Multiple sub-parcels were found for address "%s" with the specified criteria.',
                    $address->getAddress()
                )
            );
        }

        return $parcels[0];
    }

    protected function findSubParcels(
        Address $address,
        ?int    $floor = null,
        ?string $door = null,
        ?string $building = null,
        ?string $staircase = null
    ): array
    {
        return array_values(array_filter(
            $address->getSubParcels(),
            fn(SubParcel $parcel): bool => (is_null($floor) || $parcel->getFloorNumber() === $floor)
                && (is_null($door) || $parcel->getDoorNumber() === $door)
                && (is_null($building) || $parcel->getBuildingNumber() === $building)
                && (is_null($staircase) || $parcel->getStaircaseNumber() === $staircase)
        ));
    }
}
