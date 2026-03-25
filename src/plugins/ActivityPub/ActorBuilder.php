<?php
declare(strict_types=1);

namespace ActivityPub;

class ActorBuilder
{
    public static function slugify(string $name): string
    {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($slug === false || $slug === '') {
            $slug = $name;
        }
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }

    public static function build(object $agent, string $baseUrlOrAuthority): array
    {
        $slug = self::slugify((string) ($agent->name ?? ''));
        $base = Url::actor($baseUrlOrAuthority, $slug);

        $actor = [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
            ],
            'type'              => 'Person',
            'id'                => $base,
            'preferredUsername' => $slug,
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
