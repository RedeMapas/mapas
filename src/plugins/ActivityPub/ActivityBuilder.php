<?php
declare(strict_types=1);

namespace ActivityPub;

use ActivityPub\ActorBuilder;

class ActivityBuilder
{
    private const OBJECT_TYPES = [
        'MapasCulturais\Entities\Event'         => 'Event',
        'MapasCulturais\Entities\Space'         => 'Place',
        'MapasCulturais\Entities\Project'       => 'Note',
        'MapasCulturais\Entities\Opportunity'   => 'Note',
        'MapasCulturais\Entities\Registration'  => 'Note',
        'MapasCulturais\Entities\AgentRelation' => 'Relationship',
    ];

    public static function build(
        string $activityType,
        object $entity,
        string $entityClass,
        object $actor,
        string $domain,
        string $activityId
    ): array {
        $actorUri  = "https://{$domain}/activitypub/agent/" . ActorBuilder::slugify((string) ($actor->name ?? ''));
        $published = self::resolvePublished($activityType, $entity);

        return [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'type'      => $activityType,
            'id'        => $activityId,
            'actor'     => $actorUri,
            'published' => $published,
            'object'    => self::buildObject($entityClass, $entity, $actorUri, $domain),
        ];
    }

    private static function resolvePublished(string $activityType, object $entity): string
    {
        $ts = match ($activityType) {
            'Create' => $entity->createTimestamp ?? new \DateTime(),
            default  => $entity->updateTimestamp ?? $entity->createTimestamp ?? new \DateTime(),
        };

        if (!$ts instanceof \DateTimeInterface) {
            $ts = new \DateTime();
        }

        return $ts->format(\DateTime::ATOM);
    }

    private static function buildObject(
        string $entityClass,
        object $entity,
        string $actorUri,
        string $domain
    ): array {
        $type = self::OBJECT_TYPES[$entityClass] ?? 'Note';
        $base = ['type' => $type, 'attributedTo' => $actorUri];

        return match ($entityClass) {
            'MapasCulturais\Entities\Event'         => $base + self::eventObject($entity),
            'MapasCulturais\Entities\Space'         => $base + self::spaceObject($entity),
            'MapasCulturais\Entities\Project'       => $base + self::noteObject($entity),
            'MapasCulturais\Entities\Opportunity'   => $base + self::noteObject($entity),
            'MapasCulturais\Entities\Registration'  => $base + self::registrationObject($entity),
            'MapasCulturais\Entities\AgentRelation' => $base + self::relationObject($entity, $actorUri),
            default                                 => $base + self::noteObject($entity),
        };
    }

    private static function eventObject(object $entity): array
    {
        $obj = ['name' => $entity->name ?? '', 'url' => $entity->singleUrl ?? ''];
        $occ = ($entity->occurrences ?? [])[0] ?? null;
        if ($occ && isset($occ->startsOn)) {
            $obj['startTime'] = (new \DateTime((string) $occ->startsOn))->format(\DateTime::ATOM);
        }
        return $obj;
    }

    private static function spaceObject(object $entity): array
    {
        $obj = ['name' => $entity->name ?? '', 'url' => $entity->singleUrl ?? ''];
        $loc = $entity->location ?? null;
        if ($loc) {
            $obj['latitude']  = $loc->latitude ?? null;
            $obj['longitude'] = $loc->longitude ?? null;
        }
        return $obj;
    }

    private static function noteObject(object $entity): array
    {
        return [
            'name'    => $entity->name ?? '',
            'content' => $entity->shortDescription ?? '',
            'url'     => $entity->singleUrl ?? '',
        ];
    }

    private static function registrationObject(object $entity): array
    {
        $opp    = $entity->opportunity ?? null;
        $oppName = $opp->name ?? 'edital';
        $oppUrl  = $opp->singleUrl ?? '';
        return ['content' => "Inscreveu-se em {$oppName}", 'url' => $oppUrl];
    }

    private static function relationObject(object $entity, string $actorUri): array
    {
        $ownerUrl = $entity->owner->singleUrl ?? '';
        return [
            'subject'      => $actorUri,
            'relationship' => 'administrator',
            'object'       => $ownerUrl,
        ];
    }
}
