<?php
declare(strict_types=1);

// models/BranchModel.php

final class BranchModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query('SELECT branch_id, branch_name FROM branches ORDER BY branch_name')->fetchAll();
    }
}
