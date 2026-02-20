<?php
declare(strict_types=1);

// index.php - simple front controller

session_start();

// Basic error handling (disable display_errors in production)
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Autoload minimal (manual includes for now)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/BranchModel.php';
require_once __DIR__ . '/models/PersonModel.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/ActivityLogModel.php';
require_once __DIR__ . '/services/RelationshipEngine.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/PersonController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/MemberController.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/config/database.php';
    $dsn = "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset={$cfg['charset']}";

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /index.php?route=login');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($userRole !== $role) {
        http_response_code(403);
        echo '403 - Forbidden';
        exit;
    }
}

$route = $_GET['route'] ?? 'login';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$authController = new AuthController(db());
$adminController = new AdminController(db());
$personController = new PersonController(db());
$userController = new UserController(db());
$memberController = new MemberController(db());

switch ($route) {
    case 'login':
        if ($method === 'POST') {
            $authController->login();
        } else {
            $authController->showLogin();
        }
        break;

    case 'logout':
        $authController->logout();
        break;

    case 'admin-dashboard':
        require_role('admin');
        $adminController->dashboard();
        break;

    case 'admin-persons':
        require_role('admin');
        $personController->list();
        break;

    case 'admin-person-add':
        require_role('admin');
        if ($method === 'POST') {
            $personController->create();
        } else {
            $personController->showCreate();
        }
        break;

    case 'admin-person-edit':
        require_role('admin');
        $personController->showEdit();
        break;

    case 'admin-person-update':
        require_role('admin');
        if ($method === 'POST') {
            $personController->update();
        }
        break;

    case 'admin-parent-add':
        require_role('admin');
        if ($method === 'POST') {
            $personController->addParent();
        }
        break;

    case 'admin-marriage-add':
        require_role('admin');
        if ($method === 'POST') {
            $personController->addMarriage();
        }
        break;

    case 'admin-media-upload':
        require_role('admin');
        if ($method === 'POST') {
            $personController->uploadMedia();
        }
        break;

    case 'admin-person-search':
        require_role('admin');
        $personController->search();
        break;

    case 'admin-users':
        require_role('admin');
        $userController->list();
        break;

    case 'admin-user-add':
        require_role('admin');
        if ($method === 'POST') {
            $userController->create();
        } else {
            $userController->showCreate();
        }
        break;

    case 'member-dashboard':
        require_role('member');
        $memberController->dashboard();
        break;

    case 'member-profile':
        require_role('member');
        if ($method === 'POST') {
            $memberController->saveProfile();
        } else {
            $memberController->profile();
        }
        break;

    case 'member-person-add':
        require_role('member');
        if ($method === 'POST') {
            $memberController->createFamilyMember();
        } else {
            $memberController->showAddFamilyMember();
        }
        break;

    case 'member-marriage-add':
        require_role('member');
        if ($method === 'POST') {
            $memberController->createMarriage();
        } else {
            $memberController->showAddMarriage();
        }
        break;

    case 'member-family':
        require_role('member');
        $memberController->familyList();
        break;

    case 'member-tree':
        require_role('member');
        $memberController->tree();
        break;

    case 'member-ancestors':
        require_role('member');
        $memberController->ancestors();
        break;

    case 'member-descendants':
        require_role('member');
        $memberController->descendants();
        break;

    case 'member-relationship':
        require_role('member');
        $memberController->relationship();
        break;

    case 'member-person-search':
        require_role('member');
        $memberController->searchPeople();
        break;

    default:
        http_response_code(404);
        echo '404 - Page not found';
        break;
}
