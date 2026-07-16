<?php

namespace MeuCandidato\Ingestion\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

trait WithRetry
{
    /** @return array<string, string> */
    protected function retryHeaders(): array
    {
        return [];
    }

    private function requestWithRetry(string $method, string $url, array $options = []): ?Response
    {
        $attempts = 0;
        $maxRetries = static::MAX_RETRIES ?? 3;
        $retryDelayMs = static::RETRY_DELAY_MS ?? 1000;

        while ($attempts < $maxRetries) {
            try {
                $http = Http::timeout(60);
                $headers = $this->retryHeaders();
                if ($headers !== []) {
                    $http = $http->withHeaders($headers);
                }
                $response = $http->$method($url, $options);

                if ($response->successful()) {
                    return $response;
                }

                if ($response->status() === 429) {
                    $attempts++;
                    usleep($retryDelayMs * 1000 * $attempts);

                    continue;
                }

                return $response;
            } catch (ConnectionException $e) {
                $attempts++;

                if ($attempts >= $maxRetries) {
                    throw $e;
                }

                usleep($retryDelayMs * 1000 * $attempts);
            }
        }

        return null;
    }
}
