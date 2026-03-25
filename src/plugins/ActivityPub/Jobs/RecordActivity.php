<?php
declare(strict_types=1);

namespace ActivityPub\Jobs;

use ActivityPub\ActivityBuilder;
use ActivityPub\ActorBuilder;
use ActivityPub\Url;
use MapasCulturais\App;
use MapasCulturais\Definitions\JobType;
use MapasCulturais\Entities\Job;

class RecordActivity extends JobType
{
    public const SLUG = 'activitypub.record_activity';

    public function __construct()
    {
        parent::__construct(self::SLUG);
    }

    protected function _generateId(array $data, string $start_string, string $interval_string, int $iterations): string
    {
        // ID único por (type, entityClass, entityId) — evita jobs duplicados na fila
        return md5("{$data['activityType']}:{$data['entityClass']}:{$data['entityId']}");
    }

    protected function _execute(Job $job): bool
    {
        $app  = App::i();

        // No Mapas, dados do job são acessados diretamente como propriedades via __get()
        $activityType = $job->activityType ?? null;
        $entityClass  = $job->entityClass  ?? null;
        $entityId     = (int) ($job->entityId ?? 0);

        if (!$activityType || !$entityClass || !$entityId) {
            $app->log->warning("[activitypub] Job com payload inválido");
            return true;
        }

        // 1. Carregar entidade
        $entity = $app->repo($entityClass)->find($entityId);
        if (!$entity) {
            return true; // entidade deletada — ok, ack
        }

        // 2. Verificar status da entidade
        if (($entity->status ?? 0) < 1) {
            return true; // rascunho / lixeira — skip
        }

        // 3. Resolver actor
        if ($entityClass === 'MapasCulturais\Entities\AgentRelation') {
            $actor = $entity->agent ?? null;
        } else {
            $actor = $entity->ownerAgent ?? null;
        }

        if (!$actor || ($actor->status ?? 0) < 1) {
            return true; // actor inativo — skip
        }

        // 4. Computar activity_id
        $baseUrl   = Url::resolveBaseUrl($app);
        $actorUri  = Url::actor($baseUrl, ActorBuilder::slugify((string) ($actor->name ?? '')));
        $tsForHash = match ($activityType) {
            'Create' => ($entity->createTimestamp instanceof \DateTimeInterface)
                            ? $entity->createTimestamp->getTimestamp()
                            : time(),
            default  => time(), // cada Update é único (acumula histórico)
        };
        $hash       = substr(hash('sha256', "{$activityType}:{$entityClass}:{$entityId}:{$tsForHash}"), 0, 16);
        $activityId = "{$actorUri}/activities/{$hash}";

        // 5. Construir payload JSON-LD
        $payload = ActivityBuilder::build($activityType, $entity, $entityClass, $actor, $baseUrl, $activityId);

        // 6. Persistir
        $conn       = $app->em->getConnection();
        $objectType = $this->shortClass($entityClass);
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $published   = $payload['published'];

        try {
            if ($activityType === 'Create') {
                // Partial index dedup: ON CONFLICT com colunas + WHERE clause
                $conn->executeQuery(
                    "INSERT INTO activitypub_activity
                        (agent_id, activity_id, type, object_type, object_id, payload, published)
                     VALUES
                        (:agent_id, :activity_id, :type, :object_type, :object_id, :payload, :published)
                     ON CONFLICT (agent_id, object_type, object_id) WHERE type = 'Create'
                     DO NOTHING",
                    [
                        'agent_id'    => $actor->id,
                        'activity_id' => $activityId,
                        'type'        => $activityType,
                        'object_type' => $objectType,
                        'object_id'   => $entityId,
                        'payload'     => $payloadJson,
                        'published'   => $published,
                    ]
                );
            } else {
                // Update/Announce/Add: acumula — sem dedup, mas guarda contra activity_id duplicado
                $conn->executeQuery(
                    "INSERT INTO activitypub_activity
                        (agent_id, activity_id, type, object_type, object_id, payload, published)
                     VALUES
                        (:agent_id, :activity_id, :type, :object_type, :object_id, :payload, :published)
                     ON CONFLICT ON CONSTRAINT activitypub_activity_id_unique
                     DO NOTHING",
                    [
                        'agent_id'    => $actor->id,
                        'activity_id' => $activityId,
                        'type'        => $activityType,
                        'object_type' => $objectType,
                        'object_id'   => $entityId,
                        'payload'     => $payloadJson,
                        'published'   => $published,
                    ]
                );
            }
        } catch (\Throwable $e) {
            $app->log->error("[activitypub] Erro ao persistir activity: " . $e->getMessage());
            return false; // retentar
        }

        return true;
    }

    private function shortClass(string $entityClass): string
    {
        return (string) substr(strrchr($entityClass, '\\'), 1);
    }
}
