<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Interface\OpportunityRepositoryInterface;
use App\Service\Interface\OpportunityServiceInterface;
use MapasCulturais\Entities\Opportunity;
use Symfony\Component\Serializer\SerializerInterface;

class OpportunityService implements OpportunityServiceInterface
{
    public const FILE_TYPES = '/src/conf/opportunity-types.php';

    public function __construct(
        private readonly OpportunityRepositoryInterface $repository,
        private readonly SerializerInterface $serializer
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

    public function update(int $id, object $data): Opportunity
    {
        $opportunityFromDB = $this->repository->find($id);
        $opportunityUpdated = $this->serializer->denormalize(
            data: $data,
            type: Opportunity::class,
            context: ['object_to_populate' => $opportunityFromDB]
        );

        $opportunityUpdated->saveTerms();
        $this->repository->save($opportunityUpdated);

        return $opportunityUpdated;
    }

    public function create(mixed $data): Opportunity
    {
        $opportunity = new Opportunity();

        $opportunity->setType($data['opportunityType']);
        $opportunity->setName($data['name']);
        $opportunity->terms['area'] = $data['terms']['area'];

        if (true === isset($data['project'])) {
            $opportunity->setObjectType("MapasCulturais\Entities\Project");
            $opportunity->setProject($data['project']);
        }
        if (true === isset($data['event'])) {
            $opportunity->setObjectType("MapasCulturais\Entities\Event");
            $opportunity->setEvent($data['event']);
        }
        if (true === isset($data['space'])) {
            $opportunity->setObjectType("MapasCulturais\Entities\Space");
            $opportunity->setSpace($data['space']);
        }
        if (true === isset($data['agent'])) {
            $opportunity->setObjectType("MapasCulturais\Entities\Agent");
            $opportunity->setAgent($data['agent']);
        }

        $this->repository->save($opportunity);

        return $opportunity;
    }

    public function removeById(int $id): void
    {
        $opportunity = $this->repository->find($id);
        $this->repository->remove($opportunity);
    }
}
