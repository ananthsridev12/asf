<?php
declare(strict_types=1);

// models/ActivityLogModel.php

final class ActivityLogModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function log(int $userId, string $action, ?int $personId = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO activity_logs (user_id, action, affected_person_id)
             VALUES (:user_id, :action, :person_id)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':person_id' => $personId,
        ]);
    }
}
