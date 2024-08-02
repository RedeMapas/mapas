<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Interface\AgentRepositoryInterface;
use App\Repository\Interface\UserRepositoryInterface;
use App\Service\Interface\AgentServiceInterface;
use MapasCulturais\Entities\Agent;
use Symfony\Component\Serializer\SerializerInterface;

class AgentService implements AgentServiceInterface
{
    public const FILE_TYPES = '/src/conf/agent-types.php';

    public function __construct(
        private readonly AgentRepositoryInterface $repository,
        private readonly UserRepositoryInterface $userRepository,
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

    public function update(int $id, object $data): Agent
    {
        $agentFromDB = $this->repository->find($id);

        $agentUpdated = $this->serializer->denormalize(
            data: $data,
            type: Agent::class,
            context: ['object_to_populate' => $agentFromDB]
        );

        $agentUpdated->saveTerms();
        $this->repository->save($agentUpdated);

        return $agentUpdated;
    }

    public function create(mixed $data): Agent
    {
        $user = $this->userRepository->find(1);

        $agent = new Agent($user);
        $agent->setName($data->name);
        $agent->setShortDescription($data->shortDescription);
        $agent->setType($data->type);

        $this->repository->save($agent);

        return $agent;
    }

    public function removeById(int $id): void
    {
        $agent = $this->repository->find($id);
        $this->repository->remove($agent);
    }
}
