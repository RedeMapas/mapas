<?php
declare(strict_types=1);

namespace ActivityPub;

class ActorBuilder
{
    public static function build(object $agent, string $domain): array
    {
        $agentId = (string) ($agent->id ?? '');
        $base = "https://{$domain}/activitypub/agent/{$agentId}";

        $actor = [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
            ],
            'type'              => 'Person',
            'id'                => $base,
            'preferredUsername' => $agentId,
            'name'              => $agent->name ?? '',
            'summary'           => $agent->shortDescription ?? '',
            'url'               => $agent->singleUrl ?? $base,
            'inbox'             => "{$base}/inbox",
            'outbox'            => "{$base}/outbox",
            'publicKey'         => [
                'id'           => "{$base}#main-key",
                'owner'        => $base,
                'publicKeyPem' => '',
            ],
        ];

        // Avatar: Agent real expõe $agent->avatar (File object com ->url).
        $avatarUrl = null;
        $avatar = $agent->avatar ?? null;
        if ($avatar !== null) {
            $avatarUrl = $avatar->url ?? null;
        }

        if ($avatarUrl) {
            $actor['icon'] = [
                'type' => 'Image',
                'url'  => $avatarUrl,
            ];
        }

        return $actor;
    }
}
