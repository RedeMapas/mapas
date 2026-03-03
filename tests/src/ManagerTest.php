<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    protected function setUp(): void
    {
        // Clean session before each test
        $_SESSION = [];
    }

    public function testUnauthenticatedUserRedirectsToLogin(): void
    {
        $_SESSION = [];

        ob_start();
        require __DIR__ . '/../../public/manager.php';
        $output = ob_get_clean();

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
}
