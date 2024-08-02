<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\EntityStatusEnum;
use App\Exception\ResourceNotFoundException;
use App\Repository\Interface\SealRepositoryInterface;
use Doctrine\Persistence\ObjectRepository;
use MapasCulturais\Entities\Seal;

class SealRepository extends AbstractRepository implements SealRepositoryInterface
{
    private ObjectRepository $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = $this->mapaCulturalEntityManager->getRepository(Seal::class);
    }

    public function findAll(): array
    {
        return $this->repository
            ->createQueryBuilder('seal')
            ->where('seal.status = :status')
            ->setParameter('status', EntityStatusEnum::ENABLED->getValue())
            ->getQuery()
            ->getArrayResult();
    }

    public function find(int $id): Seal
    {
        $seal = $this->repository->findOneBy([
            'id' => $id,
            'status' => EntityStatusEnum::ENABLED->getValue(),
        ]);

        if (null === $seal) {
            throw new ResourceNotFoundException();
        }

        return $seal;
    }

    public function save(Seal $seal): void
    {
        $this->mapaCulturalEntityManager->persist($seal);
        $this->mapaCulturalEntityManager->flush();
    }

    public function remove(Seal $seal): void
    {
        $seal->setStatus(EntityStatusEnum::TRASH->getValue());
        $this->mapaCulturalEntityManager->persist($seal);
        $this->mapaCulturalEntityManager->flush();
    }
}
