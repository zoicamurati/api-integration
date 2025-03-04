<?php
namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;


class EuropaceAuthClient
{
    private const TOKEN_URL = 'https://api.europace.de/auth/token';
    private const CACHE_KEY_PREFIX = 'europace_token_';
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 2; // seconds

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly array $scopes = []
    ) {}

    /**
     * Get a valid access token, either from cache or by authenticating
     *
     * @return string The access token
     * @throws \Exception If authentication fails
     */
    public function getAccessToken(): string
    {
        $cacheKey = $this->getCacheKey();

        // Try to get token from cache first
        $cachedToken = $this->cache->get($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        // No valid token in cache, get a new one
        return $this->authenticate();
    }

    /**
     * Authenticate with the Europace API using client credentials flow
     *
     * @return string The access token
     * @throws \Exception If authentication fails
     */
    private function authenticate(): string
    {
        $retries = 0;
        $exception = null;

        while ($retries < self::MAX_RETRIES) {
            try {
                $response = $this->client->request('POST', self::TOKEN_URL, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'scope' => implode(' ', $this->scopes),
                    ],
                    'timeout' => 30,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    throw new \Exception("Authentication failed with status code $statusCode");
                }

                $data = $response->toArray();
                $token = $data['access_token'] ?? null;

                if (!$token) {
                    throw new \Exception("Authentication response did not contain an access token");
                }

                // Cache the token
                $expiresIn = ($data['expires_in'] ?? 3600) - 60; // Subtract 60 seconds as buffer
                $this->cache->set($this->cacheKey, $token, $expiresIn);

                return $token;
            } catch (\Exception $e) {
                $exception = $e;
                $this->logger->warning(sprintf(
                    'Authentication to Europace failed (attempt %d/%d): %s',
                    $retries + 1,
                    self::MAX_RETRIES,
                    $e->getMessage()
                ));

                sleep(self::RETRY_DELAY);
                $retries++;
            }
        }

        $this->logger->error(sprintf(
            'Authentication to Europace failed after %d attempts: %s',
            self::MAX_RETRIES,
            $exception ? $exception->getMessage() : 'Unknown error'
        ));

        throw new \Exception(
            'Failed to authenticate with Europace API after multiple attempts',
            0,
            $exception
        );
    }

    /**
     * Generate a cache key for the token based on client ID and scopes
     *
     * @return string Cache key
     */
    private function getCacheKey(): string
    {
        $scopesKey = implode('_', $this->scopes);
        return self::CACHE_KEY_PREFIX . md5($this->clientId . '_' . $scopesKey);
    }
}