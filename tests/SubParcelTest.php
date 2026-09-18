<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Tests;

use BuwlOpenAI\OenyHrsz\Entity\Address;
use BuwlOpenAI\OenyHrsz\Entity\SubParcel;
use PHPUnit\Framework\TestCase;

class SubParcelTest extends TestCase
{
    protected function getAddress(): Address
    {
        return new Address(1482741, null, 'Egri út 6');
    }

    public function testParsesGroundFloor(): void
    {
        $parcel = SubParcel::fromArray([
            'plotNumber' => '1900/3/A/29',
            'floor' => 'földszint',
            'doorNumber' => '1',
            'houseNumber' => '6',
        ], $this->getAddress());

        self::assertSame('1900/3/A/29', $parcel->getPlotNumber());
        self::assertSame('6', $parcel->getHouseNumber());
        self::assertSame(0, $parcel->getFloorNumber());
        self::assertSame('1', $parcel->getDoorNumber());
        self::assertNull($parcel->getBuildingNumber());
        self::assertNull($parcel->getStaircaseNumber());
    }

    public function testParsesBasementFloor(): void
    {
        $parcel = SubParcel::fromArray([
            'plotNumber' => '1900/3/A/28',
            'floor' => 'pinceszint',
            'doorNumber' => '1',
            'houseNumber' => '6',
        ], $this->getAddress());

        self::assertSame(-1, $parcel->getFloorNumber());
    }

    public function testParsesNumericFloor(): void
    {
        $parcel = SubParcel::fromArray([
            'plotNumber' => '1900/3/A/31',
            'floor' => '1. emelet',
            'doorNumber' => '2',
            'houseNumber' => '6',
        ], $this->getAddress());

        self::assertSame(1, $parcel->getFloorNumber());
    }

    public function testParsesNonNumericDoorNumber(): void
    {
        $parcel = SubParcel::fromArray([
            'plotNumber' => '1900/3/A/31',
            'floor' => '1. emelet',
            'doorNumber' => '2/A',
            'houseNumber' => '6',
        ], $this->getAddress());

        self::assertSame('2/A', $parcel->getDoorNumber());
    }

    public function testParsesBuildingNumber(): void
    {
        $parcel = SubParcel::fromArray([
            'plotNumber' => '1900/3/A/31',
            'floor' => '1. emelet',
            'doorNumber' => '2',
            'houseNumber' => '6 ép:A',
        ], $this->getAddress());

        self::assertSame('A', $parcel->getBuildingNumber());
        self::assertNull($parcel->getStaircaseNumber());
        self::assertSame('6', $parcel->getHouseNumber());
    }

    public function testParsesStaircaseNumber(): void
    {
        $parcel = SubParcel::fromArray([
            'plotNumber' => '1900/3/A/31',
            'floor' => '1. emelet',
            'doorNumber' => '2',
            'houseNumber' => '6 lh:2',
        ], $this->getAddress());

        self::assertNull($parcel->getBuildingNumber());
        self::assertSame('2', $parcel->getStaircaseNumber());
        self::assertSame('6', $parcel->getHouseNumber());
    }

    public function testParsesBuildingAndStaircaseNumber(): void
    {
        $parcel = SubParcel::fromArray([
            'plotNumber' => '1900/3/A/31',
            'floor' => '1. emelet',
            'doorNumber' => '2/A',
            'houseNumber' => '48 ép:A lh:1',
        ], $this->getAddress());

        self::assertSame('48', $parcel->getHouseNumber());
        self::assertSame('A', $parcel->getBuildingNumber());
        self::assertSame('1', $parcel->getStaircaseNumber());
        self::assertSame(1, $parcel->getFloorNumber());
        self::assertSame('2/A', $parcel->getDoorNumber());
    }
}
