<?php
declare(strict_types=1);

// models/UserModel.php

final class UserModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function countAll(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
    }

    public function listAll(): array
    {
        $stmt = $this->db->query(
            'SELECT u.user_id, u.username, u.email, u.is_active, r.role_name, u.person_id
             FROM users u
             INNER JOIN roles r ON r.role_id = u.role_id
             ORDER BY u.user_id DESC'
        );
        return $stmt->fetchAll();
    }
}
