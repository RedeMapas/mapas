<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\EntityStatusEnum;
use App\Exception\ResourceNotFoundException;
use App\Repository\Interface\ProjectRepositoryInterface;
use Doctrine\Persistence\ObjectRepository;
use MapasCulturais\Entities\Project;

class ProjectRepository extends AbstractRepository implements ProjectRepositoryInterface
{
    private ObjectRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = $this->mapaCulturalEntityManager->getRepository(Project::class);
    }

    public function findAll(): array
    {
        return $this->repository
            ->createQueryBuilder('project')
            ->where('project.status = :status')
            ->setParameter('status', EntityStatusEnum::ENABLED->getValue())
            ->getQuery()
            ->getArrayResult();
    }

    public function find(int $id): Project
    {
        $project = $this->repository->findOneBy([
            'id' => $id,
            'status' => EntityStatusEnum::ENABLED->getValue(),
        ]);

        if (null === $project) {
            throw new ResourceNotFoundException();
        }

        return $project;
    }

    public function save(Project $project): void
    {
        $this->mapaCulturalEntityManager->persist($project);
        $this->mapaCulturalEntityManager->flush();
    }

    public function remove(Project $project): void
    {
        $project->setStatus(EntityStatusEnum::TRASH->getValue());
        $this->mapaCulturalEntityManager->persist($project);
        $this->mapaCulturalEntityManager->flush();
    }
}
