<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use MapasCulturais\Managers\ThemeManager;

class ThemeManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up test themes
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);
        $themesPath = $manager->getThemePath('');
        
        if (is_dir($themesPath)) {
            $testThemes = ['mock', 'test_theme_delete', 'test_theme_activate'];
            foreach ($testThemes as $theme) {
                $themePath = $manager->getThemePath($theme);
                if (is_dir($themePath)) {
                    $this->removeDirectory($themePath);
                }
            }
        }
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testCanCloneThemeFromGithub()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        $themeName = 'test_theme_' . uniqid();
        $themePath = $manager->getThemePath($themeName);

        $this->assertFalse(is_dir($themePath), 'Test theme directory should not exist initially');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid GitHub repository URL');

        $manager->cloneFromGithub('invalid-url', $themeName);
    }

    public function testCanListThemes()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        $themePath = $manager->getThemePath('mock');
        mkdir($themePath, 0755, true);
        file_put_contents($themePath . '/Theme.php', '<?php class Theme {}');

        $themes = $manager->list();

        $this->assertIsArray($themes);
        $this->assertArrayHasKey('mock', $themes);
        $this->assertEquals('mock', $themes['mock']['name']);
        $this->assertArrayHasKey('active', $themes['mock']);
    }

    public function testCanActivateTheme()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        // Create mock theme
        $themeName = 'test_theme_activate';
        $themePath = $manager->getThemePath($themeName);
        mkdir($themePath, 0755, true);
        file_put_contents($themePath . '/Theme.php', '<?php class Theme {}');

        $manager->activate($themeName);

        $this->assertEquals($themeName, $app->config['app.theme']);
    }

    public function testCanDeleteTheme()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        // Create mock theme
        $themeName = 'test_theme_delete';
        $themePath = $manager->getThemePath($themeName);
        mkdir($themePath, 0755, true);
        file_put_contents($themePath . '/Theme.php', '<?php class Theme {}');

        $this->assertDirectoryExists($themePath);
        $manager->delete($themeName);
        $this->assertDirectoryDoesNotExist($themePath);
    }

    public function testCannotDeleteNonExistentTheme()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Theme not found');
        $manager->delete('non_existent_theme');
    }

    public function testCannotActivateNonExistentTheme()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Theme not found');
        $manager->activate('non_existent_theme');
    }

    public function testInvalidGithubUrlThrowsException()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid GitHub repository URL');
        $manager->cloneFromGithub('https://gitlab.com/test/test.git', 'test');
    }

    public function testCannotCloneToExistingDirectory()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        // Create existing directory
        $themePath = $manager->getThemePath('existing');
        mkdir($themePath, 0755, true);
        file_put_contents($themePath . '/Theme.php', '<?php class Theme {}');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Theme directory already exists');
        $manager->cloneFromGithub('https://github.com/test/test.git', 'existing');
    }
}
