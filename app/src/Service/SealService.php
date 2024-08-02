<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Interface\AgentRepositoryInterface;
use App\Repository\Interface\SealRepositoryInterface;
use App\Service\Interface\SealServiceInterface;
use MapasCulturais\Entities\Seal;
use Symfony\Component\Serializer\SerializerInterface;

class SealService extends AbstractService implements SealServiceInterface
{
    public function __construct(
        private readonly AgentRepositoryInterface $agentRepository,
        private readonly SealRepositoryInterface $sealRepository,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function create(mixed $data): Seal
    {
        $seal = $this->serializer->denormalize($data, Seal::class);
        $this->setProperty($seal, 'owner', $this->agentRepository->find(1));
        $this->sealRepository->save($seal);

        return $seal;
    }

    public function removeById(int $id): void
    {
        $seal = $this->sealRepository->find($id);
        $this->sealRepository->remove($seal);
    }

    public function update(int $id, object $data): Seal
    {
        $sealFromDB = $this->sealRepository->find($id);

        $sealUpdated = $this->serializer->denormalize(
            data: $data,
            type: Seal::class,
            context: ['object_to_populate' => $sealFromDB]
        );

        $this->sealRepository->save($sealUpdated);

        return $sealUpdated;
    }
}
