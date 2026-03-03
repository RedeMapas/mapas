<?php
declare(strict_types=1);

/**
 * Mapas Culturais Manager - Admin Panel
 * 
 * Standalone admin panel for managing subsites, plugins, and themes.
 * Requires superAdmin role for access.
 */

// Load application bootstrap FIRST (this initializes session and auth)
require __DIR__ . '/bootstrap.php';

// Authentication check - use Mapas Culturais auth system
if (!$app->user || !$app->user->id) {
    // Not authenticated, redirect to login
    $app->auth->requireAuthentication('/manager.php');
    exit;
}

// Authorization check - require superAdmin role
$user = $app->user;
if (!$user->is('superAdmin')) {
    http_response_code(403);
    throw new \Exception('Access denied: superAdmin role required');
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
$toast = null;

try {
    switch ($entity) {
        case 'subsite':
            $result = handleSubsiteAction($action, $id, $subsiteManager, $app);
            $response = $result['response'];
            $toast = $result['toast'] ?? null;
            break;
        case 'plugin':
            $result = handlePluginAction($action, $pluginManager);
            $response = $result['response'];
            $toast = $result['toast'] ?? null;
            break;
        case 'theme':
            $result = handleThemeAction($action, $themeManager);
            $response = $result['response'];
            $toast = $result['toast'] ?? null;
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
    $toast = ['message' => $e->getMessage(), 'type' => 'error'];
}

// Return JSON for HTMX requests
if ($isJsonRequest) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(array_merge($response, $toast ? ['toast' => $toast] : []));
    exit;
}

// Render HTML layout
renderLayout($entity, $action, $response, $app, $toast);

/**
 * Handle subsite actions
 */
function handleSubsiteAction(string $action, ?int $id, \MapasCulturais\Managers\SubsiteManager $manager, $app): array
{
    $toast = null;
    
    switch ($action) {
        case 'list':
            return ['response' => ['view' => 'subsite-list', 'data' => ['subsites' => $manager->list()]], 'toast' => null];
        
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate input
                $errors = validateSubsiteInput($_POST['name'] ?? '', $_POST['url'] ?? '');
                if (!empty($errors)) {
                    return ['response' => ['view' => 'subsite-create', 'data' => ['errors' => $errors, 'input' => $_POST]], 'toast' => ['message' => implode(', ', $errors), 'type' => 'error']];
                }
                
                $data = [
                    'name' => sanitize($_POST['name']),
                    'url' => sanitize($_POST['url']),
                    'owner' => (int)($_POST['owner'] ?? 1),
                    'namespace' => sanitize($_POST['namespace'] ?? 'Subsite'),
                ];
                $subsite = $manager->create($data);
                return ['response' => ['view' => 'subsite-created', 'data' => ['subsite' => $subsite]], 'toast' => ['message' => 'Subsite created successfully!', 'type' => 'success']];
            }
            return ['response' => ['view' => 'subsite-create', 'data' => []], 'toast' => null];
        
        case 'edit':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $errors = validateSubsiteInput($_POST['name'] ?? '', $_POST['url'] ?? '');
                if (!empty($errors)) {
                    $subsite = $app->repo('Subsite')->find($id);
                    return ['response' => ['view' => 'subsite-edit', 'data' => ['subsite' => $subsite, 'errors' => $errors]], 'toast' => ['message' => implode(', ', $errors), 'type' => 'error']];
                }
                
                $data = [
                    'name' => sanitize($_POST['name']),
                    'url' => sanitize($_POST['url']),
                ];
                $subsite = $manager->update($id, $data);
                return ['response' => ['view' => 'subsite-updated', 'data' => ['subsite' => $subsite]], 'toast' => ['message' => 'Subsite updated successfully!', 'type' => 'success']];
            }
            $subsite = $app->repo('Subsite')->find($id);
            return ['response' => ['view' => 'subsite-edit', 'data' => ['subsite' => $subsite]], 'toast' => null];
        
        case 'toggle':
            $manager->toggleStatus($id);
            $subsite = $app->repo('Subsite')->find($id);
            return [
                'response' => ['view' => 'subsite-toggled', 'data' => ['id' => $id, 'status' => $subsite->status]],
                'toast' => ['message' => $subsite->status ? 'Subsite activated!' : 'Subsite deactivated!', 'type' => 'success']
            ];
        
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $manager->delete($id);
                return ['response' => ['view' => 'subsite-deleted', 'data' => ['id' => $id]], 'toast' => ['message' => 'Subsite deleted successfully!', 'type' => 'success']];
            }
            return ['response' => ['view' => 'subsite-delete-confirm', 'data' => ['id' => $id]], 'toast' => null];
        
        case 'search':
            $query = $_GET['q'] ?? '';
            $subsites = $manager->list();
            if ($query) {
                $subsites = array_filter($subsites, fn($s) => stripos($s->name, $query) !== false || stripos($s->url, $query) !== false);
            }
            return ['response' => ['view' => 'subsite-search', 'data' => ['subsites' => $subsites, 'query' => $query]], 'toast' => null];
        
        default:
            return ['response' => ['view' => 'subsite-list', 'data' => ['subsites' => $manager->list()]], 'toast' => null];
    }
}

