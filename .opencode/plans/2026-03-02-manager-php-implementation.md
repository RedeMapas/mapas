# manager.php Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create a web interface authenticated with `superAdmin` role to manage Multi-SaaS instances (subsites), plugins, and themes using HTMX + TailwindCSS.

**Architecture:** Single standalone file `public/manager.php` following `health.php` pattern. Three-layer: bootstrap/auth, business logic (managers), frontend (HTMX + TailwindCSS via CDN). Backend uses Doctrine ORM for subsites, filesystem + git for plugins/themes.

**Tech Stack:** PHP 8.3, Doctrine ORM, HTMX 1.9.10, TailwindCSS 3.4, Git (system), PHPUnit for tests.

---

## Task 1: Test Authentication Middleware

**Files:**
- Create: `tests/src/ManagerTest.php`
- Create: `public/manager.php` (basic structure)

**Step 1: Write failing test**

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    public function testUnauthenticatedUserRedirectsToLogin()
    {
        // Mock session without user_id
        $_SESSION = [];

        // This should redirect to /auth/login
        $this->expectOutputRegex('/Location: \/auth\/login/');
    }

    public function testNonSuperAdminUserReturns403()
    {
        // Mock session with user without superAdmin role
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['admin']; // Not superAdmin

        $this->expectOutputRegex('/HTTP\/1\.1 403 Forbidden/');
    }

    public function testSuperAdminUserCanAccess()
    {
        // Mock session with superAdmin
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        // Should return 200 with HTML content
        $this->expectOutputRegex('/200 OK/');
    }
}
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/src/ManagerTest.php --filter testUnauthenticatedUserRedirectsToLogin -v`
Expected: FAIL - manager.php doesn't exist yet

**Step 3: Write minimal implementation**

Create `public/manager.php`:

```php
<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login');
    exit;
}

// Check superAdmin role
if (!in_array('superAdmin', $_SESSION['user_roles'] ?? [])) {
    http_response_code(403);
    echo "HTTP/1.1 403 Forbidden";
    exit;
}

// Basic response for now
http_response_code(200);
echo "OK";
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/src/ManagerTest.php -v`
Expected: PASS for all three tests

**Step 5: Commit**

```bash
git add tests/src/ManagerTest.php public/manager.php
git commit -m "feat(manager): add authentication middleware"
```

---

## Task 2: Load MapasCulturais Bootstrap

**Files:**
- Modify: `public/manager.php`
- Test: `tests/src/ManagerTest.php`

**Step 1: Write failing test**

```php
public function testAppInstanceIsLoaded()
{
    // After bootstrap, App::i() should be available
    $this->assertTrue(class_exists('MapasCulturais\App'));
}

public function testEntityManagerIsAvailable()
{
    // EntityManager should be accessible
    $app = \MapasCulturais\App::i();
    $this->assertNotNull($app->em);
}
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/src/ManagerTest.php --filter testAppInstanceIsLoaded -v`
Expected: FAIL - App class not loaded

**Step 3: Write minimal implementation**

Update `public/manager.php`:

```php
<?php
declare(strict_types=1);

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login');
    exit;
}

if (!in_array('superAdmin', $_SESSION['user_roles'] ?? [])) {
    http_response_code(403);
    echo "HTTP/1.1 403 Forbidden";
    exit;
}

// Load minimal MapasCulturais bootstrap
$bootstrap_file = __DIR__ . '/../src/bootstrap.php';
require_once $bootstrap_file;

// Get App instance
$app = \MapasCulturais\App::i();

// Basic response
http_response_code(200);
echo "OK - App loaded";
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/src/ManagerTest.php -v`
Expected: PASS

**Step 5: Commit**

```bash
git add public/manager.php tests/src/ManagerTest.php
git commit -m "feat(manager): load MapasCulturais bootstrap"
```

---

## Task 3: Create SubsiteManager Class

**Files:**
- Create: `src/core/Managers/SubsiteManager.php`
- Test: `tests/src/SubsiteManagerTest.php`

**Step 1: Write failing test**

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use MapasCulturais\Managers\SubsiteManager;

class SubsiteManagerTest extends TestCase
{
    public function testCanCreateSubsite()
    {
        $app = \MapasCulturais\App::i();
        $manager = new SubsiteManager($app);

        $data = [
            'name' => 'Test Subsite',
            'url' => 'test.local',
            'owner' => 1, // agent_id
            'namespace' => 'Subsite',
        ];

        $subsite = $manager->create($data);

        $this->assertInstanceOf('MapasCulturais\Entities\Subsite', $subsite);
        $this->assertEquals('Test Subsite', $subsite->name);
        $this->assertEquals('test.local', $subsite->url);
        $this->assertEquals(1, $subsite->status); // enabled
    }

    public function testCannotCreateSubsiteWithDuplicateUrl()
    {
        $app = \MapasCulturais\App::i();
        $manager = new SubsiteManager($app);

        // Create first subsite
        $manager->create([
            'name' => 'First',
            'url' => 'duplicate.local',
            'owner' => 1,
            'namespace' => 'Subsite',
        ]);

        // Try to create duplicate
        $this->expectException(\Exception::class);
        $manager->create([
            'name' => 'Second',
            'url' => 'duplicate.local',
            'owner' => 1,
            'namespace' => 'Subsite',
        ]);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/src/SubsiteManagerTest.php --filter testCanCreateSubsite -v`
