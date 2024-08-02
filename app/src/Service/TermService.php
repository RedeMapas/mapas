<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Interface\TermRepositoryInterface;
use App\Service\Interface\TermServiceInterface;
use MapasCulturais\Entities\Term;
use Symfony\Component\Serializer\SerializerInterface;

class TermService extends AbstractService implements TermServiceInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly TermRepositoryInterface $termRepository
    ) {
    }

    public function create($data): Term
    {
        $term = $this->serializer->denormalize($data, Term::class);
        $this->termRepository->save($term);

        return $term;
    }

    public function update(int $id, array $termData): Term
    {
        $termFromDB = $this->termRepository->find($id);

        $termUpdated = $this->serializer->denormalize(
            data: $termData,
            type: Term::class,
            context: ['object_to_populate' => $termFromDB]
        );

        $this->termRepository->save($termUpdated);

        return $termUpdated;
    }

    public function removeById(int $id): void
    {
        $term = $this->termRepository->find($id);
        $this->termRepository->remove($term);
    }
}
