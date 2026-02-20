<?php
declare(strict_types=1);

// models/PersonModel.php

final class PersonModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM persons WHERE person_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countAll(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) AS c FROM persons')->fetch()['c'];
    }

    public function listPaginated(int $page, int $perPage): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $stmt = $this->db->prepare('SELECT * FROM persons ORDER BY person_id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
