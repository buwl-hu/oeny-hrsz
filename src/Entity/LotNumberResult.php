<?php

namespace BuwlOpenAI\OenyHrsz\Entity;

class LotNumberResult
{
    public function __construct(
        protected readonly Address|SubParcel $entity
    )
    {
    }

    public function getEntity(): Address|SubParcel
    {
        return $this->entity;
    }

    public function getLotNumber(): string
    {
        return $this->entity->getPlotNumber();
    }
}
