<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/themes/RedeMapas/Pwa/WebmanifestBuilder.php';
require_once __DIR__ . '/../../src/themes/RedeMapas/Pwa/HeadTagsBuilder.php';

use MapasCulturais\Themes\RedeMapas\Pwa\HeadTagsBuilder;
use MapasCulturais\Themes\RedeMapas\Pwa\WebmanifestBuilder;

class RedeMapasPwaTest extends TestCase
{
    public function testBuildWebmanifestDataContainsInstallabilityFields(): void
    {
        $manifest = WebmanifestBuilder::build(
            siteName: 'Rede Mapas',
            siteDescription: 'Mapa cultural colaborativo',
            startUrl: '/',
            icon192: '/img/favicon-192x192.png',
            icon512: '/img/favicon-512x512.png',
            wideScreenshot: '/img/home/home-circuits/circuits.jpg',
            mobileScreenshot: '/img/home/home-main-header/banner.png',
            themeColor: '#0f172a',
            backgroundColor: '#ffffff'
        );

        $this->assertSame('Rede Mapas', $manifest['name']);
        $this->assertSame('Rede Mapas', $manifest['short_name']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#0f172a', $manifest['theme_color']);
        $this->assertSame('#ffffff', $manifest['background_color']);
        $this->assertCount(2, $manifest['icons']);
        $this->assertSame('/img/favicon-192x192.png', $manifest['icons'][0]['src']);
        $this->assertSame('/img/favicon-512x512.png', $manifest['icons'][1]['src']);
        $this->assertArrayHasKey('screenshots', $manifest);
        $this->assertGreaterThanOrEqual(2, count($manifest['screenshots']));

        $wideScreenshots = array_values(array_filter(
            $manifest['screenshots'],
            static fn (array $shot): bool => ($shot['form_factor'] ?? '') === 'wide'
        ));
        $mobileScreenshots = array_values(array_filter(
            $manifest['screenshots'],
            static fn (array $shot): bool => ($shot['form_factor'] ?? '') !== 'wide'
        ));

        $this->assertNotEmpty($wideScreenshots);
        $this->assertNotEmpty($mobileScreenshots);
    }

    public function testBuildPwaHeadTagsContainsManifestAndCoreMetaTags(): void
    {
        $head = HeadTagsBuilder::build(
            siteName: 'Rede Mapas',
            manifestUrl: '/site.webmanifest',
            appleTouchIcon: '/img/favicon-180x180.png',
            themeColor: '#0f172a'
        );

        $this->assertArrayHasKey('links', $head);
        $this->assertArrayHasKey('metas', $head);

        $this->assertCount(1, $head['links']);
        $this->assertSame('manifest', $head['links'][0]['rel']);
        $this->assertSame('/site.webmanifest', $head['links'][0]['href']);

        $metaNames = array_column($head['metas'], 'name');
        $this->assertContains('theme-color', $metaNames);
        $this->assertContains('mobile-web-app-capable', $metaNames);
        $this->assertContains('apple-mobile-web-app-capable', $metaNames);
        $this->assertContains('apple-mobile-web-app-title', $metaNames);
        $this->assertContains('msapplication-TileColor', $metaNames);
    }
}