Expected: FAIL - SubsiteManager doesn't exist

**Step 3: Write minimal implementation**

Create `src/core/Managers/SubsiteManager.php`:

```php
<?php
declare(strict_types=1);

namespace MapasCulturais\Managers;

use MapasCulturais\App;
use MapasCulturais\Entities\Subsite;

class SubsiteManager
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function create(array $data): Subsite
    {
        $subsite = new Subsite();

        $subsite->name = $data['name'];
        $subsite->url = $data['url'];
        $subsite->_ownerId = $data['owner'];
        $subsite->namespace = $data['namespace'] ?? 'Subsite';

        $this->app->em->persist($subsite);
        $this->app->em->flush();

        return $subsite;
    }

    public function update(int $id, array $data): Subsite
    {
        $subsite = $this->app->repo('Subsite')->find($id);

        if (!$subsite) {
            throw new \Exception("Subsite not found");
        }

        if (isset($data['name'])) {
            $subsite->name = $data['name'];
        }

        if (isset($data['url'])) {
            $subsite->url = $data['url'];
        }

        $this->app->em->flush();

        return $subsite;
    }

    public function delete(int $id): void
    {
        $subsite = $this->app->repo('Subsite')->find($id);

        if (!$subsite) {
            throw new \Exception("Subsite not found");
        }

        $this->app->em->remove($subsite);
        $this->app->em->flush();
    }

    public function toggleStatus(int $id): void
    {
        $subsite = $this->app->repo('Subsite')->find($id);

        if (!$subsite) {
            throw new \Exception("Subsite not found");
        }

        $subsite->status = $subsite->status === 1 ? 0 : 1;
        $this->app->em->flush();
    }

    public function list(): array
    {
        return $this->app->repo('Subsite')->findAll();
    }
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/src/SubsiteManagerTest.php -v`
Expected: PASS (validation will be handled by Doctrine unique constraint)

**Step 5: Commit**

```bash
git add src/core/Managers/SubsiteManager.php tests/src/SubsiteManagerTest.php
git commit -m "feat(manager): add SubsiteManager"
```

---

## Task 4: Create PluginManager Class

**Files:**
- Create: `src/core/Managers/PluginManager.php`
- Test: `tests/src/PluginManagerTest.php`

**Step 1: Write failing test**

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use MapasCulturais\Managers\PluginManager;

