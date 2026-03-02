<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use MapasCulturais\Managers\PluginManager;
use MapasCulturais\App;

class PluginManagerTest extends TestCase
{
    private $app;
    private $manager;
    private $testPluginsPath;
    private $testRepoPath;

    protected function setUp(): void
    {
        $this->app = $this->createMock(App::class);
        $this->testPluginsPath = sys_get_temp_dir() . '/mapas_test_plugins_' . uniqid();
        $this->manager = new TestablePluginManager($this->app, $this->testPluginsPath);

        // Create a test git repository
        $this->testRepoPath = sys_get_temp_dir() . '/mapas_test_repo_' . uniqid();
        mkdir($this->testRepoPath, 0755, true);
        file_put_contents($this->testRepoPath . '/Plugin.php', '<?php class TestPlugin {}');

        // Initialize git repo
        exec("cd {$this->testRepoPath} && git init && git config user.email 'test@test.com' && git config user.name 'Test' && git add . && git commit -m 'Initial commit'");
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        if ($this->manager) {
            $pluginsPath = $this->manager->getPluginPath('');
            if (is_dir($pluginsPath . 'test_plugin')) {
                $this->manager->delete('test_plugin');
            }
            if (is_dir($pluginsPath . 'existing')) {
                $this->manager->delete('existing');
            }
            if (is_dir($pluginsPath . 'mock')) {
                $this->manager->delete('mock');
            }
        }

        // Clean up test repository
        if ($this->testRepoPath && is_dir($this->testRepoPath)) {
            $this->removeDirectory($this->testRepoPath);
        }

        parent::tearDown();
    }

    public function testCanClonePluginFromGithub()
    {
        $manager = $this->manager;

        $repoUrl = $this->testRepoPath;
        $pluginName = 'test_plugin';

        $result = $manager->cloneFromGithub($repoUrl, $pluginName);

        $this->assertTrue($result);
        $this->assertDirectoryExists($manager->getPluginPath($pluginName));

        // Cleanup
        $manager->delete($pluginName);
    }

    private function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testCannotCloneToExistingDirectory()
    {
        $manager = $this->manager;

        // Create existing directory
        $pluginPath = $manager->getPluginPath('existing');
        mkdir($pluginPath, 0755, true);
        file_put_contents($pluginPath . '/Plugin.php', '<?php class Plugin {}');

        $this->expectException(\Exception::class);
        $manager->cloneFromGithub('https://github.com/test/test.git', 'existing');
    }

    public function testCanListPlugins()
    {
        $manager = $this->manager;

        // Create mock plugin
        $pluginPath = $manager->getPluginPath('mock');
        mkdir($pluginPath, 0755, true);
        file_put_contents($pluginPath . '/Plugin.php', '<?php class Plugin {}');

        $plugins = $manager->list();

        $this->assertIsArray($plugins);
        $this->assertArrayHasKey('mock', $plugins);
    }
}

class TestablePluginManager extends PluginManager
{
    protected function validateRepoUrl(string $repoUrl): void
    {
        // Skip validation for tests
    }
}
