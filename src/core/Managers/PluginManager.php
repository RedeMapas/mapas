<?php
declare(strict_types=1);

namespace MapasCulturais\Managers;

use MapasCulturais\App;

class PluginManager
{
    protected App $app;
    protected string $pluginsPath;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->pluginsPath = APPLICATION_PATH . '/src/plugins/';
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
        if (!preg_match('/^https:\/\/github\.com\/[\w-]+\/[\w-]+\.git$/', $repoUrl)) {
            throw new \Exception("Invalid GitHub repository URL");
        }

        // Validate plugin name (prevent directory traversal)
        if (!preg_match('/^[\w-]+$/', $pluginName)) {
            throw new \Exception("Invalid plugin name");
        }

        // Create plugins directory if it doesn't exist
        if (!is_dir($this->pluginsPath)) {
            mkdir($this->pluginsPath, 0755, true);
        }

        // Execute git clone with escaped arguments
        $output = [];
        $returnCode = 0;
        $safeUrl = escapeshellarg($repoUrl);
        $safePath = escapeshellarg($targetPath);
        exec("git clone {$safeUrl} {$safePath} 2>&1", $output, $returnCode);

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

    public function toggle(string $pluginName): bool
    {
        $enabled = $this->isEnabled($pluginName);
        $enabledPlugins = $this->app->config['app.enabledPlugins'] ?? [];
        
        if ($enabled) {
            // Disable: remove from enabled list
            $key = array_search($pluginName, $enabledPlugins);
            if ($key !== false) {
                unset($enabledPlugins[$key]);
                $enabledPlugins = array_values($enabledPlugins); // Re-index
            }
        } else {
            // Enable: add to enabled list
            $enabledPlugins[] = $pluginName;
        }
        
        $this->app->config['app.enabledPlugins'] = $enabledPlugins;
        
        // Persist configuration (if config file exists)
        $configFile = APPLICATION_PATH . '../config/conf/config.php';
        if (file_exists($configFile)) {
            // Note: In production, use proper config persistence
            // This is a runtime-only change for now
        }
        
        return !$enabled;
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