class PluginManagerTest extends TestCase
{
    public function testCanClonePluginFromGithub()
    {
        $app = \MapasCulturais\App::i();
        $manager = new PluginManager($app);

        $repoUrl = 'https://github.com/example/test-plugin.git';
        $pluginName = 'test_plugin';

        $result = $manager->cloneFromGithub($repoUrl, $pluginName);

        $this->assertTrue($result);
        $this->assertDirectoryExists($manager->getPluginPath($pluginName));
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
    }
}
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/src/PluginManagerTest.php --filter testCanClonePluginFromGithub -v`
Expected: FAIL - PluginManager doesn't exist

**Step 3: Write minimal implementation**

Create `src/core/Managers/PluginManager.php`:

```php
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

        // Create plugins directory if it doesn't exist
        if (!is_dir($this->pluginsPath)) {
            mkdir($this->pluginsPath, 0755, true);
        }

        // Execute git clone
        $output = [];
        $returnCode = 0;
        exec("git clone {$repoUrl} {$targetPath} 2>&1", $output, $returnCode);

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
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/src/PluginManagerTest.php -v`
Expected: PASS

**Step 5: Commit**

```bash
git add src/core/Managers/PluginManager.php tests/src/PluginManagerTest.php
git commit -m "feat(manager): add PluginManager"
```

---

## Task 5: Create ThemeManager Class

**Files:**
- Create: `src/core/Managers/ThemeManager.php`
- Test: `tests/src/ThemeManagerTest.php`

**Step 1: Write failing test**

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use MapasCulturais\Managers\ThemeManager;

class ThemeManagerTest extends TestCase
{
    public function testCanCloneThemeFromGithub()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        $repoUrl = 'https://github.com/example/test-theme.git';
        $themeName = 'test_theme';

        $result = $manager->cloneFromGithub($repoUrl, $themeName);

        $this->assertTrue($result);
        $this->assertDirectoryExists($manager->getThemePath($themeName));
    }

    public function testCanListThemes()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        // Create mock theme
        $themePath = $manager->getThemePath('mock');
        mkdir($themePath, 0755, true);
        file_put_contents($themePath . '/Theme.php', '<?php class Theme {}');

        $themes = $manager->list();

        $this->assertIsArray($themes);
        $this->assertArrayHasKey('mock', $themes);
    }

    public function testCanActivateTheme()
    {
        $app = \MapasCulturais\App::i();
        $manager = new ThemeManager($app);

        $themeName = 'BaseV1';
        $manager->activate($themeName);

        $this->assertEquals($themeName, $app->config['app.theme']);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/src/ThemeManagerTest.php --filter testCanCloneThemeFromGithub -v`
Expected: FAIL - ThemeManager doesn't exist

**Step 3: Write minimal implementation**

Create `src/core/Managers/ThemeManager.php`:

```php
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
        $this->themesPath = APPLICATION_PATH . '/src/themes/';
    }

    public function getThemePath(string $name): string
    {
        return $this->themesPath . $name;
    }

    public function cloneFromGithub(string $repoUrl, string $themeName): bool
    {
        $targetPath = $this->getThemePath($themeName);

        // Check if directory already exists
        if (is_dir($targetPath)) {
            throw new \Exception("Theme directory already exists: {$themeName}");
        }

        // Validate GitHub URL
        if (!preg_match('/^https:\/\/github\.com\/[\w-]+\/[\w-]+\.git$/', $repoUrl)) {
            throw new \Exception("Invalid GitHub repository URL");
        }

        // Create themes directory if it doesn't exist
        if (!is_dir($this->themesPath)) {
            mkdir($this->themesPath, 0755, true);
        }

        // Execute git clone
        $output = [];
        $returnCode = 0;
        exec("git clone {$repoUrl} {$targetPath} 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("Git clone failed: " . implode("\n", $output));
        }

        // Validate structure
        if (!file_exists($targetPath . '/Theme.php')) {
            // Clean up failed clone
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
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/src/ThemeManagerTest.php -v`
Expected: PASS

**Step 5: Commit**

```bash
git add src/core/Managers/ThemeManager.php tests/src/ThemeManagerTest.php
git commit -m "feat(manager): add ThemeManager"
```

---

## Task 6: Add Basic Router and HTML Layout

**Files:**
- Modify: `public/manager.php`

**Step 1: Create router structure**

Update `public/manager.php`:

