<?php
declare(strict_types=1);

/**
 * Mapas Culturais Manager - Admin Panel
 * 
 * Standalone admin panel for managing subsites, plugins, and themes.
 * Requires superAdmin role for access.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
if (empty($_SESSION['user_id'])) {
    header('Location: /auth/login');
    exit;
}

// Load application bootstrap
require __DIR__ . '/bootstrap.php';

// Authorization check - require superAdmin role
$user = $app->user;
if (!$user || !$user->is('superAdmin')) {
    http_response_code(403);
    throw new \Exception('Access denied');
}

// Initialize managers
$subsiteManager = new \MapasCulturais\Managers\SubsiteManager($app);
$pluginManager = new \MapasCulturais\Managers\PluginManager($app);
$themeManager = new \MapasCulturais\Managers\ThemeManager($app);

// Simple router
$entity = $_GET['entity'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle JSON requests (HTMX)
$isJsonRequest = isset($_GET['format']) && $_GET['format'] === 'json';

// Route handling
$response = [];
$httpCode = 200;

try {
    switch ($entity) {
        case 'subsite':
            $response = handleSubsiteAction($action, $id, $subsiteManager, $app);
            break;
        case 'plugin':
            $response = handlePluginAction($action, $pluginManager);
            break;
        case 'theme':
            $response = handleThemeAction($action, $themeManager);
            break;
        case 'dashboard':
        default:
            $response = [
                'view' => 'dashboard',
                'data' => [
                    'subsites_count' => count($subsiteManager->list()),
                    'plugins_count' => count($pluginManager->list()),
                    'themes_count' => count($themeManager->list()),
                ]
            ];
            break;
    }
} catch (\Exception $e) {
    $httpCode = 500;
    $response = ['error' => $e->getMessage()];
}

// Return JSON for HTMX requests
if ($isJsonRequest) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Render HTML layout
renderLayout($entity, $action, $response, $app);

/**
 * Handle subsite actions
 */
function handleSubsiteAction(string $action, ?int $id, \MapasCulturais\Managers\SubsiteManager $manager, $app): array
{
    switch ($action) {
        case 'list':
            return ['view' => 'subsite-list', 'data' => ['subsites' => $manager->list()]];
        
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'name' => $_POST['name'] ?? '',
                    'url' => $_POST['url'] ?? '',
                    'owner' => (int)($_POST['owner'] ?? 1),
                    'namespace' => $_POST['namespace'] ?? 'Subsite',
                ];
                $subsite = $manager->create($data);
                return ['view' => 'subsite-created', 'data' => ['subsite' => $subsite]];
            }
            return ['view' => 'subsite-create', 'data' => []];
        
        case 'edit':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'name' => $_POST['name'] ?? '',
                    'url' => $_POST['url'] ?? '',
                ];
                $subsite = $manager->update($id, $data);
                return ['view' => 'subsite-updated', 'data' => ['subsite' => $subsite]];
            }
            $subsite = $app->repo('Subsite')->find($id);
            return ['view' => 'subsite-edit', 'data' => ['subsite' => $subsite]];
        
        case 'toggle':
            $manager->toggleStatus($id);
            return ['view' => 'subsite-toggled', 'data' => ['id' => $id]];
        
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $manager->delete($id);
                return ['view' => 'subsite-deleted', 'data' => ['id' => $id]];
            }
            return ['view' => 'subsite-delete-confirm', 'data' => ['id' => $id]];
        
        case 'search':
            $query = $_GET['q'] ?? '';
            $subsites = $manager->list();
            if ($query) {
                $subsites = array_filter($subsites, fn($s) => stripos($s->name, $query) !== false || stripos($s->url, $query) !== false);
            }
            return ['view' => 'subsite-search', 'data' => ['subsites' => $subsites, 'query' => $query]];
        
        default:
            return ['view' => 'subsite-list', 'data' => ['subsites' => $manager->list()]];
    }
}

