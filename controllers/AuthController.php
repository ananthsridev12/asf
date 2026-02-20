<?php
declare(strict_types=1);

// controllers/AuthController.php

final class AuthController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function showLogin(): void
    {
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        include __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        $usernameOrEmail = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $token = $_POST['csrf_token'] ?? '';

        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=login');
            exit;
        }

        if ($usernameOrEmail === '' || $password === '') {
            $_SESSION['flash_error'] = 'Username/email and password are required.';
            header('Location: /index.php?route=login');
            exit;
        }

        $stmt = $this->db->prepare(
            'SELECT u.user_id, u.person_id, u.username, u.email, u.password_hash, u.is_active, r.role_name
             FROM users u
             INNER JOIN roles r ON r.role_id = u.role_id
             WHERE u.username = :u1 OR u.email = :u2
             LIMIT 1'
        );
        $stmt->execute([':u1' => $usernameOrEmail, ':u2' => $usernameOrEmail]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Invalid credentials.';
            header('Location: /index.php?route=login');
            exit;
        }

        if ((int)$user['is_active'] !== 1) {
            $_SESSION['flash_error'] = 'Account not active.';
            header('Location: /index.php?route=login');
            exit;
        }

        $update = $this->db->prepare('UPDATE users SET last_login = NOW() WHERE user_id = :id');
        $update->execute([':id' => $user['user_id']]);

        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'person_id' => $user['person_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role_name'],
        ];

        if ($user['role_name'] === 'admin') {
            header('Location: /index.php?route=admin-dashboard');
        } else {
            header('Location: /index.php?route=member-dashboard');
        }
        exit;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        header('Location: /index.php?route=login');
        exit;
    }
}