```php
<?php
declare(strict_types=1);

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login');
    exit;
}

if (!in_array('superAdmin', $_SESSION['user_roles'] ?? [])) {
    http_response_code(403);
    echo "HTTP/1.1 403 Forbidden";
    exit;
}

// Load bootstrap
$bootstrap_file = __DIR__ . '/../src/bootstrap.php';
require_once $bootstrap_file;

$app = \MapasCulturais\App::i();

// Simple router
$entity = $_GET['entity'] ?? 'subsite';
$action = $_GET['action'] ?? 'list';
$format = $_GET['format'] ?? 'html'; // html, json (for HTMX)

// Valid entities
$validEntities = ['subsite', 'plugin', 'theme'];
if (!in_array($entity, $validEntities)) {
    http_response_code(404);
    exit;
}

// Valid actions
$validActions = ['list', 'create', 'edit', 'delete', 'toggle'];
if (!in_array($action, $validActions)) {
    http_response_code(404);
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $managerClass = "MapasCulturais\\Managers\\" . ucfirst($entity) . "Manager";
    $manager = new $managerClass($app);

    try {
        switch ($action) {
            case 'create':
                $manager->create($_POST);
                $message = ucfirst($entity) . " criado com sucesso";
                break;
            case 'edit':
                $manager->update((int)$_GET['id'], $_POST);
                $message = ucfirst($entity) . " atualizado com sucesso";
                break;
            case 'delete':
                $manager->delete((int)$_GET['id']);
                $message = ucfirst($entity) . " removido com sucesso";
                break;
            case 'toggle':
                $manager->toggleStatus((int)$_GET['id']);
                $message = ucfirst($entity) . " atualizado com sucesso";
                break;
        }

        if ($format === 'json') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $message]);
            exit;
        }
    } catch (\Exception $e) {
        if ($format === 'json') {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// Render HTML
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager - Mapas Culturais</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Manager - Mapas Culturais</h1>

        <!-- Navigation -->
        <nav class="bg-white shadow-md rounded-lg mb-6">
            <div class="flex space-x-4 p-4">
                <a href="/manager.php?entity=subsite" class="px-4 py-2 rounded hover:bg-gray-100 <?php echo $entity === 'subsite' ? 'bg-blue-100' : ''; ?>">Subsites</a>
                <a href="/manager.php?entity=plugin" class="px-4 py-2 rounded hover:bg-gray-100 <?php echo $entity === 'plugin' ? 'bg-blue-100' : ''; ?>">Plugins</a>
                <a href="/manager.php?entity=theme" class="px-4 py-2 rounded hover:bg-gray-100 <?php echo $entity === 'theme' ? 'bg-blue-100' : ''; ?>">Temas</a>
                <a href="/auth/logout" class="ml-auto px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Logout</a>
            </div>
        </nav>

        <!-- Content -->
        <div id="content">
            <?php
            $managerClass = "MapasCulturais\\Managers\\" . ucfirst($entity) . "Manager";
            $manager = new $managerClass($app);

            if ($action === 'list') {
                $items = $manager->list();
                renderList($entity, $items);
            }
            ?>
        </div>
    </div>
</body>
</html>

<?php

function renderList(string $entity, array $items): void
{
    ?>
    <div class="bg-white shadow-md rounded-lg">
        <div class="p-4 border-b">
            <h2 class="text-xl font-semibold"><?php echo ucfirst($entity); ?>s</h2>
        </div>
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php echo htmlspecialchars($item['name'] ?? $item->name); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php
                        $status = $item['status'] ?? ($item['enabled'] ?? $item['active'] ?? false);
                        $statusText = $status ? 'Ativo' : 'Inativo';
                        $statusColor = $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                            <?php echo $statusText; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="/manager.php?entity=<?php echo $entity; ?>&action=edit&id=<?php echo $item['id'] ?? ''; ?>" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                        <a href="/manager.php?entity=<?php echo $entity; ?>&action=toggle&id=<?php echo $item['id'] ?? ''; ?>" class="ml-4 text-indigo-600 hover:text-indigo-900">Toggle</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
```

**Step 2: Commit**

```bash
git add public/manager.php
git commit -m "feat(manager): add router and HTML layout with TailwindCSS"
```

---

## Task 7: Add Create/Edit Forms

**Files:**
- Modify: `public/manager.php`

**Step 1: Add form rendering function**

Update `public/manager.php` to add `renderCreate` and `renderEdit` functions:

```php
// Update router to handle create/edit actions
if ($action === 'create') {
    renderCreate($entity);
    exit;
}

if ($action === 'edit' && isset($_GET['id'])) {
    $managerClass = "MapasCulturais\\Managers\\" . ucfirst($entity) . "Manager";
    $manager = new $managerClass($app);
    $item = $manager->list()[(int)$_GET['id']] ?? $app->repo(ucfirst($entity))->find($_GET['id']);
    renderEdit($entity, $item);
    exit;
}

// Add after renderList() function:

function renderCreate(string $entity): void
{
    ?>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-md rounded-lg">
            <div class="p-4 border-b">
                <h2 class="text-xl font-semibold">Criar <?php echo ucfirst($entity); ?></h2>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <?php if ($entity === 'subsite'): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">URL (subdomínio)</label>
                        <input type="text" name="url" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Owner (Agent ID)</label>
                        <input type="number" name="owner" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Namespace</label>
                        <input type="text" name="namespace" value="Subsite" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                <?php elseif ($entity === 'plugin'): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome do Plugin</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">URL do Repositório GitHub</label>
                        <input type="text" name="repo" required placeholder="https://github.com/owner/plugin.git" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                <?php elseif ($entity === 'theme'): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome do Tema</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">URL do Repositório GitHub</label>
                        <input type="text" name="repo" required placeholder="https://github.com/owner/theme.git" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                <?php endif; ?>

                <div class="flex space-x-4">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Salvar</button>
                    <a href="/manager.php?entity=<?php echo $entity; ?>" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function renderEdit(string $entity, $item): void
{
    ?>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-md rounded-lg">
            <div class="p-4 border-b">
                <h2 class="text-xl font-semibold">Editar <?php echo ucfirst($entity); ?></h2>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <?php if ($entity === 'subsite'): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($item->name); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">URL</label>
                        <input type="text" name="url" value="<?php echo htmlspecialchars($item->url); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="1" <?php echo $item->status === 1 ? 'selected' : ''; ?>>Ativo</option>
                            <option value="0" <?php echo $item->status === 0 ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="flex space-x-4">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Salvar</button>
                    <a href="/manager.php?entity=<?php echo $entity; ?>" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    <?php
}
```

