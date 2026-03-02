<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    public function testUnauthenticatedUserRedirectsToLogin()
    {
        $_SESSION = [];

        $this->expectOutputRegex('/Location: \/auth\/login/');

        require __DIR__ . '/../../public/manager.php';
    }

    public function testNonSuperAdminUserReturns403()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['admin'];

        $this->expectOutputRegex('/HTTP\/1\.1 403 Forbidden/');

        require __DIR__ . '/../../public/manager.php';
    }

    public function testSuperAdminUserCanAccess()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        $this->expectOutputRegex('/200 OK/');

        require __DIR__ . '/../../public/manager.php';
    }

    public function testAppInstanceIsLoaded()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        // After bootstrap, App::i() should be available
        $this->assertTrue(class_exists('MapasCulturais\App'));

        require __DIR__ . '/../../public/manager.php';
    }

    public function testEntityManagerIsAvailable()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        // EntityManager should be accessible
        require __DIR__ . '/../../public/manager.php';
        
        $app = \MapasCulturais\App::i();
        $this->assertNotNull($app->em);
    }
}
