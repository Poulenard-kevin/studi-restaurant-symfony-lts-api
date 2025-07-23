<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/restaurant', name: 'app_api_restaurant_')]
class RestaurantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private RestaurantRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(methods: ['POST'])]
    #[OA\Post(
        summary: "Créer un restaurant",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données du restaurant à créer",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Nom du restaurant"),
                    new OA\Property(property: "description", type: "string", example: "Description du restaurant"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Restaurant créé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Nom du restaurant"),
                        new OA\Property(property: "description", type: "string", example: "Description du restaurant"),
                        new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                    ]
                )
            )
        ]
    )]
    public function new(Request $request): JsonResponse
    {
        $restaurant = $this->serializer->deserialize($request->getContent(), Restaurant::class, 'json');
        $restaurant->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($restaurant);
        $this->manager->flush();

        $responseData = $this->serializer->serialize($restaurant, 'json');
        $location = $this->urlGenerator->generate(
            'app_api_restaurant_show',
            ['id' => $restaurant->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        summary: "Afficher un restaurant par ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID du restaurant à afficher",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Restaurant trouvé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Nom du restaurant"),
                        new OA\Property(property: "description", type: "string", example: "Description du restaurant"),
                        new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Restaurant non trouvé"
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);
        if ($restaurant) {
            $responseData = $this->serializer->serialize($restaurant, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
        summary: "Modifier un restaurant par ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID du restaurant à modifier",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Nouvelles données du restaurant à mettre à jour",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Nouveau nom du restaurant"),
                    new OA\Property(property: "description", type: "string", example: "Nouvelle description du restaurant"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: "Restaurant modifié avec succès"
            ),
            new OA\Response(
                response: 404,
                description: "Restaurant non trouvé"
            )
        ]
    )]
    public function edit(int $id, Request $request): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);
        if ($restaurant) {
            $restaurant = $this->serializer->deserialize(
                $request->getContent(),
                Restaurant::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $restaurant]
            );
            $restaurant->setUpdatedAt(new DateTimeImmutable());

            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: "Supprimer un restaurant par ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID du restaurant à supprimer",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Restaurant supprimé avec succès"
            ),
            new OA\Response(
                response: 404,
                description: "Restaurant non trouvé"
            )
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);
        if ($restaurant) {
            $this->manager->remove($restaurant);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}