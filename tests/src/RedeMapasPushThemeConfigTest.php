<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/themes/RedeMapas/Push/PushConfigBuilder.php';

use MapasCulturais\Themes\RedeMapas\Push\PushConfigBuilder;

class RedeMapasPushThemeConfigTest extends TestCase
{
    public function testBuildPushClientConfigEnabled(): void
    {
        $config = PushConfigBuilder::buildClientConfig(
            enabled: true,
            publicKey: 'PUBLIC_KEY',
            subscribeUrl: '/push/subscribe',
            unsubscribeUrl: '/push/unsubscribe',
            serviceWorkerUrl: '/push/serviceWorker'
        );

        $this->assertTrue($config['enabled']);
        $this->assertSame('PUBLIC_KEY', $config['publicKey']);
        $this->assertSame('/push/subscribe', $config['subscribeUrl']);
        $this->assertSame('/push/unsubscribe', $config['unsubscribeUrl']);
        $this->assertSame('/push/serviceWorker', $config['serviceWorkerUrl']);
    }

    public function testBuildPushClientConfigDisabledWithoutPublicKey(): void
    {
        $config = PushConfigBuilder::buildClientConfig(
            enabled: true,
            publicKey: '',
            subscribeUrl: '/push/subscribe',
            unsubscribeUrl: '/push/unsubscribe',
            serviceWorkerUrl: '/push/serviceWorker'
        );

        $this->assertFalse($config['enabled']);
    }
}