/**
 * Handle plugin actions
 */
function handlePluginAction(string $action, \MapasCulturais\Managers\PluginManager $manager): array
{
    switch ($action) {
        case 'list':
            return ['view' => 'plugin-list', 'data' => ['plugins' => $manager->list()]];
        
        case 'clone':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $repoUrl = $_POST['repo_url'] ?? '';
                $pluginName = $_POST['plugin_name'] ?? basename($repoUrl, '.git');
                $manager->cloneFromGithub($repoUrl, $pluginName);
                return ['view' => 'plugin-cloned', 'data' => ['name' => $pluginName]];
            }
            return ['view' => 'plugin-clone', 'data' => []];
        
        case 'toggle':
            return ['view' => 'plugin-toggled', 'data' => ['id' => $_GET['name'] ?? '']];
        
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $manager->delete($_GET['name'] ?? '');
                return ['view' => 'plugin-deleted', 'data' => ['name' => $_GET['name'] ?? '']];
            }
            return ['view' => 'plugin-delete-confirm', 'data' => ['name' => $_GET['name'] ?? '']];
        
        default:
            return ['view' => 'plugin-list', 'data' => ['plugins' => $manager->list()]];
    }
}

/**
 * Handle theme actions
 */
function handleThemeAction(string $action, \MapasCulturais\Managers\ThemeManager $manager): array
{
    switch ($action) {
        case 'list':
            return ['view' => 'theme-list', 'data' => ['themes' => $manager->list()]];
        
        case 'clone':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $repoUrl = $_POST['repo_url'] ?? '';
                $themeName = $_POST['theme_name'] ?? basename($repoUrl, '.git');
                $manager->cloneFromGithub($repoUrl, $themeName);
                return ['view' => 'theme-cloned', 'data' => ['name' => $themeName]];
            }
            return ['view' => 'theme-clone', 'data' => []];
        
        case 'activate':
            $themeName = $_GET['name'] ?? '';
            $manager->activate($themeName);
            return ['view' => 'theme-activated', 'data' => ['name' => $themeName]];
        
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $manager->delete($_GET['name'] ?? '');
                return ['view' => 'theme-deleted', 'data' => ['name' => $_GET['name'] ?? '']];
            }
            return ['view' => 'theme-delete-confirm', 'data' => ['name' => $_GET['name'] ?? '']];
        
        default:
            return ['view' => 'theme-list', 'data' => ['themes' => $manager->list()]];
    }
}

/**
 * Render main HTML layout with HTMX and TailwindCSS
 */