/**
 * Handle plugin actions
 */
function handlePluginAction(string $action, \MapasCulturais\Managers\PluginManager $manager): array
{
    switch ($action) {
        case 'list':
            return ['response' => ['view' => 'plugin-list', 'data' => ['plugins' => $manager->list()]], 'toast' => null];
        
        case 'clone':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $repoUrl = $_POST['repo_url'] ?? '';
                $pluginName = $_POST['plugin_name'] ?? basename($repoUrl, '.git');
                
                // Validate
                $errors = validatePluginInput($repoUrl, $pluginName);
                if (!empty($errors)) {
                    return ['response' => ['view' => 'plugin-clone', 'data' => ['errors' => $errors]], 'toast' => ['message' => implode(', ', $errors), 'type' => 'error']];
                }
                
                $manager->cloneFromGithub($repoUrl, $pluginName);
                return ['response' => ['view' => 'plugin-cloned', 'data' => ['name' => $pluginName]], 'toast' => ['message' => "Plugin '{$pluginName}' cloned successfully!", 'type' => 'success']];
            }
            return ['response' => ['view' => 'plugin-clone', 'data' => []], 'toast' => null];
        
        case 'toggle':
            $pluginName = $_GET['name'] ?? '';
            $newStatus = $manager->toggle($pluginName);
            return [
                'response' => ['view' => 'plugin-toggled', 'data' => ['name' => $pluginName, 'enabled' => $newStatus]],
                'toast' => ['message' => $newStatus ? "Plugin '{$pluginName}' enabled!" : "Plugin '{$pluginName}' disabled!", 'type' => 'success']
            ];
        
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $pluginName = $_GET['name'] ?? '';
                $manager->delete($pluginName);
                return ['response' => ['view' => 'plugin-deleted', 'data' => ['name' => $pluginName]], 'toast' => ['message' => "Plugin '{$pluginName}' deleted!", 'type' => 'success']];
            }
            return ['response' => ['view' => 'plugin-delete-confirm', 'data' => ['name' => $_GET['name'] ?? '']], 'toast' => null];
        
        default:
            return ['response' => ['view' => 'plugin-list', 'data' => ['plugins' => $manager->list()]], 'toast' => null];
    }
}

/**
 * Handle theme actions
 */
