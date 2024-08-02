<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\EntityStatusEnum;
use App\Exception\ResourceNotFoundException;
use App\Repository\Interface\EventRepositoryInterface;
use Doctrine\Persistence\ObjectRepository;
use MapasCulturais\Entities\Event;
use MapasCulturais\Entities\EventOccurrence;

class EventRepository extends AbstractRepository implements EventRepositoryInterface
{
    private ObjectRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = $this->mapaCulturalEntityManager->getRepository(Event::class);
    }

    public function findAll(): array
    {
        return $this->repository
            ->createQueryBuilder('events')
            ->where('events.status = :status')
            ->setParameter('status', EntityStatusEnum::ENABLED->getValue())
            ->getQuery()
            ->getArrayResult();
    }

    public function find(int $id): Event
    {
        $event = $this->repository->findOneBy([
            'id' => $id,
            'status' => EntityStatusEnum::ENABLED->getValue(),
        ]);

        if (null === $event) {
            throw new ResourceNotFoundException();
        }

        return $event;
    }

    public function findEventsBySpaceId(int $spaceId): array
    {
        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->select([
                'e.id',
                'e.name',
                'e.shortDescription',
                'eo.startsOn',
                'eo.endsOn',
                'eo.startsAt',
                'eo.endsAt',
                'eo.price',
                'eo.priceInfo',
                'eo.frequency',
            ])
            ->from(EventOccurrence::class, 'eo')
            ->join(Event::class, 'e', 'WITH', 'eo.event = e.id')
            ->where('eo.space = :spaceId')
            ->setParameter(':spaceId', $spaceId);

        return $queryBuilder->getQuery()->getResult();
    }

    public function save($event): void
    {
        $event->save();
    }

    public function remove(Event $event): void
    {
        $event->setStatus(EntityStatusEnum::TRASH->getValue());
        $this->mapaCulturalEntityManager->persist($event);
        $this->mapaCulturalEntityManager->flush();
    }

    public function update(Event $event): void
    {
        $this->mapaCulturalEntityManager->persist($event);
        $this->mapaCulturalEntityManager->flush();
    }
}
