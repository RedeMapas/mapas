<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\EntityStatusEnum;
use App\Exception\ResourceNotFoundException;
use App\Repository\Interface\UserRepositoryInterface;
use Doctrine\Persistence\ObjectRepository;
use MapasCulturais\Entities\User;
use MapasCulturais\Entities\UserMeta;

class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    private ObjectRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = $this->mapaCulturalEntityManager->getRepository(User::class);
    }

    public function findAll(): array
    {
        return $this->repository
            ->createQueryBuilder('user')
            ->where('User.status = :status')
            ->setParameter('status', EntityStatusEnum::ENABLED->getValue())
            ->getQuery()
            ->getArrayResult();
    }

    public function getUserMetadata(int $userId): mixed
    {
        return $this->mapaCulturalEntityManager
            ->getRepository(UserMeta::class)
            ->findOneBy(['owner' => $userId]);
    }

    public function find(int $id): User
    {
        $user = $this->repository->findOneBy([
            'id' => $id,
            'status' => EntityStatusEnum::ENABLED->getValue(),
        ]);

        if (null === $user) {
            throw new ResourceNotFoundException();
        }

        return $user;
    }

    public function save(User $user): void
    {
        $this->mapaCulturalEntityManager->persist($user);
        $this->mapaCulturalEntityManager->flush();
    }

    public function findOneBy(array $params): ?User
    {
        $params['status'] = EntityStatusEnum::ENABLED->getValue();

        return $this->repository->findOneBy($params);
    }

    public function findBy(array $params): array
    {
        return $this->repository->findBy($params);
    }
}
