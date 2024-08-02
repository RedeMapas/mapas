<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Interface\SpaceRepositoryInterface;
use App\Service\Interface\SpaceServiceInterface;
use MapasCulturais\Entities\Space;
use Symfony\Component\Serializer\SerializerInterface;

class SpaceService implements SpaceServiceInterface
{
    public const FILE_TYPES = '/src/conf/space-types.php';

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly SpaceRepositoryInterface $repository
    ) {
    }

    public function getTypes(): array
    {
        $typesFromConf = (require dirname(__DIR__, 3).self::FILE_TYPES)['items'] ?? [];

        return array_map(
            fn ($key, $item) => ['id' => $key, 'name' => $item['name']],
            array_keys($typesFromConf),
            $typesFromConf
        );
    }

    public function create(mixed $data): Space
    {
        $space = new Space();

        $space->setName($data->name);
        $space->setShortDescription($data->shortDescription);
        $space->setType($data->type);
        $space->terms['area'] = $data->terms['area'];
        $space->saveTerms();

        $this->repository->save($space);

        return $space;
    }

    public function update(int $id, object $data): Space
    {
        $spaceFromDB = $this->repository->find($id);
        $spaceUpdated = $this->serializer->denormalize($data, Space::class, context: ['object_to_populate' => $spaceFromDB]);

        $this->repository->save($spaceUpdated);

        return $spaceUpdated;
    }

    public function removeById(int $id): void
    {
        $space = $this->repository->find($id);
        $this->repository->remove($space);
    }
}
