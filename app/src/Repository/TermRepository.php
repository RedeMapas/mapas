<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\ResourceNotFoundException;
use App\Repository\Interface\TermRepositoryInterface;
use Doctrine\Persistence\ObjectRepository;
use MapasCulturais\Entities\Term;

class TermRepository extends AbstractRepository implements TermRepositoryInterface
{
    private ObjectRepository $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = $this->mapaCulturalEntityManager->getRepository(Term::class);
    }

    public function findAll(): array
    {
        return $this->repository
            ->createQueryBuilder('term')
            ->getQuery()
            ->getArrayResult();
    }

    public function find(int $id): Term
    {
        $term = $this->repository->findOneBy([
            'id' => $id,
        ]);

        if (null === $term) {
            throw new ResourceNotFoundException();
        }

        return $term;
    }

    public function save(Term $term): void
    {
        $this->mapaCulturalEntityManager->persist($term);
        $this->mapaCulturalEntityManager->flush();
    }

    public function remove(Term $term): void
    {
        $this->mapaCulturalEntityManager->remove($term);
        $this->mapaCulturalEntityManager->flush();
    }
}