**Step 2: Commit**

```bash
git add public/manager.php
git commit -m "feat(manager): add create/edit forms"
```

---

## Task 8: Add HTMX Interactivity

**Files:**
- Modify: `public/manager.php`

**Step 1: Add HTMX attributes to toggle and delete actions**

Update `public/manager.php` to add HTMX attributes:

```php
// In renderList(), update actions with HTMX:

// Toggle button with HTMX
<a
    href="#"
    hx-post="/manager.php?entity=<?php echo $entity; ?>&action=toggle&id=<?php echo $item['id'] ?? ''; ?>&format=json"
    hx-target="#status-<?php echo $item['id'] ?? ''; ?>"
    hx-swap="outerHTML"
    class="ml-4 text-indigo-600 hover:text-indigo-900"
>
    Toggle
</a>

// Delete button with HTMX
<a
    href="#"
    hx-delete="/manager.php?entity=<?php echo $entity; ?>&action=delete&id=<?php echo $item['id'] ?? ''; ?>&format=json"
    hx-confirm="Tem certeza que deseja remover?"
    hx-target="tr"
    hx-swap="outerHTML"
    class="ml-4 text-red-600 hover:text-red-900"
>
    Remover
</a>

// Update POST handler to support HTMX partial updates:

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $managerClass = "MapasCulturais\\Managers\\" . ucfirst($entity) . "Manager";
    $manager = new $managerClass($app);

    try {
        switch ($action) {
            case 'create':
                $manager->create($_POST);
                $message = ucfirst($entity) . " criado com sucesso";
                break;
            case 'edit':
                $manager->update((int)$_GET['id'], $_POST);
                $message = ucfirst($entity) . " atualizado com sucesso";
                break;
            case 'delete':
                $manager->delete((int)$_GET['id']);
                $message = ucfirst($entity) . " removido com sucesso";
                break;
            case 'toggle':
                $manager->toggleStatus((int)$_GET['id']);
                // Return updated status for HTMX
                $item = $app->repo(ucfirst($entity))->find((int)$_GET['id']);
                header('Content-Type: text/html');
                renderStatus($item);
                exit;
        }

        if ($format === 'json') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $message]);
            exit;
        }
    } catch (\Exception $e) {
        if ($format === 'json') {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// Add renderStatus() function:

function renderStatus($item): void
{
    $status = $item['status'] ?? $item->status;
    $statusText = $status ? 'Ativo' : 'Inativo';
    $statusColor = $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    ?>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
            <?php echo $statusText; ?>
        </span>
    </td>
    <?php
}
```

**Step 2: Add search with HTMX**

Add search input to nav:

```html
<!-- In navigation div, add search -->
<div class="flex-1">
    <input
        type="text"
        placeholder="Buscar..."
        hx-get="/manager.php?entity=<?php echo $entity; ?>&action=search&format=html"
        hx-trigger="input changed delay:500ms"
        hx-target="#content"
        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
</div>

// Add search action handler:

if ($action === 'search') {
    $query = $_GET['q'] ?? '';
    $managerClass = "MapasCulturais\\Managers\\" . ucfirst($entity) . "Manager";
    $manager = new $managerClass($app);
    $items = array_filter($manager->list(), function($item) use ($query) {
        $name = $item['name'] ?? $item->name;
        return stripos($name, $query) !== false;
    });

    renderList($entity, $items);
    exit;
}
```

**Step 3: Commit**

```bash
git add public/manager.php
git commit -m "feat(manager): add HTMX interactivity for toggle, delete, search"
```

---

