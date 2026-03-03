<?php
declare(strict_types=1);

namespace MapasCulturais\Managers;

use MapasCulturais\App;

class ThemeManager
{
    protected App $app;
    protected string $themesPath;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->themesPath = THEMES_PATH;
    }

    public function getThemePath(string $name): string
    {
        return $this->themesPath . $name;
    }

    public function cloneFromGithub(string $repoUrl, string $themeName): bool
    {
        $targetPath = $this->getThemePath($themeName);

        if (is_dir($targetPath)) {
            throw new \Exception("Theme directory already exists: {$themeName}");
        }

        if (!preg_match('/^https:\/\/github\.com\/[\w-]+\/[\w-]+\.git$/', $repoUrl)) {
            throw new \Exception("Invalid GitHub repository URL");
        }

        if (!is_dir($this->themesPath)) {
            mkdir($this->themesPath, 0755, true);
        }

        $output = [];
        $returnCode = 0;
        $safeUrl = escapeshellarg($repoUrl);
        $safePath = escapeshellarg($targetPath);
        exec("git clone {$safeUrl} {$safePath} 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("Git clone failed: " . implode("\n", $output));
        }

        if (!file_exists($targetPath . '/Theme.php')) {
            $this->removeDirectory($targetPath);
            throw new \Exception("Invalid theme structure: Theme.php not found");
        }

        return true;
    }

    public function list(): array
    {
        $themes = [];

        if (!is_dir($this->themesPath)) {
            return $themes;
        }

        $dirs = scandir($this->themesPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $themePath = $this->themesPath . $dir;

            if (is_dir($themePath) && file_exists($themePath . '/Theme.php')) {
                $themes[$dir] = [
                    'name' => $dir,
                    'path' => $themePath,
                    'active' => $this->isActive($dir),
                ];
            }
        }

        return $themes;
    }

    public function activate(string $themeName): void
    {
        $targetPath = $this->getThemePath($themeName);

        if (!is_dir($targetPath)) {
            throw new \Exception("Theme not found: {$themeName}");
        }

        if (!file_exists($targetPath . '/Theme.php')) {
            throw new \Exception("Invalid theme structure");
        }

        $this->app->config['app.theme'] = $themeName;
    }

    public function delete(string $themeName): void
    {
        $targetPath = $this->getThemePath($themeName);

        if (!is_dir($targetPath)) {
            throw new \Exception("Theme not found: {$themeName}");
        }

        $this->removeDirectory($targetPath);
    }

    protected function isActive(string $themeName): bool
    {
        return $this->app->config['app.theme'] === $themeName;
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
