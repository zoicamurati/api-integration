<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Property;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class PropertyService
{
    private const CACHE_EXPIRATION = 3600; // 1 Hour

    /** @var ApiClientInterface[] */
    private iterable $apiClients;

    public function __construct(
        #[TaggedIterator('app.api_client')] iterable $apiClients,
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger
    ) {
        $this->apiClients = $apiClients;
    }

    public function fetchProperties(): int
    {
        $totalCount = 0;

        foreach ($this->apiClients as $apiClient) {
            $source = $apiClient->getName();
            $cacheKey = "properties_$source";
            $cached = $this->cache->getItem($cacheKey);

            if (!$cached->isHit()) {
                try {
                    $this->logger->info("Fetching properties from $source API");
                    $data = $apiClient->fetchProperties();

                    $count = $this->storeProperties($data, $source);
                    $totalCount += $count;

                    $this->logger->info("Successfully fetched and stored $count properties from $source");

                    $cached->set($data);
                    $cached->expiresAfter(self::CACHE_EXPIRATION);
                    $this->cache->save($cached);
                } catch (\Exception $e) {
                    $this->logger->error("Failed to process data from $source API: " . $e->getMessage());
                }
            } else {
                $this->logger->info("Using cached data for $source API");
                $data = $cached->get();
                $totalCount += count($data);
            }
        }

        return $totalCount;
    }

    private function storeProperties(array $data, string $source): int
    {
        $count = 0;

        // Use batch processing for better performance
        $batchSize = 20;
        $i = 0;

        foreach ($data as $item) {
            $externalId = $item['externalId'] ?? '';

            // Skip if no external ID
            if (empty($externalId)) {
                $this->logger->warning("Skipping property without external ID from source $source");
                continue;
            }

            // Check if property already exists
            $property = $this->entityManager->getRepository(Property::class)
                ->findOneBy(['externalId' => $externalId, 'source' => $source]);

            if (!$property) {
                $property = new Property();
                $property->setExternalId($externalId);
                $property->setSource($source);
            }

            // Update property data
            $property->setAddress($item['address'] ?? 'Unknown');
            $property->setPrice($item['price'] ?? 0.0);
            $property->setRooms($item['rooms'] ?? null);
            $property->setArea($item['area'] ?? null);
            $property->setLastUpdated(new \DateTime());
            $property->setAdditionalData($item['additionalData'] ?? null);

            $this->entityManager->persist($property);
            $count++;

            // Flush every $batchSize entities to free memory
            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $i++;
        }

        // Final flush for remaining entities
        $this->entityManager->flush();

        return $count;
    }

}