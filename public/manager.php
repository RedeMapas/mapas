<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$testing = defined('TESTING');

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login');
    if (!$testing) exit;
    return;
}

if (!in_array('superAdmin', $_SESSION['user_roles'] ?? [])) {
    http_response_code(403);
    echo "HTTP/1.1 403 Forbidden";
    if (!$testing) exit;
    return;
}

http_response_code(200);
echo "200 OK";
