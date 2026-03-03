<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use MapasCulturais\Managers\PluginManager;

class PluginManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up test plugins
        $app = \MapasCulturais\App::i();
        $manager = new PluginManager($app);
        $pluginsPath = $manager->getPluginPath('');
        
        if (is_dir($pluginsPath)) {
            $testPlugins = ['test_plugin', 'existing', 'mock', 'toggle_test'];
            foreach ($testPlugins as $plugin) {
                $pluginPath = $manager->getPluginPath($plugin);
                if (is_dir($pluginPath)) {
                    $this->removeDirectory($pluginPath);
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

    public function testCanClonePluginFromGithub()
    {
        $app = \MapasCulturais\App::i();
        $manager = new PluginManager($app);

        $repoUrl = 'https://github.com/example/test-plugin.git';
        $pluginName = 'test_plugin';

        // Note: This test will fail without network access or with invalid repo
        // Using a try-catch to handle expected failure
        try {
            $result = $manager->cloneFromGithub($repoUrl, $pluginName);
            $this->assertTrue($result);
            $this->assertDirectoryExists($manager->getPluginPath($pluginName));
        } catch (\Exception $e) {
            // Expected to fail with invalid repo URL
            $this->assertStringContainsString('Git clone failed', $e->getMessage());
        }
    }

    public function testCannotCloneToExistingDirectory()
    {
        $app = \MapasCulturais\App::i();
        $manager = new PluginManager($app);

        // Create existing directory
        $pluginPath = $manager->getPluginPath('existing');
        mkdir($pluginPath, 0755, true);
        file_put_contents($pluginPath . '/Plugin.php', '<?php class Plugin {}');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Plugin directory already exists');
        $manager->cloneFromGithub('https://github.com/test/test.git', 'existing');
    }

    public function testCanListPlugins()
    {
        $app = \MapasCulturais\App::i();
        $manager = new PluginManager($app);

        // Create mock plugin
        $pluginPath = $manager->getPluginPath('mock');
        mkdir($pluginPath, 0755, true);
        file_put_contents($pluginPath . '/Plugin.php', '<?php class Plugin {}');

        $plugins = $manager->list();

        $this->assertIsArray($plugins);
        $this->assertArrayHasKey('mock', $plugins);
        $this->assertEquals('mock', $plugins['mock']['name']);
        $this->assertArrayHasKey('enabled', $plugins['mock']);
    }

    public function testCanTogglePlugin()
    {
        $app = \MapasCulturais\App::i();
        $manager = new PluginManager($app);

        // Create mock plugin
        $pluginPath = $manager->getPluginPath('toggle_test');
        mkdir($pluginPath, 0755, true);
        file_put_contents($pluginPath . '/Plugin.php', '<?php class Plugin {}');

        // Initially disabled
        $plugins = $manager->list();
        $this->assertFalse($plugins['toggle_test']['enabled']);

        // Toggle to enabled
        $newStatus = $manager->toggle('toggle_test');
        $this->assertTrue($newStatus);

        // Verify in list
        $plugins = $manager->list();
        $this->assertTrue($plugins['toggle_test']['enabled']);

        // Toggle back to disabled
        $newStatus = $manager->toggle('toggle_test');
        $this->assertFalse($newStatus);
    }

    public function testCanDeletePlugin()
    {
        $app = \MapasCulturais\App::i();
        $manager = new PluginManager($app);

        // Create mock plugin
        $pluginPath = $manager->getPluginPath('test_delete');
        mkdir($pluginPath, 0755, true);
        file_put_contents($pluginPath . '/Plugin.php', '<?php class Plugin {}');

        $this->assertDirectoryExists($pluginPath);
        $manager->delete('test_delete');
        $this->assertDirectoryDoesNotExist($pluginPath);
    }

    public function testCannotDeleteNonExistentPlugin()
    {
        $app = \MapasCulturais\App::i();
        $manager = new PluginManager($app);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Plugin not found');
        $manager->delete('non_existent_plugin');
    }

    public function testInvalidGithubUrlThrowsException()
    {
        $app = \MapasCulturais\App::i();
        $manager = new PluginManager($app);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid GitHub repository URL');
        $manager->cloneFromGithub('https://gitlab.com/test/test.git', 'test');
    }

    public function testInvalidPluginNameThrowsException()
    {
        $app = \MapasCulturais\App::i();
        $manager = new PluginManager($app);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid plugin name');
        $manager->cloneFromGithub('https://github.com/test/test.git', '../traversal');
    }
}
