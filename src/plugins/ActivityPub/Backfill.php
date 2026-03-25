<?php
declare(strict_types=1);

namespace ActivityPub;

use ActivityPub\Jobs\RecordActivity;
use MapasCulturais\App;
use MapasCulturais\Entity;

class Backfill
{
    /**
     * @var array<string, array{class: string, activityType: string}>
     */
    private const ENTITY_MAP = [
        'event' => [
            'class' => 'MapasCulturais\\Entities\\Event',
            'activityType' => 'Create',
        ],
        'space' => [
            'class' => 'MapasCulturais\\Entities\\Space',
            'activityType' => 'Create',
        ],
        'project' => [
            'class' => 'MapasCulturais\\Entities\\Project',
            'activityType' => 'Create',
        ],
        'opportunity' => [
            'class' => 'MapasCulturais\\Entities\\Opportunity',
            'activityType' => 'Create',
        ],
        'registration' => [
            'class' => 'MapasCulturais\\Entities\\Registration',
            'activityType' => 'Announce',
        ],
    ];

    public function __construct(
        private readonly ?App $app = null
    ) {
    }

    /**
     * @param array<string, mixed> $options
     * @return array{dryRun: bool, totals: array<string, int>, enqueued: array<string, int>}
     */
    public function run(array $options = []): array
    {
        $app = $this->app ?? App::i();
        if (!$app->getRegisteredJobType(RecordActivity::SLUG)) {
            throw new \RuntimeException('ActivityPub is disabled or RecordActivity is not registered');
        }

        $dryRun = (bool) ($options['dry-run'] ?? false);
        $agentId = isset($options['agent-id']) ? (int) $options['agent-id'] : null;

        $keys = $this->resolveEntityKeys($options);
        $report = [
            'dryRun' => $dryRun,
            'totals' => [],
            'enqueued' => [],
        ];

        foreach ($keys as $key) {
            $config = self::ENTITY_MAP[$key];
            $label = $this->shortClass($config['class']);
            $entities = $this->findEligibleEntities($config['class'], $agentId, $app);

            $report['totals'][$label] = count($entities);
            $report['enqueued'][$label] = 0;

            if ($dryRun) {
                continue;
            }

            foreach ($entities as $entity) {
                $app->enqueueJob(RecordActivity::SLUG, [
                    'activityType' => $config['activityType'],
                    'entityClass' => $config['class'],
                    'entityId' => (int) $entity->id,
                ]);
                $report['enqueued'][$label]++;
            }
        }

        return $report;
    }

    /**
     * @param array<string, mixed> $options
     * @return list<string>
     */
    private function resolveEntityKeys(array $options): array
    {
        $entity = strtolower((string) ($options['entity'] ?? 'all'));

        if ($entity && $entity !== 'all') {
            if (!isset(self::ENTITY_MAP[$entity])) {
                throw new \InvalidArgumentException("Unknown entity '{$entity}'");
            }

            return [$entity];
        }

        $keys = ['event', 'space', 'project', 'opportunity'];
        if (!empty($options['include-registrations'])) {
            $keys[] = 'registration';
        }

        return $keys;
    }

    /**
     * @return list<Entity>
     */
    private function findEligibleEntities(string $entityClass, ?int $agentId, App $app): array
    {
        /** @var list<Entity> $entities */
        $entities = $app->repo($entityClass)->findAll();

        return array_values(array_filter($entities, function (Entity $entity) use ($agentId): bool {
            if (((int) ($entity->status ?? 0)) < 1 || !(int) ($entity->id ?? 0)) {
                return false;
            }

            $actor = $entity->ownerAgent ?? null;
            if (!$actor || ((int) ($actor->status ?? 0)) < 1) {
                return false;
            }

            if ($agentId && (int) ($actor->id ?? 0) !== $agentId) {
                return false;
            }

            return true;
        }));
    }

    private function shortClass(string $entityClass): string
    {
        return (string) substr(strrchr($entityClass, '\\'), 1);
    }
}
