<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Entity;

class Locality
{
    public function __construct(
        protected readonly string $code,
        protected readonly string $name,
        protected readonly ?array $bounding_box = null,
    )
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array{min:array{x:float,y:float},max:array{x:float,y:float}}|null */
    public function getBoundingBox(): ?array
    {
        return $this->bounding_box;
    }

    public static function fromArray(array $data): self
    {
        return new self((string)($data['kshCode'] ?? ''), (string)$data['name']);
    }
}
