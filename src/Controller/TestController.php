<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/api/test', name: 'api_test', methods: ['POST'])]
    #[OA\Post(
        summary: "Test endpoint avec body",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "foo", type: "string", example: "bar"),
                    new OA\Property(property: "number", type: "integer", example: 42),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "result", type: "string", example: "success"),
                    ]
                )
            )
        ]
    )]
    public function test(Request $request): JsonResponse
    {
        return new JsonResponse(['result' => 'success']);
    }
}