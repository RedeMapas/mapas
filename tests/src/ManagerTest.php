<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

define('TESTING', true);

class ManagerTest extends TestCase
{
    public function testUnauthenticatedUserRedirectsToLogin()
    {
        $_SESSION = [];

        $this->expectOutputString('');

        require __DIR__ . '/../../public/manager.php';
    }

    public function testNonSuperAdminUserReturns403()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['admin'];

        $this->expectOutputString('HTTP/1.1 403 Forbidden');

        require __DIR__ . '/../../public/manager.php';
    }

    public function testSuperAdminUserCanAccess()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_roles'] = ['superAdmin'];

        $this->expectOutputString('200 OK');

        require __DIR__ . '/../../public/manager.php';
    }
}
