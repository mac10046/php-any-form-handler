<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\ConfigLoader;
use App\Database;

$configDir = __DIR__ . '/../configs';
$configLoader = new ConfigLoader($configDir);
$templatesDir = __DIR__ . '/../templates';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tenant_id'])) {
    $tenantId = trim($_POST['tenant_id']);

    $config = $configLoader->loadByTenantId($tenantId);

    if ($config) {
        $_SESSION['tenant_id'] = $tenantId;
        $_SESSION['config'] = $config;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Tenant not found. Please check your Tenant ID.';
        include $templatesDir . '/dashboard-login.php';
        exit;
    }
}

if (!isset($_SESSION['tenant_id']) || !isset($_SESSION['config'])) {
    $error = '';
    include $templatesDir . '/dashboard-login.php';
    exit;
}

$tenantId = $_SESSION['tenant_id'];
$config = $_SESSION['config'];

try {
    $db = new Database($config['database']);
} catch (\Exception $e) {
    session_destroy();
    $error = 'Database connection error. Please contact support.';
    include $templatesDir . '/dashboard-login.php';
    exit;
}

$perPage = 20;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$currentForm = $_GET['form'] ?? null;
$offset = ($currentPage - 1) * $perPage;

$formNames = $db->getFormNames();
$totalCount = $db->countSubmissions($currentForm);
$totalPages = (int) ceil($totalCount / $perPage);
$submissions = $db->getSubmissions($perPage, $offset, $currentForm);

include $templatesDir . '/dashboard-list.php';
