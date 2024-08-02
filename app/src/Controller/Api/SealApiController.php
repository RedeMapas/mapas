<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\Interface\SealRepositoryInterface;
use App\Request\SealRequest;
use App\Service\Interface\SealServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SealApiController extends AbstractApiController
{
    public function __construct(
        private readonly SealRepositoryInterface $repository,
        private readonly SealServiceInterface $sealService,
        private readonly SealRequest $sealRequest
    ) {
    }

    public function getList(): JsonResponse
    {
        $seals = $this->repository->findAll();

        return new JsonResponse($seals);
    }

    public function getOne(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $seal = $this->repository->find($id);

        return new JsonResponse($seal);
    }

    public function post(): JsonResponse
    {
        $sealData = $this->sealRequest->validatePost();
        $seal = $this->sealService->create($sealData);

        return new JsonResponse($seal, Response::HTTP_CREATED);
    }

    public function patch(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $sealData = $this->sealRequest->validatePatch();
        $seal = $this->sealService->update($id, (object) $sealData);

        return new JsonResponse($seal, Response::HTTP_CREATED);
    }

    public function remove(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $this->sealService->removeById($id);

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
