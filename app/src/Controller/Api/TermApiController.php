<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\Interface\TermRepositoryInterface;
use App\Request\TermRequest;
use App\Service\Interface\TermServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TermApiController extends AbstractApiController
{
    public function __construct(
        private readonly TermRepositoryInterface $repository,
        private readonly TermRequest $termRequest,
        private readonly TermServiceInterface $service
    ) {
    }

    public function getList(): JsonResponse
    {
        $terms = $this->repository->findAll();

        return new JsonResponse($terms);
    }

    public function getOne(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $term = $this->repository->find($id);

        return new JsonResponse($term);
    }

    public function post(): JsonResponse
    {
        $termData = $this->termRequest->validatePost();
        $term = $this->service->create($termData);

        return new JsonResponse($term, Response::HTTP_CREATED);
    }

    public function patch(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $termData = $this->termRequest->validatePatch();
        $term = $this->service->update($id, $termData);

        return new JsonResponse($term, Response::HTTP_OK);
    }

    public function remove(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $this->service->removeById($id);

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
