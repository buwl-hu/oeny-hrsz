<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Http;

interface HttpClientInterface
{
    /** @return array<string, mixed> */
    public function get(string $url, array $query = []): array;
}
