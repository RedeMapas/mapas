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
