<?php
declare(strict_types=1);

namespace ActivityPub;

use MapasCulturais\App;

class Url
{
    public static function resolveBaseUrl(App $app): string
    {
        $configured = trim((string) ($app->config['activitypub.domain'] ?? ''));
        $appBaseUrl = trim((string) ($app->config['base.url'] ?? ''));

        if ($configured !== '') {
            return self::normalizeConfiguredBaseUrl($configured, $appBaseUrl);
        }

        return self::normalize($appBaseUrl);
    }

    public static function normalize(string $baseUrlOrAuthority): string
    {
        $baseUrlOrAuthority = rtrim(trim($baseUrlOrAuthority), '/');

        if ($baseUrlOrAuthority === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $baseUrlOrAuthority)) {
            return $baseUrlOrAuthority;
        }

        return "https://{$baseUrlOrAuthority}";
    }

    public static function authority(string $baseUrlOrAuthority): string
    {
        $normalized = self::normalize($baseUrlOrAuthority);
        $host = (string) parse_url($normalized, PHP_URL_HOST);
        $port = parse_url($normalized, PHP_URL_PORT);

        if ($host === '') {
            return '';
        }

        return $port ? "{$host}:{$port}" : $host;
    }

    public static function actor(string $baseUrlOrAuthority, string $slug): string
    {
        $baseUrl = self::normalize($baseUrlOrAuthority);
        return "{$baseUrl}/activitypub/agent/{$slug}";
    }

    private static function normalizeConfiguredBaseUrl(string $configured, string $appBaseUrl): string
    {
        if (preg_match('#^https?://#i', $configured)) {
            return rtrim($configured, '/');
        }

        $scheme = (string) parse_url($appBaseUrl, PHP_URL_SCHEME);
        if ($scheme === '') {
            $scheme = 'https';
        }

        return "{$scheme}://{$configured}";
    }
}
