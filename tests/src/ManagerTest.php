<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    protected function setUp(): void
    {
        // Clean session before each test
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testUnauthenticatedUserRedirectsToLogin(): void
    {
        $_SESSION = [];

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        ob_get_clean();

        $this->assertStringContainsString('Location: /auth/login', implode('', headers_list()));
    }

    public function testNonSuperAdminUserReturns403(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['admin'];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied');

        require __DIR__ . '/../../public/manager.php';
    }

    public function testSuperAdminUserCanAccess(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Mapas Culturais - Manager', $output);
        $this->assertStringContainsString('Subsites', $output);
        $this->assertStringContainsString('Plugins', $output);
        $this->assertStringContainsString('Themes', $output);
    }

    public function testRouterHandlesSubsiteEntity(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'subsite';
        $_GET['action'] = 'list';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Subsites', $output);
    }

    public function testRouterHandlesPluginEntity(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'plugin';
        $_GET['action'] = 'list';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Plugins', $output);
    }

    public function testRouterHandlesThemeEntity(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'theme';
        $_GET['action'] = 'list';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Themes', $output);
    }

    public function testDefaultActionIsDashboard(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET = [];

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Mapas Culturais - Manager', $output);
    }

    public function testDashboardShowsCounts(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Subsites', $output);
        $this->assertStringContainsString('Plugins', $output);
        $this->assertStringContainsString('Themes', $output);
    }

    public function testSubsiteCreateFormRenders(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'subsite';
        $_GET['action'] = 'create';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Create New Subsite', $output);
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('URL (subdomain)', $output);
        $this->assertStringContainsString('Owner (Agent ID)', $output);
    }

    public function testPluginCloneFormRenders(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'plugin';
        $_GET['action'] = 'clone';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Clone Plugin from GitHub', $output);
        $this->assertStringContainsString('GitHub Repository URL', $output);
    }

    public function testThemeCloneFormRenders(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'theme';
        $_GET['action'] = 'clone';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Clone Theme from GitHub', $output);
        $this->assertStringContainsString('GitHub Repository URL', $output);
    }

    public function testHtmxJsonResponseForSubsiteSearch(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'subsite';
        $_GET['action'] = 'search';
        $_GET['format'] = 'json';
        $_GET['q'] = 'test';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('view', $data);
        $this->assertEquals('subsite-search', $data['view']);
    }

    public function testHtmxJsonResponseForPluginList(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'plugin';
        $_GET['action'] = 'list';
        $_GET['format'] = 'json';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('view', $data);
        $this->assertEquals('plugin-list', $data['view']);
    }

    public function testHtmxJsonResponseForThemeList(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'theme';
        $_GET['action'] = 'list';
        $_GET['format'] = 'json';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('view', $data);
        $this->assertEquals('theme-list', $data['view']);
    }

    public function testLayoutIncludesHtmxLibrary(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('htmx.org', $output);
        $this->assertStringContainsString('cdn.tailwindcss.com', $output);
    }

    public function testLayoutIncludesToastContainer(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('toast-container', $output);
        $this->assertStringContainsString('showToast', $output);
    }

    public function testLayoutIncludesLoadingSpinner(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('loading-spinner', $output);
        $this->assertStringContainsString('htmx-indicator', $output);
    }

    public function testNavigationTabsArePresent(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('/manager.php?entity=subsite', $output);
        $this->assertStringContainsString('/manager.php?entity=plugin', $output);
        $this->assertStringContainsString('/manager.php?entity=theme', $output);
    }

    public function testSubsiteListHasHtmxActions(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'subsite';
        $_GET['action'] = 'list';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('hx-post', $output);
        $this->assertStringContainsString('hx-delete', $output);
        $this->assertStringContainsString('hx-confirm', $output);
    }

    public function testPluginListHasHtmxActions(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'plugin';
        $_GET['action'] = 'list';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('hx-post', $output);
        $this->assertStringContainsString('hx-delete', $output);
    }

    public function testThemeListHasHtmxActions(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];
        $_GET['entity'] = 'theme';
        $_GET['action'] = 'list';

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('hx-post', $output);
        $this->assertStringContainsString('hx-delete', $output);
    }
}
