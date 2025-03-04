<?php
namespace App\Service;

use App\Service\EuropaceAuthClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class EuropaceApiClient implements ApiClientInterface
{
    private const API_URL = 'https://api.europace.de/v1/properties';
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 2; // seconds

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly EuropaceAuthClient $authClient
    ) {}

    public function getName(): string
    {
        return 'europace';
    }

    public function fetchProperties(): array
    {
        $retries = 0;
        $exception = null;

        while ($retries < self::MAX_RETRIES) {
            try {
                $accessToken = $this->authClient->getAccessToken();
                $response = $this->client->request('GET', self::API_URL, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $accessToken,
                    ],
                    'timeout' => 30,
                ]);

                $data = $response->toArray();

                // Normalize the data structure to match our common format
                // This is a short solution if there are more data i would use a function about it to make the code mor clean
                return array_map(function ($item) {
                    return [
                        'address' => $this->formatAddress($item),
                        'price' => $item['marketValue'] ?? $item['purchasePrice'] ?? 0.0,
                    ];
                }, $data['properties'] ?? []);
            } catch (\Exception $e) {
                $exception = $e;
                $this->logger->warning(sprintf(
                    'API request to Europace failed (attempt %d/%d): %s',
                    $retries + 1,
                    self::MAX_RETRIES,
                    $e->getMessage()
                ));

                sleep(self::RETRY_DELAY);
                $retries++;
            }
        }

        $this->logger->error(sprintf(
            'API request to Europace failed after %d attempts: %s',
            self::MAX_RETRIES,
            $exception ? $exception->getMessage() : 'Unknown error'
        ));

        return [];
    }

    private function formatAddress(array $item): string
    {
        $address = $item['address'] ?? [];

        $parts = [
            $address['street'] ?? '',
            $address['houseNumber'] ?? '',
            $address['zipCode'] ?? '',
            $address['city'] ?? '',
        ];

        return implode(' ', array_filter($parts));
    }

}