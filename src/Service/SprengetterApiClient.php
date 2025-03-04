<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SprengetterApiClient implements ApiClientInterface
{
    private const API_URL = 'https://api.avm.sprengnetter.de/service/api/';
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 2; // seconds

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly string $username,
        private readonly string $password
    ) {}

    public function getName(): string
    {
        return 'sprengnetter';
    }

    public function fetchProperties(): array
    {
        $retries = 0;
        $exception = null;

        while ($retries < self::MAX_RETRIES) {
            try {
                $response = $this->client->request('GET', self::API_URL, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic ' . base64_encode("$this->username:$this->password")
                    ],
                    'timeout' => 30,
                ]);

                $data = $response->toArray();

                // Normalize the data structure to match our common format
                return array_map(function ($item) {
                    return [
                        'address' => $this->formatAddress($item),
                        'price' => $item['price'] ?? 0.0,
                    ];
                }, $data['properties'] ?? []);
            } catch (\Exception $e) {
                $exception = $e;
                $this->logger->warning(sprintf(
                    'API request to Sprengnetter failed (attempt %d/%d): %s',
                    $retries + 1,
                    self::MAX_RETRIES,
                    $e->getMessage()
                ));

                sleep(self::RETRY_DELAY);
                $retries++;
            }
        }

        $this->logger->error(sprintf(
            'API request to Sprengnetter failed after %d attempts: %s',
            self::MAX_RETRIES,
            $exception ? $exception->getMessage() : 'Unknown error'
        ));

        return [];
    }

    private function formatAddress(array $item): string
    {
        $parts = [
            $item['street'] ?? '',
            $item['houseNumber'] ?? '',
            $item['zipCode'] ?? '',
            $item['city'] ?? '',
        ];

        return implode(' ', array_filter($parts));
    }
}