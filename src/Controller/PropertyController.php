<?php

namespace App\Controller;

use App\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/properties', name: 'api_properties_')]
class PropertyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(50, max(10, $request->query->getInt('limit', 20)));
        $source = $request->query->get('source');

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Property::class, 'p')
            ->orderBy('p.lastUpdated', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($source) {
            $queryBuilder->andWhere('p.source = :source')
                ->setParameter('source', $source);
        }

        $properties = $queryBuilder->getQuery()->getResult();

        $data = array_map(function (Property $property) {
            return [
                'id' => $property->getId(),
                'address' => $property->getAddress(),
                'price' => $property->getPrice(),
                'source' => $property->getSource(),
                'lastUpdated' => $property->getLastUpdated()->format('Y-m-d H:i:s'),
            ];
        }, $properties);

        return new JsonResponse([
            'page' => $page,
            'limit' => $limit,
            'total' => count($data),
            'data' => $data,
        ]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $property = $this->entityManager->getRepository(Property::class)->find($id);

        if (!$property) {
            return new JsonResponse(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $property->getId(),
            'address' => $property->getAddress(),
            'price' => $property->getPrice(),
            'source' => $property->getSource(),
            'lastUpdated' => $property->getLastUpdated()->format('Y-m-d H:i:s'),
        ]);
    }
}