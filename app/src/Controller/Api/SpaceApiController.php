<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Exception\FieldInvalidException;
use App\Exception\FieldRequiredException;
use App\Repository\Interface\SpaceRepositoryInterface;
use App\Request\SpaceRequest;
use App\Service\Interface\SpaceServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SpaceApiController extends AbstractApiController
{
    public function __construct(
        private readonly SpaceRepositoryInterface $repository,
        private readonly SpaceServiceInterface $spaceService,
        private readonly SpaceRequest $spaceRequest
    ) {
    }

    public function getList(): JsonResponse
    {
        $spaces = $this->repository->findAll();

        return new JsonResponse($spaces);
    }

    public function getOne(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $space = $this->repository->find($id);

        return new JsonResponse($space);
    }

    public function post(): JsonResponse
    {
        $spaceData = $this->spaceRequest->validatePost();

        if (true === empty($spaceData['name'])) {
            throw new FieldRequiredException('name');
        }

        if (false === is_string($spaceData['name'])) {
            throw new FieldInvalidException('name');
        }

        $space = $this->spaceService->create((object) $spaceData);

        $responseData = [
            'id' => $space->getId(),
            'name' => $space->getName(),
            'shortDescription' => $space->getShortDescription(),
            'terms' => $space->getTerms(),
            'type' => $space->getType(),
        ];

        return new JsonResponse($responseData, Response::HTTP_CREATED);
    }

    public function patch(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $spaceData = $this->spaceRequest->validateUpdate();

        if (true === empty($spaceData['name'])) {
            throw new FieldRequiredException('name');
        }

        if (false === is_string($spaceData['name'])) {
            throw new FieldInvalidException('name');
        }

        $space = $this->spaceService->update($id, (object) $spaceData);

        return new JsonResponse($space, Response::HTTP_CREATED);
    }

    public function remove(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $this->spaceService->removeById($id);

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
