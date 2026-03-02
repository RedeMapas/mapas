<?php

namespace Tests;

use Tests\Abstract\TestCase;
use MapasCulturais\Managers\ThemeManager;

require_once __DIR__ . '/Abstract/TestCase.php';
require_once __DIR__ . '/../../src/bootstrap.php';

class ThemeManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $manager = new ThemeManager($this->app);
        $themes = $manager->list();

        foreach ($themes as $name => $theme) {
            if (str_starts_with($name, 'test_theme_') || $name === 'mock') {
                $themePath = $manager->getThemePath($name);
                if (is_dir($themePath)) {
                    $manager->delete($name);
                }
            }
        }
    }

    public function testCanCloneThemeFromGithub()
    {
        $manager = new ThemeManager($this->app);

        $themeName = 'test_theme_' . uniqid();
        $themePath = $manager->getThemePath($themeName);

        $this->assertFalse(is_dir($themePath), 'Test theme directory should not exist initially');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid GitHub repository URL');

        $manager->cloneFromGithub('invalid-url', $themeName);
    }

    public function testCanListThemes()
    {
        $manager = new ThemeManager($this->app);

        $themePath = $manager->getThemePath('mock');
        mkdir($themePath, 0755, true);
        file_put_contents($themePath . '/Theme.php', '<?php class Theme {}');

        $themes = $manager->list();

        $this->assertIsArray($themes);
        $this->assertArrayHasKey('mock', $themes);
    }

    public function testCanActivateTheme()
    {
        $manager = new ThemeManager($this->app);

        $themeName = 'BaseV1';
        $manager->activate($themeName);

        $this->assertEquals($themeName, $this->app->config['app.theme']);
    }
}
