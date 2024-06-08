<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\ProjectRepository;
use App\Request\ProjectRequest;
use App\Service\ProjectService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectApiController extends AbstractApiController
{
    public function __construct(
        private ProjectService $projectService,
        private ProjectRequest $projectRequest,
        private ProjectRepository $repository
    ) {
    }

    public function getList(): JsonResponse
    {
        $projects = $this->repository->findAll();

        return new JsonResponse($projects);
    }

    public function getOne(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $project = $this->repository->find($id);

        return new JsonResponse($project);
    }

    public function post(): JsonResponse
    {
        $projectData = $this->projectRequest->validatePost();

        $project = $this->projectService->create((object) $projectData);

        $responseData = [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'shortDescription' => $project->getShortDescription(),
            'type' => $project->getType(),
        ];

        return new JsonResponse($responseData, status: Response::HTTP_CREATED);
    }

    public function patch(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $projectData = $this->projectRequest->validateUpdate();
        $project = $this->projectService->update($id, (object) $projectData);

        return new JsonResponse($project, Response::HTTP_CREATED);
    }

    public function delete(array $params): JsonResponse
    {
        $id = $this->extractIdParam($params);
        $this->projectService->discard($id);

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
