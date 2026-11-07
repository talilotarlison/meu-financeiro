<?php
declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $requested = __DIR__ . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if (is_file($requested) && realpath($requested) !== realpath(__FILE__)) return false;
}

$config = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($config['timezone']);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: private, no-store, max-age=0');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; worker-src 'self'; manifest-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
session_name('meu_financeiro');
session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax', 'path' => '/']);
session_start();

require dirname(__DIR__) . '/app/Database.php';
require dirname(__DIR__) . '/app/Migrator.php';
require dirname(__DIR__) . '/app/Services/FinancialService.php';
require dirname(__DIR__) . '/app/Services/DashboardService.php';
require dirname(__DIR__) . '/app/Services/RecurrenceService.php';
require dirname(__DIR__) . '/app/Services/BudgetService.php';
require dirname(__DIR__) . '/app/Services/GoalService.php';
require dirname(__DIR__) . '/app/Services/InvestmentService.php';
require dirname(__DIR__) . '/app/Services/PatrimonyService.php';
require dirname(__DIR__) . '/app/Services/ImportService.php';
require dirname(__DIR__) . '/app/Services/BackupService.php';
require dirname(__DIR__) . '/app/Services/ReportService.php';
require dirname(__DIR__) . '/app/Helpers/functions.php';
require dirname(__DIR__) . '/app/Controllers/AuthController.php';
require dirname(__DIR__) . '/app/Controllers/DashboardController.php';
require dirname(__DIR__) . '/app/Controllers/ProfileController.php';
require dirname(__DIR__) . '/app/Controllers/SettingsController.php';
require dirname(__DIR__) . '/app/Controllers/AccountController.php';
require dirname(__DIR__) . '/app/Controllers/CategoryController.php';
require dirname(__DIR__) . '/app/Controllers/TransactionController.php';
require dirname(__DIR__) . '/app/Controllers/TransferController.php';
require dirname(__DIR__) . '/app/Controllers/CardController.php';
require dirname(__DIR__) . '/app/Controllers/CommitmentController.php';
require dirname(__DIR__) . '/app/Controllers/RecurrenceController.php';
require dirname(__DIR__) . '/app/Controllers/BudgetController.php';
require dirname(__DIR__) . '/app/Controllers/GoalController.php';
require dirname(__DIR__) . '/app/Controllers/InvestmentController.php';
require dirname(__DIR__) . '/app/Controllers/PatrimonyController.php';
require dirname(__DIR__) . '/app/Controllers/ImportController.php';
require dirname(__DIR__) . '/app/Controllers/BackupController.php';
require dirname(__DIR__) . '/app/Controllers/ReportController.php';
Migrator::run(Database::connection());

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$routes = require dirname(__DIR__) . '/routes/web.php';
$handler = $routes[$method][$path] ?? null;
if (!$handler) { http_response_code(404); render('errors/404', ['title' => 'Página não encontrada']); exit; }
if (is_callable($handler)) { $handler(); exit; }
[$class, $action] = $handler;
(new $class())->$action();
