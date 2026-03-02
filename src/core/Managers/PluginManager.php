<?php
declare(strict_types=1);

namespace MapasCulturais\Managers;

use MapasCulturais\App;

class PluginManager
{
    protected App $app;
    protected string $pluginsPath;

    public function __construct(App $app, ?string $pluginsPath = null)
    {
        $this->app = $app;
        $path = $pluginsPath ?? \APPLICATION_PATH . 'src/plugins/';
        $this->pluginsPath = rtrim($path, '/') . '/';
    }

    public function getPluginPath(string $name): string
    {
        return $this->pluginsPath . $name;
    }

    public function cloneFromGithub(string $repoUrl, string $pluginName): bool
    {
        $targetPath = $this->getPluginPath($pluginName);

        // Check if directory already exists
        if (is_dir($targetPath)) {
            throw new \Exception("Plugin directory already exists: {$pluginName}");
        }

        // Validate GitHub URL
        $this->validateRepoUrl($repoUrl);

        // Create plugins directory if it doesn't exist
        if (!is_dir($this->pluginsPath)) {
            mkdir($this->pluginsPath, 0755, true);
        }

        // Execute git clone
        $output = [];
        $returnCode = 0;
        $cloneCommand = $this->getCloneCommand($repoUrl, $targetPath);
        exec($cloneCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("Git clone failed: " . implode("\n", $output));
        }

        // Validate structure
        if (!file_exists($targetPath . '/Plugin.php')) {
            // Clean up failed clone
            $this->removeDirectory($targetPath);
            throw new \Exception("Invalid plugin structure: Plugin.php not found");
        }

        return true;
    }

    protected function validateRepoUrl(string $repoUrl): void
    {
        if (!preg_match('/^https:\/\/github\.com\/[\w-]+\/[\w-]+\.git$/', $repoUrl)) {
            throw new \Exception("Invalid GitHub repository URL");
        }
    }

    protected function getCloneCommand(string $repoUrl, string $targetPath): string
    {
        return "git clone {$repoUrl} {$targetPath}";
    }

    public function list(): array
    {
        $plugins = [];

        if (!is_dir($this->pluginsPath)) {
            return $plugins;
        }

        $dirs = scandir($this->pluginsPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $pluginPath = $this->pluginsPath . $dir;

            if (is_dir($pluginPath) && file_exists($pluginPath . '/Plugin.php')) {
                $plugins[$dir] = [
                    'name' => $dir,
                    'path' => $pluginPath,
                    'enabled' => $this->isEnabled($dir),
                ];
            }
        }

        return $plugins;
    }

    public function delete(string $pluginName): void
    {
        $targetPath = $this->getPluginPath($pluginName);

        if (!is_dir($targetPath)) {
            throw new \Exception("Plugin not found: {$pluginName}");
        }

        $this->removeDirectory($targetPath);
    }

    protected function isEnabled(string $pluginName): bool
    {
        // Check if plugin is enabled in app configuration
        $enabledPlugins = $this->app->config['app.enabledPlugins'] ?? [];
        return in_array($pluginName, $enabledPlugins);
    }

    protected function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
