<?php
declare(strict_types=1);


namespace App\Models;


use App\Database;


/** Data access for the discussions table. */
final class Discussion
{
    public function all(): array
    {
        return Database::pdo()->query(
            'SELECT d.id, d.title, d.created_at, u.username AS author
               FROM discussions d
               JOIN users u ON u.id = d.user_id
           ORDER BY d.created_at DESC, d.id DESC'
        )->fetchAll();
    }


    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT d.id, d.title, d.body, d.created_at, u.username AS author
               FROM discussions d
               JOIN users u ON u.id = d.user_id
              WHERE d.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}