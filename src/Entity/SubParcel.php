<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Entity;

class SubParcel
{
    public function __construct(
        protected readonly string  $plot_number,
        protected readonly ?string $house_number,
        protected readonly ?string $building_number,
        protected readonly ?string $staircase_number,
        protected readonly ?int    $floor_number,
        protected readonly ?string $door_number,
        protected readonly Address $address
    )
    {
    }

    public function getPlotNumber(): string
    {
        return $this->plot_number;
    }

    public function getLotNumber(): string
    {
        return $this->plot_number;
    }

    public function getFloorNumber(): ?int
    {
        return $this->floor_number;
    }

    public function getDoorNumber(): ?string
    {
        return $this->door_number;
    }

    public function getHouseNumber(): ?string
    {
        return $this->house_number;
    }

    public function getBuildingNumber(): ?string
    {
        return $this->building_number;
    }

    public function getStaircaseNumber(): ?string
    {
        return $this->staircase_number;
    }

    public static function fromArray(array $data, Address $address): self
    {
        $house_number = null;
        $building_number = null;
        $staircase_number = null;
        $floor_number = null;

        if (!empty($data['houseNumber'])) {
            $house_number = trim(preg_replace('/\s+(?:ép|lh):.*$/u', '', $data['houseNumber']));

            if (preg_match('/\bép:(\S+)/u', $data['houseNumber'], $matches)) {
                $building_number = $matches[1];
            }

            if (preg_match('/\blh:(\S+)/u', $data['houseNumber'], $matches)) {
                $staircase_number = $matches[1];
            }
        }

        if (!empty($floor = mb_strtolower(trim($data['floor'])))) {
            $floor_number = (int)match ($floor) {
                'pinceszint' => -1,
                'földszint' => 0,
                default => $floor
            };
        }

        return new self(
            plot_number: $data['plotNumber'],
            house_number: $house_number,
            building_number: $building_number,
            staircase_number: $staircase_number,
            floor_number: $floor_number,
            door_number: !empty($data['doorNumber']) ? $data['doorNumber'] : null,
            address: $address
        );
    }
}