function handleThemeAction(string $action, \MapasCulturais\Managers\ThemeManager $manager): array
{
    switch ($action) {
        case 'list':
            return ['response' => ['view' => 'theme-list', 'data' => ['themes' => $manager->list()]], 'toast' => null];
        
        case 'clone':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $repoUrl = $_POST['repo_url'] ?? '';
                $themeName = $_POST['theme_name'] ?? basename($repoUrl, '.git');
                
                $errors = validateThemeInput($repoUrl, $themeName);
                if (!empty($errors)) {
                    return ['response' => ['view' => 'theme-clone', 'data' => ['errors' => $errors]], 'toast' => ['message' => implode(', ', $errors), 'type' => 'error']];
                }
                
                $manager->cloneFromGithub($repoUrl, $themeName);
                return ['response' => ['view' => 'theme-cloned', 'data' => ['name' => $themeName]], 'toast' => ['message' => "Theme '{$themeName}' cloned successfully!", 'type' => 'success']];
            }
            return ['response' => ['view' => 'theme-clone', 'data' => []], 'toast' => null];
        
        case 'activate':
            $themeName = $_GET['name'] ?? '';
            $manager->activate($themeName);
            return [
                'response' => ['view' => 'theme-activated', 'data' => ['name' => $themeName]],
                'toast' => ['message' => "Theme '{$themeName}' activated!", 'type' => 'success']
            ];
        
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $themeName = $_GET['name'] ?? '';
                $manager->delete($themeName);
                return ['response' => ['view' => 'theme-deleted', 'data' => ['name' => $themeName]], 'toast' => ['message' => "Theme '{$themeName}' deleted!", 'type' => 'success']];
            }
            return ['response' => ['view' => 'theme-delete-confirm', 'data' => ['name' => $_GET['name'] ?? '']], 'toast' => null];
        
        default:
            return ['response' => ['view' => 'theme-list', 'data' => ['themes' => $manager->list()]], 'toast' => null];
    }
}

/**
 * Validation helpers
 */
function validateSubsiteInput(string $name, string $url): array
{
    $errors = [];
    if (empty(trim($name))) {
        $errors[] = 'Name is required';
    }
    if (empty(trim($url))) {
        $errors[] = 'URL is required';
    } elseif (!preg_match('/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/i', $url)) {
        $errors[] = 'Invalid URL format (use subdomain format)';
    }
    return $errors;
}

function validatePluginInput(string $repoUrl, string $pluginName): array
{
    $errors = [];
    if (empty(trim($repoUrl))) {
        $errors[] = 'Repository URL is required';
    } elseif (!preg_match('/^https:\/\/github\.com\/[\w-]+\/[\w-]+\.git$/', $repoUrl)) {
        $errors[] = 'Invalid GitHub repository URL';
    }
    if (!empty(trim($pluginName)) && !preg_match('/^[\w-]+$/', $pluginName)) {
        $errors[] = 'Invalid plugin name';
    }
    return $errors;
}

function validateThemeInput(string $repoUrl, string $themeName): array
{
    $errors = [];
    if (empty(trim($repoUrl))) {
        $errors[] = 'Repository URL is required';
    } elseif (!preg_match('/^https:\/\/github\.com\/[\w-]+\/[\w-]+\.git$/', $repoUrl)) {
        $errors[] = 'Invalid GitHub repository URL';
    }
    if (!empty(trim($themeName)) && !preg_match('/^[\w-]+$/', $themeName)) {
        $errors[] = 'Invalid theme name';
    }
    return $errors;
}

function sanitize(string $input): string
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Render main HTML layout with HTMX and TailwindCSS
 */
