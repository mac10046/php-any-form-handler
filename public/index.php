<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\ConfigLoader;
use App\FormHandler;
use App\Response;

$configDir = __DIR__ . '/../configs';
$configLoader = new ConfigLoader($configDir);
$handler = new FormHandler($configLoader);

$configId = $_POST['configId'] ?? $_GET['configId'] ?? null;

if ($configId) {
    $allowedOrigins = $handler->getAllowedOrigins($configId);
    Response::handleCors($allowedOrigins);
} else {
    Response::handleCors(['*']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed. Use POST.', 405);
}

if (!$configId) {
    Response::error('Missing configId parameter', 400);
}

$result = $handler->process($_POST);

if ($result['success']) {
    Response::success($result['message'], $result['redirect']);
} else {
    Response::error($result['message'], 400, $result['redirect']);
}
