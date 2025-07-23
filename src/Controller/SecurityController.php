<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SerializerInterface $serializer,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/registration', name: 'registration', methods: ['POST'])]
    #[OA\Post(
        summary: "Inscription d'un nouvel utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de l'utilisateur à inscrire",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "firstName", type: "string", example: "Thomas"),
                    new OA\Property(property: "lastName", type: "string", example: "Dupont"),
                    new OA\Property(property: "email", type: "string", example: "thomas@email.com"),
                    new OA\Property(property: "password", type: "string", example: "Mot de passe"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Utilisateur inscrit avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "user", type: "string", example: "Nom d'utilisateur"),
                        new OA\Property(property: "apiToken", type: "string", example: "31a023e212f116124a36af14ea0c1c3806eb9378"),
                        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string", example: "ROLE_USER")),
                    ]
                )
            )
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse(
            ['user'  => $user->getUserIdentifier(), 'apiToken' => $user->getApiToken(), 'roles' => $user->getRoles()],
            Response::HTTP_CREATED
        );
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        summary: "Connecter un utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Identifiants de connexion",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "username", type: "string"),
                    new OA\Property(property: "password", type: "string"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Connexion réussie",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "X-AUTH-TOKEN", type: "string"),
                        new OA\Property(property: "user", type: "string"),
                        new OA\Property(property: "apiToken", type: "string"),
                        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string")),
                    ]
                )
            )
        ]
    )]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Missing credentials'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user'  => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/account/me', name: 'me', methods: ['GET'])]
    #[OA\Get(
        summary: "Récupérer toutes les informations de l'objet User",
        responses: [
            new OA\Response(
                response: 200,
                description: "Tous les champs utilisateurs retournés"
            )
        ]
    )]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        $responseData = $this->serializer->serialize($user, 'json');

        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    #[Route('/account/edit', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
        summary: "Modifier son compte utilisateur avec l'un ou tous les champs",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Nouvelles données éventuelles de l'utilisateur à mettre à jour",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "firstName", type: "string", example: "Nouveau prénom"),
                    // Ajoute ici d'autres propriétés si besoin
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: "Utilisateur modifié avec succès"
            )
        ]
    )]
    public function edit(Request $request): JsonResponse
    {
        $user = $this->serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $this->getUser()],
        );
        $user->setUpdatedAt(new DateTimeImmutable());

        if (isset($request->toArray()['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        }

        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