function renderLayout(string $entity, string $action, array $response, $app, ?array $toast = null): void
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
        .htmx-request { opacity: 0.5; pointer-events: none; }
        .htmx-indicator { display: none; }
        .htmx-request .htmx-indicator { display: inline-block; }
        .toast { transition: all 0.3s ease-in-out; transform: translateX(0); }
        .toast.hidden { opacity: 0; transform: translateX(100%); pointer-events: none; }
        .loading-spinner { animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" hx-on::after-request="handleHtmxResponse(event)">
    <!-- Header -->
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

    <!-- Navigation Tabs -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8">
                <a href="/manager.php" class="<?= $activeTab === 'dashboard' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Dashboard</a>
                <a href="/manager.php?entity=subsite" class="<?= $activeTab === 'subsite' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Subsites</a>
                <a href="/manager.php?entity=plugin" class="<?= $activeTab === 'plugin' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Plugins</a>
                <a href="/manager.php?entity=theme" class="<?= $activeTab === 'theme' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Themes</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <?= renderContent($entity, $action, $response, $app) ?>
    </main>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 space-y-2 z-50"></div>

    <!-- Loading Indicator Template -->
    <template id="loading-spinner">
        <svg class="loading-spinner h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </template>

    <script>
        // Toast notification system
        function showToast(message, type = 'success', duration = 3000) {
            const colors = { 
                success: 'bg-green-500 border-green-600', 
                error: 'bg-red-500 border-red-600', 
                info: 'bg-blue-500 border-blue-600' 
            };
            const icons = {
                success: '✓',
                error: '✕',
                info: 'ℹ'
            };
            
            const toast = document.createElement('div');
            toast.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg border toast flex items-center space-x-3 min-w-[300px]`;
            toast.innerHTML = `<span class="font-bold">${icons[type]}</span><span>${message}</span>`;
            
            const container = document.getElementById('toast-container');
            container.appendChild(toast);
            
            // Trigger reflow for animation
            requestAnimationFrame(() => toast.classList.remove('hidden'));
            
            setTimeout(() => {
                toast.classList.add('hidden');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Handle HTMX responses
        function handleHtmxResponse(event) {
            const detail = event.detail;
            if (!detail.successful) return;
            
            try {
                const data = JSON.parse(detail.xhr.response);
                if (data.toast) {
                    showToast(data.toast.message, data.toast.type || 'success');
                }
                // Handle redirect after successful operations
                if (data.redirect) {
                    setTimeout(() => window.location.href = data.redirect, 500);
                }
            } catch (e) {
                // Not JSON response, ignore
            }
        }

        // Show initial toast if provided
        <?php if ($toast): ?>
        document.addEventListener('DOMContentLoaded', () => {
            showToast(<?= json_encode($toast['message']) ?>, <?= json_encode($toast['type'] ?? 'success') ?>);
        });
        <?php endif; ?>

        // Add loading indicators to all HTMX buttons
        document.body.addEventListener('htmx:beforeRequest', function(event) {
            const btn = event.target.closest('button');
            if (btn) {
                btn.disabled = true;
                const spinner = document.getElementById('loading-spinner').content.cloneNode(true);
                btn.appendChild(spicker);
            }
        });
        
        document.body.addEventListener('htmx:afterRequest', function(event) {
            const btn = event.target.closest('button');
            if (btn) {
                btn.disabled = false;
                const spinner = btn.querySelector('.loading-spinner');
                if (spinner) spinner.remove();
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
        case 'subsite-create': return renderSubsiteCreateForm($data['input'] ?? [], $data['errors'] ?? [], $app);
        case 'subsite-edit': return renderSubsiteEditForm($data['subsite'] ?? null, $data['errors'] ?? [], $app);
        case 'plugin-list': return renderPluginList($data['plugins'] ?? [], $app);
        case 'plugin-clone': return renderPluginCloneForm($data['errors'] ?? [], $app);
        case 'theme-list': return renderThemeList($data['themes'] ?? [], $app);
        case 'theme-clone': return renderThemeCloneForm($data['errors'] ?? [], $app);
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
        <!-- Subsites Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
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

        <!-- Plugins Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
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

        <!-- Themes Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
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
            <a href="/manager.php?entity=subsite&action=create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">New Subsite</a>
        </div>
        <div class="relative">
            <input type="text" 
                   hx-get="/manager.php?entity=subsite&action=search&format=json" 
                   hx-trigger="input changed delay:500ms" 
                   hx-target="#subsite-list" 
                   hx-swap="outerHTML" 
                   name="q" 
                   placeholder="Search subsites by name or URL..." 
                   value="<?= htmlspecialchars($query) ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
            <div class="absolute right-3 top-2.5 htmx-indicator">
                <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
        </div>
        <div id="subsite-list" class="bg-white shadow overflow-hidden rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php if (empty($subsites)): ?>
                    <li class="px-4 py-8 text-center text-gray-500">No subsites found</li>
                <?php else: ?>
                    <?php foreach ($subsites as $subsite): ?>
                        <li class="px-4 py-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($subsite->name) ?></h3>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($subsite->url) ?><?php if ($subsite->aliasUrl): ?> <span class="text-gray-400">(<?= htmlspecialchars($subsite->aliasUrl) ?>)</span><?php endif; ?></p>
                                    <p class="text-xs text-gray-400 mt-1">ID: <?= $subsite->id ?> | Status: <span id="status-<?= $subsite->id ?>" class="font-medium <?= $subsite->status ? 'text-green-600' : 'text-red-600' ?>"><?= $subsite->status ? 'Active' : 'Inactive' ?></span></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button hx-post="/manager.php?entity=subsite&action=toggle&id=<?= $subsite->id ?>&format=json" 
                                            hx-target="#subsite-list" 
                                            hx-swap="outerHTML"
                                            class="htmx-indicator-target text-sm <?= $subsite->status ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200' ?> px-3 py-1 rounded transition-colors">
                                        <?= $subsite->status ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                    <a href="/manager.php?entity=subsite&action=edit&id=<?= $subsite->id ?>" class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded hover:bg-blue-200 transition-colors">Edit</a>
                                    <button hx-delete="/manager.php?entity=subsite&action=delete&id=<?= $subsite->id ?>&format=json" 
                                            hx-confirm="Are you sure you want to delete this subsite? This action cannot be undone." 
                                            class="text-sm bg-red-100 text-red-800 px-3 py-1 rounded hover:bg-red-200 transition-colors">Delete</button>
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
function renderSubsiteCreateForm(array $input = [], array $errors = [], $app): string
{
    ob_start();
    ?>
    <div class="max-w-2xl">
        <div class="mb-4"><a href="/manager.php?entity=subsite" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">← Back to subsites</a></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Create New Subsite</h2>
        
        <?php if (!empty($errors)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="/manager.php?entity=subsite&action=create" class="space-y-4" novalidate>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required value="<?= htmlspecialchars($input['name'] ?? '') ?>" 
                       class="w-full px-3 py-2 border <?= !empty($errors) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' ?> rounded-md shadow-sm transition-shadow">
                <p class="mt-1 text-sm text-gray-500">The display name for this subsite</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL (subdomain) <span class="text-red-500">*</span></label>
                <input type="text" name="url" required value="<?= htmlspecialchars($input['url'] ?? '') ?>" placeholder="subsite.example.com"
                       class="w-full px-3 py-2 border <?= !empty($errors) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' ?> rounded-md shadow-sm transition-shadow">
                <p class="mt-1 text-sm text-gray-500">The subdomain URL for accessing this subsite</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Owner (Agent ID) <span class="text-red-500">*</span></label>
                <input type="number" name="owner" required value="<?= htmlspecialchars($input['owner'] ?? '1') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-shadow">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Namespace</label>
                <input type="text" name="namespace" value="<?= htmlspecialchars($input['namespace'] ?? 'Subsite') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-shadow">
            </div>
            
            <div class="pt-4 flex space-x-3">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-medium transition-colors flex items-center justify-center">
                    <span class="htmx-indicator mr-2"><svg class="loading-spinner h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                    Create Subsite
                </button>
                <a href="/manager.php?entity=subsite" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render subsite edit form
 */
function renderSubsiteEditForm(?\MapasCulturais\Entities\Subsite $subsite, array $errors = [], $app): string
{
    if (!$subsite) return '<div class="text-red-600 bg-red-50 px-4 py-3 rounded-md">Subsite not found</div>';
    
    ob_start();
    ?>
    <div class="max-w-2xl">
        <div class="mb-4"><a href="/manager.php?entity=subsite" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">← Back to subsites</a></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Subsite: <?= htmlspecialchars($subsite->name) ?></h2>
        
        <?php if (!empty($errors)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="/manager.php?entity=subsite&action=edit&id=<?= $subsite->id ?>" class="space-y-4" novalidate>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required value="<?= htmlspecialchars($subsite->name) ?>"
                       class="w-full px-3 py-2 border <?= !empty($errors) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' ?> rounded-md shadow-sm transition-shadow">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL (subdomain) <span class="text-red-500">*</span></label>
                <input type="text" name="url" required value="<?= htmlspecialchars($subsite->url) ?>"
                       class="w-full px-3 py-2 border <?= !empty($errors) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' ?> rounded-md shadow-sm transition-shadow">
            </div>
            
            <div class="pt-4 flex space-x-3">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-medium transition-colors flex items-center justify-center">
                    <span class="htmx-indicator mr-2"><svg class="loading-spinner h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                    Update Subsite
                </button>
                <a href="/manager.php?entity=subsite" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            </div>
        </form>
        
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Subsite Information</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">ID</dt><dd class="font-medium"><?= $subsite->id ?></dd></div>
                <div><dt class="text-gray-500">Status</dt><dd class="font-medium <?= $subsite->status ? 'text-green-600' : 'text-red-600' ?>"><?= $subsite->status ? 'Active' : 'Inactive' ?></dd></div>
                <div><dt class="text-gray-500">Namespace</dt><dd class="font-medium"><?= htmlspecialchars($subsite->namespace) ?></dd></div>
                <div><dt class="text-gray-500">Owner ID</dt><dd class="font-medium"><?= $subsite->_ownerId ?></dd></div>
            </dl>
        </div>
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
            <a href="/manager.php?entity=plugin&action=clone" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">Clone from GitHub</a>
        </div>
        <div class="bg-white shadow overflow-hidden rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php if (empty($plugins)): ?>
                    <li class="px-4 py-8 text-center text-gray-500">No plugins installed. Clone one from GitHub to get started!</li>
                <?php else: ?>
                    <?php foreach ($plugins as $plugin): ?>
                        <li class="px-4 py-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($plugin['name']) ?></h3>
                                    <p class="text-sm text-gray-500 font-mono text-xs mt-1"><?= htmlspecialchars($plugin['path']) ?></p>
                                    <p class="text-xs text-gray-400 mt-1">Status: <span class="font-medium <?= $plugin['enabled'] ? 'text-green-600' : 'text-red-600' ?>"><?= $plugin['enabled'] ? 'Enabled' : 'Disabled' ?></span></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button hx-post="/manager.php?entity=plugin&action=toggle&name=<?= urlencode($plugin['name']) ?>&format=json" 
                                            hx-target="closest li"
                                            hx-swap="outerHTML"
                                            class="htmx-indicator-target text-sm <?= $plugin['enabled'] ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200' ?> px-3 py-1 rounded transition-colors">
                                        <?= $plugin['enabled'] ? 'Disable' : 'Enable' ?>
                                    </button>
                                    <button hx-delete="/manager.php?entity=plugin&action=delete&name=<?= urlencode($plugin['name']) ?>&format=json" 
                                            hx-confirm="Are you sure you want to delete this plugin? This action cannot be undone." 
                                            class="text-sm bg-red-100 text-red-800 px-3 py-1 rounded hover:bg-red-200 transition-colors">Delete</button>
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
function renderPluginCloneForm(array $errors = [], $app): string
{
    ob_start();
    ?>
    <div class="max-w-2xl">
        <div class="mb-4"><a href="/manager.php?entity=plugin" class="text-green-600 hover:text-green-800 text-sm flex items-center">← Back to plugins</a></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Clone Plugin from GitHub</h2>
        
        <?php if (!empty($errors)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="/manager.php?entity=plugin&action=clone" class="space-y-4" novalidate>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">GitHub Repository URL <span class="text-red-500">*</span></label>
                <input type="url" name="repo_url" required placeholder="https://github.com/mapasculturais/plugin-name.git"
                       class="w-full px-3 py-2 border <?= !empty($errors) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-green-500 focus:border-green-500' ?> rounded-md shadow-sm transition-shadow font-mono text-sm">
                <p class="mt-1 text-sm text-gray-500">Must be a valid GitHub repository URL ending in .git</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plugin Name <span class="text-gray-400">(optional)</span></label>
                <input type="text" name="plugin_name" placeholder="Leave empty to extract from URL"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 transition-shadow">
                <p class="mt-1 text-sm text-gray-500">Will be automatically extracted from the repository URL if not provided</p>
            </div>
            
            <div class="pt-4 flex space-x-3">
                <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-medium transition-colors flex items-center justify-center">
                    <span class="htmx-indicator mr-2"><svg class="loading-spinner h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                    Clone Plugin
                </button>
                <a href="/manager.php?entity=plugin" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            </div>
        </form>
        
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-md p-4">
            <h3 class="text-sm font-medium text-blue-800 mb-2">Requirements</h3>
            <ul class="text-sm text-blue-700 space-y-1">
                <li>• Repository must contain a <code class="bg-blue-100 px-1 rounded">Plugin.php</code> file</li>
                <li>• Plugin must follow Mapas Culturais plugin structure</li>
                <li>• Server must have git installed and network access to GitHub</li>
            </ul>
        </div>
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
            <a href="/manager.php?entity=theme&action=clone" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">Clone from GitHub</a>
        </div>
        <div class="bg-white shadow overflow-hidden rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php if (empty($themes)): ?>
                    <li class="px-4 py-8 text-center text-gray-500">No themes installed. Clone one from GitHub to get started!</li>
                <?php else: ?>
                    <?php foreach ($themes as $theme): ?>
                        <li class="px-4 py-4 hover:bg-gray-50 transition-colors <?= $theme['active'] ? 'bg-purple-50' : '' ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <?= htmlspecialchars($theme['name']) ?>
                                        <?php if ($theme['active']): ?>
                                            <span class="ml-2 text-xs bg-purple-600 text-white px-2 py-0.5 rounded-full">Active Theme</span>
                                        <?php endif; ?>
                                    </h3>
                                    <p class="text-sm text-gray-500 font-mono text-xs mt-1"><?= htmlspecialchars($theme['path']) ?></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <?php if (!$theme['active']): ?>
                                        <button hx-post="/manager.php?entity=theme&action=activate&name=<?= urlencode($theme['name']) ?>&format=json" 
                                                hx-target="closest li"
                                                hx-swap="outerHTML"
                                                class="htmx-indicator-target text-sm bg-purple-100 text-purple-800 px-3 py-1 rounded hover:bg-purple-200 transition-colors">
                                            Activate
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-purple-600 font-medium">Currently Active</span>
                                    <?php endif; ?>
                                    <button hx-delete="/manager.php?entity=theme&action=delete&name=<?= urlencode($theme['name']) ?>&format=json" 
                                            hx-confirm="Are you sure you want to delete this theme? This action cannot be undone." 
                                            class="text-sm bg-red-100 text-red-800 px-3 py-1 rounded hover:bg-red-200 transition-colors"
                                            <?= $theme['active'] ? 'disabled title="Cannot delete active theme"' : '' ?>>
                                        Delete
                                    </button>
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
function renderThemeCloneForm(array $errors = [], $app): string
{
    ob_start();
    ?>
    <div class="max-w-2xl">
        <div class="mb-4"><a href="/manager.php?entity=theme" class="text-purple-600 hover:text-purple-800 text-sm flex items-center">← Back to themes</a></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Clone Theme from GitHub</h2>
        
        <?php if (!empty($errors)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="/manager.php?entity=theme&action=clone" class="space-y-4" novalidate>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">GitHub Repository URL <span class="text-red-500">*</span></label>
                <input type="url" name="repo_url" required placeholder="https://github.com/mapasculturais/theme-name.git"
                       class="w-full px-3 py-2 border <?= !empty($errors) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-purple-500 focus:border-purple-500' ?> rounded-md shadow-sm transition-shadow font-mono text-sm">
                <p class="mt-1 text-sm text-gray-500">Must be a valid GitHub repository URL ending in .git</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Theme Name <span class="text-gray-400">(optional)</span></label>
                <input type="text" name="theme_name" placeholder="Leave empty to extract from URL"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 transition-shadow">
                <p class="mt-1 text-sm text-gray-500">Will be automatically extracted from the repository URL if not provided</p>
            </div>
            
            <div class="pt-4 flex space-x-3">
                <button type="submit" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 font-medium transition-colors flex items-center justify-center">
                    <span class="htmx-indicator mr-2"><svg class="loading-spinner h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                    Clone Theme
                </button>
                <a href="/manager.php?entity=theme" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            </div>
        </form>
        
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-md p-4">
            <h3 class="text-sm font-medium text-blue-800 mb-2">Requirements</h3>
            <ul class="text-sm text-blue-700 space-y-1">
                <li>• Repository must contain a <code class="bg-blue-100 px-1 rounded">Theme.php</code> file</li>
                <li>• Theme must follow Mapas Culturais theme structure</li>
                <li>• Server must have git installed and network access to GitHub</li>
                <li>• Note: Activating a theme will change the application appearance</li>
            </ul>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