function renderLayout(string $entity, string $action, array $response, $app): void
{
    $pageTitle = 'Mapas Culturais - Manager';
    $activeTab = $entity;
    
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <style>
        .htmx-request { opacity: 0.5; }
        .toast { transition: opacity 0.3s ease-in-out; }
        .toast.hidden { opacity: 0; pointer-events: none; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Logged in as: <?= htmlspecialchars($app->user->email ?? 'Admin') ?></span>
                    <a href="/" class="text-sm text-blue-600 hover:text-blue-800">Back to site</a>
                </div>
            </div>
        </div>
    </header>

    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8">
                <a href="/manager.php" class="<?= $activeTab === 'dashboard' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Dashboard</a>
                <a href="/manager.php?entity=subsite" class="<?= $activeTab === 'subsite' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Subsites</a>
                <a href="/manager.php?entity=plugin" class="<?= $activeTab === 'plugin' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Plugins</a>
                <a href="/manager.php?entity=theme" class="<?= $activeTab === 'theme' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Themes</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <?= renderContent($entity, $action, $response, $app) ?>
    </main>

    <div id="toast-container" class="fixed bottom-4 right-4 space-y-2"></div>

    <script>
        function showToast(message, type = 'success') {
            const colors = { success: 'bg-green-500', error: 'bg-red-500', info: 'bg-blue-500' };
            const toast = document.createElement('div');
            toast.className = colors[type] + ' text-white px-6 py-3 rounded shadow-lg toast';
            toast.textContent = message;
            const container = document.getElementById('toast-container');
            container.appendChild(toast);
            setTimeout(() => { toast.classList.add('hidden'); setTimeout(() => toast.remove(), 300); }, 3000);
        }
        document.body.addEventListener('htmx:afterRequest', function(event) {
            if (event.detail.successful) {
                try {
                    const data = JSON.parse(event.detail.xhr.response);
                    if (data.toast) showToast(data.toast.message, data.toast.type || 'success');
                } catch (e) {}
            }
        });
    </script>
</body>
</html>
    <?php
}

/**
 * Render content based on entity and action
 */
function renderContent(string $entity, string $action, array $response, $app): string
{
    $view = $response['view'] ?? 'dashboard';
    $data = $response['data'] ?? [];
    
    switch ($view) {
        case 'dashboard': return renderDashboard($data, $app);
        case 'subsite-list':
        case 'subsite-search': return renderSubsiteList($data['subsites'] ?? [], $data['query'] ?? '', $app);
        case 'subsite-create': return renderSubsiteCreateForm($app);
        case 'subsite-edit': return renderSubsiteEditForm($data['subsite'] ?? null, $app);
        case 'plugin-list': return renderPluginList($data['plugins'] ?? [], $app);
        case 'plugin-clone': return renderPluginCloneForm($app);
        case 'theme-list': return renderThemeList($data['themes'] ?? [], $app);
        case 'theme-clone': return renderThemeCloneForm($app);
        default: return '<div class="text-gray-600">View not found: ' . htmlspecialchars($view) . '</div>';
    }
}

/**
 * Render dashboard view
 */
function renderDashboard(array $data, $app): string
{
    ob_start();
    ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Subsites</dt>
                            <dd class="text-2xl font-semibold text-gray-900"><?= $data['subsites_count'] ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4"><a href="/manager.php?entity=subsite" class="text-sm font-medium text-blue-600 hover:text-blue-500">Manage subsites →</a></div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Plugins</dt>
                            <dd class="text-2xl font-semibold text-gray-900"><?= $data['plugins_count'] ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4"><a href="/manager.php?entity=plugin" class="text-sm font-medium text-green-600 hover:text-green-500">Manage plugins →</a></div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Themes</dt>
                            <dd class="text-2xl font-semibold text-gray-900"><?= $data['themes_count'] ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4"><a href="/manager.php?entity=theme" class="text-sm font-medium text-purple-600 hover:text-purple-500">Manage themes →</a></div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render subsite list
 */
function renderSubsiteList(array $subsites, string $query = '', $app): string
{
    ob_start();
    ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Subsites</h2>
            <a href="/manager.php?entity=subsite&action=create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">New Subsite</a>
        </div>
        <div class="relative">
            <input type="text" hx-get="/manager.php?entity=subsite&action=search&format=json" hx-trigger="input changed delay:500ms" hx-target="#subsite-list" hx-swap="outerHTML" name="q" placeholder="Search subsites..." value="<?= htmlspecialchars($query) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div id="subsite-list" class="bg-white shadow overflow-hidden rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php if (empty($subsites)): ?>
                    <li class="px-4 py-8 text-center text-gray-500">No subsites found</li>
                <?php else: ?>
                    <?php foreach ($subsites as $subsite): ?>
                        <li class="px-4 py-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($subsite->name) ?></h3>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($subsite->url) ?><?php if ($subsite->aliasUrl): ?> (<?= htmlspecialchars($subsite->aliasUrl) ?>)<?php endif; ?></p>
                                    <p class="text-xs text-gray-400 mt-1">ID: <?= $subsite->id ?> | Status: <span id="status-<?= $subsite->id ?>" class="<?= $subsite->status ? 'text-green-600' : 'text-red-600' ?>"><?= $subsite->status ? 'Active' : 'Inactive' ?></span></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button hx-post="/manager.php?entity=subsite&action=toggle&id=<?= $subsite->id ?>&format=json" hx-target="#status-<?= $subsite->id ?>" hx-swap="outerHTML" class="text-sm <?= $subsite->status ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?> px-3 py-1 rounded hover:opacity-80"><?= $subsite->status ? 'Deactivate' : 'Activate' ?></button>
                                    <a href="/manager.php?entity=subsite&action=edit&id=<?= $subsite->id ?>" class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded hover:bg-blue-200">Edit</a>
                                    <button hx-delete="/manager.php?entity=subsite&action=delete&id=<?= $subsite->id ?>&format=json" hx-confirm="Are you sure you want to delete this subsite?" class="text-sm bg-red-100 text-red-800 px-3 py-1 rounded hover:bg-red-200">Delete</button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render subsite create form
 */
function renderSubsiteCreateForm($app): string
{
    ob_start();
    ?>
    <div class="max-w-2xl">
        <div class="mb-4"><a href="/manager.php?entity=subsite" class="text-blue-600 hover:text-blue-800 text-sm">← Back to subsites</a></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Create New Subsite</h2>
        <form method="POST" action="/manager.php?entity=subsite&action=create" class="space-y-4">
            <div><label class="block text-sm font-medium text-gray-700">Name</label><input type="text" name="name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700">URL (subdomain)</label><input type="text" name="url" required placeholder="subsite.example.com" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700">Owner (Agent ID)</label><input type="number" name="owner" required value="1" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700">Namespace</label><input type="text" name="namespace" value="Subsite" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></div>
            <div class="pt-4"><button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-medium">Create Subsite</button></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render subsite edit form
 */
function renderSubsiteEditForm(?\MapasCulturais\Entities\Subsite $subsite, $app): string
{
    if (!$subsite) return '<div class="text-red-600">Subsite not found</div>';
    ob_start();
    ?>
    <div class="max-w-2xl">
        <div class="mb-4"><a href="/manager.php?entity=subsite" class="text-blue-600 hover:text-blue-800 text-sm">← Back to subsites</a></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Subsite</h2>
        <form method="POST" action="/manager.php?entity=subsite&action=edit&id=<?= $subsite->id ?>" class="space-y-4">
            <div><label class="block text-sm font-medium text-gray-700">Name</label><input type="text" name="name" required value="<?= htmlspecialchars($subsite->name) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700">URL (subdomain)</label><input type="text" name="url" required value="<?= htmlspecialchars($subsite->url) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></div>
            <div class="pt-4"><button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-medium">Update Subsite</button></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render plugin list
 */
function renderPluginList(array $plugins, $app): string
{
    ob_start();
    ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Plugins</h2>
            <a href="/manager.php?entity=plugin&action=clone" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">Clone from GitHub</a>
        </div>
        <div class="bg-white shadow overflow-hidden rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php if (empty($plugins)): ?>
                    <li class="px-4 py-8 text-center text-gray-500">No plugins installed</li>
                <?php else: ?>
                    <?php foreach ($plugins as $plugin): ?>
                        <li class="px-4 py-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($plugin['name']) ?></h3>
                                    <p class="text-sm text-gray-500">Path: <?= htmlspecialchars($plugin['path']) ?></p>
                                    <p class="text-xs text-gray-400 mt-1">Status: <span class="<?= $plugin['enabled'] ? 'text-green-600' : 'text-red-600' ?>"><?= $plugin['enabled'] ? 'Enabled' : 'Disabled' ?></span></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button hx-post="/manager.php?entity=plugin&action=toggle&name=<?= urlencode($plugin['name']) ?>&format=json" class="text-sm <?= $plugin['enabled'] ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?> px-3 py-1 rounded hover:opacity-80"><?= $plugin['enabled'] ? 'Disable' : 'Enable' ?></button>
                                    <button hx-delete="/manager.php?entity=plugin&action=delete&name=<?= urlencode($plugin['name']) ?>&format=json" hx-confirm="Are you sure you want to delete this plugin?" class="text-sm bg-red-100 text-red-800 px-3 py-1 rounded hover:bg-red-200">Delete</button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render plugin clone form
 */
function renderPluginCloneForm($app): string
{
    ob_start();
    ?>
    <div class="max-w-2xl">
        <div class="mb-4"><a href="/manager.php?entity=plugin" class="text-green-600 hover:text-green-800 text-sm">← Back to plugins</a></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Clone Plugin from GitHub</h2>
        <form method="POST" action="/manager.php?entity=plugin&action=clone" class="space-y-4">
            <div><label class="block text-sm font-medium text-gray-700">GitHub Repository URL</label><input type="url" name="repo_url" required placeholder="https://github.com/user/plugin-name.git" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"></div>
            <div><label class="block text-sm font-medium text-gray-700">Plugin Name (optional)</label><input type="text" name="plugin_name" placeholder="Leave empty to extract from URL" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"><p class="mt-1 text-sm text-gray-500">Will be extracted from URL if not provided</p></div>
            <div class="pt-4"><button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-medium">Clone Plugin</button></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render theme list
 */
function renderThemeList(array $themes, $app): string
{
    ob_start();
    ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Themes</h2>
            <a href="/manager.php?entity=theme&action=clone" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium">Clone from GitHub</a>
        </div>
        <div class="bg-white shadow overflow-hidden rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php if (empty($themes)): ?>
                    <li class="px-4 py-8 text-center text-gray-500">No themes installed</li>
                <?php else: ?>
                    <?php foreach ($themes as $theme): ?>
                        <li class="px-4 py-4 hover:bg-gray-50 <?= $theme['active'] ? 'bg-purple-50' : '' ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($theme['name']) ?><?php if ($theme['active']): ?> <span class="ml-2 text-xs bg-purple-600 text-white px-2 py-0.5 rounded">Active</span><?php endif; ?></h3>
                                    <p class="text-sm text-gray-500">Path: <?= htmlspecialchars($theme['path']) ?></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <?php if (!$theme['active']): ?>
                                        <button hx-post="/manager.php?entity=theme&action=activate&name=<?= urlencode($theme['name']) ?>&format=json" hx-target="closest li" class="text-sm bg-purple-100 text-purple-800 px-3 py-1 rounded hover:bg-purple-200">Activate</button>
                                    <?php endif; ?>
                                    <button hx-delete="/manager.php?entity=theme&action=delete&name=<?= urlencode($theme['name']) ?>&format=json" hx-confirm="Are you sure you want to delete this theme?" class="text-sm bg-red-100 text-red-800 px-3 py-1 rounded hover:bg-red-200" <?= $theme['active'] ? 'disabled' : '' ?>>Delete</button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render theme clone form
 */
function renderThemeCloneForm($app): string
{
    ob_start();
    ?>
    <div class="max-w-2xl">
        <div class="mb-4"><a href="/manager.php?entity=theme" class="text-purple-600 hover:text-purple-800 text-sm">← Back to themes</a></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Clone Theme from GitHub</h2>
        <form method="POST" action="/manager.php?entity=theme&action=clone" class="space-y-4">
            <div><label class="block text-sm font-medium text-gray-700">GitHub Repository URL</label><input type="url" name="repo_url" required placeholder="https://github.com/user/theme-name.git" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"></div>
            <div><label class="block text-sm font-medium text-gray-700">Theme Name (optional)</label><input type="text" name="theme_name" placeholder="Leave empty to extract from URL" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"><p class="mt-1 text-sm text-gray-500">Will be extracted from URL if not provided</p></div>
            <div class="pt-4"><button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 font-medium">Clone Theme</button></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
