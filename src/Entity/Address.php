<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Entity;

use BuwlOpenAI\OenyHrsz\Repository\AddressRepository;

class Address
{
    protected ?string $address = null;
    protected ?AddressRepository $repository = null;
    protected bool $details_loaded = false;
    protected ?string $lot_number = null;
    protected ?string $layment = null;
    protected ?array $point = null;
    protected ?array $bounding_box = null;
    protected ?array $outline = null;
    protected ?Locality $settlement = null;
    /** @var array */
    protected array $related_addresses = [];
    /** @var SubParcel[]|null */
    protected ?array $sub_parcels = null;
    /** @var string[]|null */
    protected ?array $floors = null;

    public function __construct(
        protected readonly int     $id,
        protected readonly ?string $district_prefix,
        ?string                    $address = null
    )
    {
        $this->address = $address;
    }

    public function attachRepository(AddressRepository $repository): self
    {
        $this->repository = $repository;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDistrictPrefix(): ?string
    {
        return $this->district_prefix;
    }

    public function getAddress(): string
    {
        if (is_null($this->address)) $this->loadDetails();
        return $this->address;
    }

    public function getLotNumber(): ?string
    {
        $this->loadDetails();
        return $this->lot_number;
    }

    public function getLayment(): ?string
    {
        $this->loadDetails();
        return $this->layment;
    }

    public function getPoint(): ?array
    {
        $this->loadDetails();
        return $this->point;
    }

    public function getBoundingBox(): ?array
    {
        $this->loadDetails();
        return $this->bounding_box;
    }

    public function getOutline(): ?array
    {
        $this->loadDetails();
        return $this->outline;
    }

    public function getSettlement(): ?Locality
    {
        $this->loadDetails();
        return $this->settlement;
    }

    /** @return Address[] */
    public function getRelatedAddresses(): array
    {
        $this->loadDetails();
        return $this->related_addresses;
    }

    /** @return SubParcel[] */
    public function getSubParcels(): array
    {
        if ($this->sub_parcels === null) $this->sub_parcels = $this->repository?->getSubParcels($this->id, $this) ?? [];
        return $this->sub_parcels;
    }

    /** @return string[] */
    public function getFloors(): array
    {
        if ($this->floors === null) $this->floors = $this->repository?->getFloors($this->id) ?? [];
        return $this->floors;
    }

    /** @param array<string,mixed> $data */
    public function hydrate(array $data, AddressRepository $repository): void
    {
        $this->repository = $repository;
        $this->point = $data['point'] ?? null;
        $this->bounding_box = $data['boundingBox'] ?? null;
        $this->outline = $data['outline'] ?? null;
        $this->lot_number = isset($data['lotNumber']) ? (string)$data['lotNumber'] : null;
        $this->layment = isset($data['layment']) ? (string)$data['layment'] : null;
        $this->related_addresses = $data['addresses'] ?? [];

        if (isset($data['settlement']) && is_array($data['settlement'])) {
            $this->settlement = new Locality(
                (string)($data['settlement']['kshCode'] ?? ''),
                (string)($data['settlement']['name'] ?? '')
            );
        }

        if (is_null($this->address)) $this->address = (($data['addresses'] ?? [])[0] ?? [])['address'] ?? null;

        $this->details_loaded = true;
    }

    protected function loadDetails(): void
    {
        if ($this->details_loaded || $this->repository === null) return;
        $this->repository->hydrate($this);
    }

    public static function fromArray(array $data, ?AddressRepository $repository = null): self
    {
        $address = new self(
            id: (int)$data['id'],
            district_prefix: isset($data['districtPrefix']) ? (string)$data['districtPrefix'] : null,
            address: (string)$data['address']
        );
        if ($repository) $address->attachRepository($repository);
        return $address;
    }
}
