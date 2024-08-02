<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\FieldRequiredException;
use App\Repository\Interface\ProjectRepositoryInterface;
use App\Service\Interface\ProjectServiceInterface;
use MapasCulturais\Entities\Project;
use Symfony\Component\Serializer\SerializerInterface;

class ProjectService implements ProjectServiceInterface
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function create(mixed $data): Project
    {
        if (true === empty($data->name)) {
            throw new FieldRequiredException('The name field is required.');
        }
        if (true === empty($data->shortDescription)) {
            throw new FieldRequiredException('The shortDescription field is required.');
        }
        if (true === empty($data->type)) {
            throw new FieldRequiredException('The type field is required.');
        }

        $project = new Project();
        $project->setName($data->name);
        $project->setShortDescription($data->shortDescription);
        $project->setType($data->type);

        $this->projectRepository->save($project);

        return $project;
    }

    public function update(int $id, object $data): Project
    {
        $projectFromDB = $this->projectRepository->find($id);

        if (true === empty($data->name)) {
            throw new FieldRequiredException('name');
        }

        $projectUpdated = $this->serializer->denormalize(
            data: $data,
            type: Project::class,
            context: ['object_to_populate' => $projectFromDB]
        );

        $projectUpdated->saveTerms();
        $projectUpdated->saveMetadata();

        $this->projectRepository->save($projectUpdated);

        return $projectUpdated;
    }

    public function removeById(int $id): void
    {
        $project = $this->projectRepository->find($id);
        $this->projectRepository->remove($project);
    }
}
