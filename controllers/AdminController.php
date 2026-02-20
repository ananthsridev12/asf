<?php
declare(strict_types=1);

// controllers/AdminController.php

final class AdminController
{
    private PDO $db;
    private PersonModel $persons;
    private UserModel $users;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->persons = new PersonModel($db);
        $this->users = new UserModel($db);
    }

    public function dashboard(): void
    {
        $totalPersons = $this->persons->countAll();
        $totalUsers = $this->users->countAll();
        $totalBranches = (int)$this->db->query('SELECT COUNT(*) AS c FROM branches')->fetch()['c'];

        include __DIR__ . '/../views/admin/dashboard.php';
    }
}
