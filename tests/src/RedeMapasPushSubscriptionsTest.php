<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/themes/RedeMapas/Push/SubscriptionStore.php';

use MapasCulturais\Themes\RedeMapas\Push\SubscriptionStore;

class RedeMapasPushSubscriptionsTest extends TestCase
{
    public function testUpsertAddsNewEndpoint(): void
    {
        $current = [];
        $incoming = [
            'endpoint' => 'https://push.example/abc',
            'keys' => [
                'p256dh' => 'k1',
                'auth' => 'a1',
            ],
        ];

        $result = SubscriptionStore::upsert($current, $incoming, 'UA-Test');

        $this->assertCount(1, $result);
        $this->assertSame('https://push.example/abc', $result[0]['endpoint']);
        $this->assertSame('k1', $result[0]['keys']['p256dh']);
        $this->assertSame('a1', $result[0]['keys']['auth']);
        $this->assertSame('UA-Test', $result[0]['userAgent']);
    }

    public function testUpsertUpdatesSameEndpointInsteadOfDuplicating(): void
    {
        $current = [[
            'endpoint' => 'https://push.example/abc',
            'keys' => ['p256dh' => 'old', 'auth' => 'old-auth'],
            'userAgent' => 'Old-UA',
        ]];

        $incoming = [
            'endpoint' => 'https://push.example/abc',
            'keys' => [
                'p256dh' => 'new',
                'auth' => 'new-auth',
            ],
        ];

        $result = SubscriptionStore::upsert($current, $incoming, 'New-UA');

        $this->assertCount(1, $result);
        $this->assertSame('new', $result[0]['keys']['p256dh']);
        $this->assertSame('new-auth', $result[0]['keys']['auth']);
        $this->assertSame('New-UA', $result[0]['userAgent']);
    }

    public function testRemoveEndpointDeletesMatchingSubscription(): void
    {
        $current = [
            [
                'endpoint' => 'https://push.example/a',
                'keys' => ['p256dh' => 'k1', 'auth' => 'a1'],
            ],
            [
                'endpoint' => 'https://push.example/b',
                'keys' => ['p256dh' => 'k2', 'auth' => 'a2'],
            ],
        ];

        $result = SubscriptionStore::removeByEndpoint($current, 'https://push.example/a');

        $this->assertCount(1, $result);
        $this->assertSame('https://push.example/b', $result[0]['endpoint']);
    }
}
