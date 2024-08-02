<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\EntityStatusEnum;
use App\Exception\ResourceNotFoundException;
use App\Repository\Interface\SpaceRepositoryInterface;
use Doctrine\Persistence\ObjectRepository;
use MapasCulturais\Entities\Space;

class SpaceRepository extends AbstractRepository implements SpaceRepositoryInterface
{
    private ObjectRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = $this->mapaCulturalEntityManager->getRepository(Space::class);
    }

    public function save(Space $space): void
    {
        $space->saveMetadata();
        $space->saveTerms();
        $this->mapaCulturalEntityManager->persist($space);
        $this->mapaCulturalEntityManager->flush();
    }

    public function findAll(): array
    {
        return $this->repository
            ->createQueryBuilder('space')
            ->where('space.status = :status')
            ->setParameter('status', EntityStatusEnum::ENABLED->getValue())
            ->getQuery()
            ->getArrayResult();
    }

    public function find(int $id): Space
    {
        $space = $this->repository->findOneBy([
            'id' => $id,
            'status' => EntityStatusEnum::ENABLED->getValue(),
        ]);

        if (null === $space) {
            throw new ResourceNotFoundException();
        }

        return $space;
    }

    public function remove(Space $space): void
    {
        $space->setStatus(EntityStatusEnum::TRASH->getValue());
        $this->mapaCulturalEntityManager->persist($space);
        $this->mapaCulturalEntityManager->flush();
    }
}
