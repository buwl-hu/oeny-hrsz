<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Exception;

class ApiException extends \RuntimeException
{
    public function __construct(string $message, protected readonly ?int $statusCode = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
