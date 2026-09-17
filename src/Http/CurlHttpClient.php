<?php

declare(strict_types=1);

namespace BuwlOpenAI\OenyHrsz\Http;

use BuwlOpenAI\OenyHrsz\Exception\ApiException;

class CurlHttpClient implements HttpClientInterface
{
    public function __construct(
        protected readonly int $timeout = 10,
        protected readonly string $user_agent = 'buwl-openai/oeny-hrsz'
    ) {
    }

    public function get(string $url, array $query = []): array
    {
        $url .= $query === [] ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $handle = curl_init($url);
        if ($handle === false) {
            throw new ApiException('Unable to initialize cURL.');
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => $this->user_agent,
            CURLOPT_ENCODING => '',
        ]);

        $body = curl_exec($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($body === false) {
            throw new ApiException('OÉNY API request failed: ' . ($error ?: 'unknown cURL error'));
        }

        if ($status < 200 || $status >= 300) {
            throw new ApiException(sprintf('OÉNY API returned HTTP %d.', $status), $status);
        }

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ApiException('OÉNY API returned invalid JSON.', $status, $e);
        }

        if (!is_array($data)) {
            throw new ApiException('OÉNY API returned an unexpected JSON value.', $status);
        }

        return $data;
    }
}