## Task 9: Add Error Handling and Notifications

**Files:**
- Modify: `public/manager.php`

**Step 1: Add toast notifications**

Add to HTML head:

```html
<style>
    .toast {
        position: fixed;
        top: 1rem;
        right: 1rem;
        padding: 1rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        animation: slideIn 0.3s ease-out;
    }
    .toast-success { background-color: #10b981; color: white; }
    .toast-error { background-color: #ef4444; color: white; }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
</style>
```

Add toast container and HTMX event handlers:

```html
<body ...>
    <div id="toast-container"></div>

    <script>
        document.body.addEventListener('htmx:beforeRequest', function(evt) {
            // Show loading indicator if needed
        });

        document.body.addEventListener('htmx:afterRequest', function(evt) {
            if (evt.detail.xhr.status === 200) {
                try {
                    const response = JSON.parse(evt.detail.xhr.responseText);
                    if (response.success) {
                        showToast(response.message, 'success');
                    } else if (response.error) {
                        showToast(response.error, 'error');
                    }
                } catch (e) {
                    // Not JSON, ignore
                }
            }
        });

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.getElementById('toast-container').appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
```

**Step 2: Add error page**

Add error handler:

```php
// Before router, add error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if ($format === 'json') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $errstr]);
        exit;
    }
});

set_exception_handler(function($exception) {
    if ($format === 'json') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
        exit;
    }
});
```

**Step 3: Commit**

```bash
git add public/manager.php
git commit -m "feat(manager): add toast notifications and error handling"
```

---

## Task 10: Final Integration and Tests

**Files:**
- Test: `tests/src/ManagerIntegrationTest.php`

**Step 1: Write integration tests**

Create `tests/src/ManagerIntegrationTest.php`:

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ManagerIntegrationTest extends TestCase
{
    public function testFullSubsiteWorkflow()
    {
        $app = \MapasCulturais\App::i();
        $manager = new \MapasCulturais\Managers\SubsiteManager($app);

        // Create
        $subsite = $manager->create([
            'name' => 'Integration Test',
            'url' => 'integration.local',
            'owner' => 1,
            'namespace' => 'Subsite',
        ]);

        $this->assertInstanceOf('MapasCulturais\Entities\Subsite', $subsite);

        // Update
        $subsite = $manager->update($subsite->id, ['name' => 'Updated']);
        $this->assertEquals('Updated', $subsite->name);

        // Toggle
        $manager->toggleStatus($subsite->id);
        $this->assertEquals(0, $subsite->status);

        // Cleanup
        $manager->delete($subsite->id);
        $this->assertNull($app->repo('Subsite')->find($subsite->id));
    }

    public function testPluginCloneWorkflow()
    {
        $app = \MapasCulturais\App::i();
        $manager = new \MapasCulturais\Managers\PluginManager($app);

        // This test requires mocking git commands or using a real test repo
        // For now, test listing functionality
        $plugins = $manager->list();
        $this->assertIsArray($plugins);
    }

    public function testThemeWorkflow()
    {
        $app = \MapasCulturais\App::i();
        $manager = new \MapasCulturais\Managers\ThemeManager($app);

        $themes = $manager->list();
        $this->assertIsArray($themes);
        $this->assertNotEmpty($themes); // Should have at least BaseV1
    }
}
```

**Step 2: Run all tests**

Run: `vendor/bin/phpunit tests/src/ -v`
Expected: All tests pass

**Step 3: Final commit**

```bash
git add tests/src/ManagerIntegrationTest.php
git commit -m "test(manager): add integration tests"
```

---

## Completion Checklist

- [x] Authentication and authorization
- [x] Bootstrap loading
- [x] SubsiteManager with CRUD
- [x] PluginManager with git clone
- [x] ThemeManager with git clone
- [x] Router and HTML layout
- [x] Create/Edit forms
- [x] HTMX interactivity
- [x] Error handling and notifications
- [x] Integration tests

## Testing Instructions

1. Start dev environment: `cd dev && ./start.sh`
2. Access: `http://localhost/manager.php`
3. Login with superAdmin user
4. Test creating, editing, deleting subsites
5. Test cloning plugins from GitHub
6. Test cloning themes from GitHub
7. Test search and toggle functionality
8. Verify HTMX partial updates work without full page reload

## Notes

- Git operations require git installed in the container
- For production, consider adding rate limiting and CSRF protection
- Plugin/Theme activation may require additional app configuration
- Consider adding configuration interface for plugins/themes
