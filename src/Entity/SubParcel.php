<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Entity;

class SubParcel
{
    public function __construct(
        protected readonly string $plot_number,
        protected readonly ?int   $floor_number,
        protected readonly ?int   $door_number,
        protected readonly ?int   $house_number,
    )
    {
    }

    public function getPlotNumber(): string
    {
        return $this->plot_number;
    }

    public function getFloorNumber(): ?int
    {
        return $this->floor_number;
    }

    public function getDoorNumber(): ?int
    {
        return $this->door_number;
    }

    public function getHouseNumber(): ?int
    {
        return $this->house_number;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            plot_number: $data['plotNumber'],
            floor_number: !empty($data['floor']) ? (int)$data['floor'] : null,
            door_number: !empty($data['doorNumber']) ? (int)$data['doorNumber'] : null,
            house_number: !empty($data['houseNumber']) ? (int)$data['houseNumber'] : null,
        );
    }
}
