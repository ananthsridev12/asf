<?php
declare(strict_types=1);

// controllers/UserController.php

final class UserController
{
    private PDO $db;
    private UserModel $users;
    private ActivityLogModel $logs;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->users = new UserModel($db);
        $this->logs = new ActivityLogModel($db);
    }

    public function list(): void
    {
        $items = $this->users->listAll();
        include __DIR__ . '/../views/admin/user_list.php';
    }

    public function showCreate(): void
    {
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $roles = $this->db->query('SELECT role_id, role_name FROM roles ORDER BY role_name')->fetchAll();

        include __DIR__ . '/../views/admin/user_add.php';
    }

    public function create(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=admin-user-add');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 0);
        $personId = trim($_POST['person_id'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($username === '' || $email === '' || $password === '' || $roleId <= 0) {
            $_SESSION['flash_error'] = 'Username, email, password, and role are required.';
            header('Location: /index.php?route=admin-user-add');
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (person_id, role_id, username, email, password_hash, is_active)
             VALUES (:person_id, :role_id, :username, :email, :password_hash, :is_active)'
        );
        $stmt->execute([
            ':person_id' => $personId !== '' ? (int)$personId : null,
            ':role_id' => $roleId,
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $hash,
            ':is_active' => $isActive,
        ]);

        if (!empty($_SESSION['user']['user_id'])) {
            $this->logs->log((int)$_SESSION['user']['user_id'], 'user_created', null);
        }

        $_SESSION['flash_success'] = 'User created.';
        header('Location: /index.php?route=admin-user-add');
        exit;
    }
}
