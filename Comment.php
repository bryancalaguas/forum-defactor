<?php
declare(strict_types=1);


namespace App\Models;


use App\Database;


/** Data access for the comments table. */
final class Comment
{
    /** All comments of one discussion, with the author's name. */
    public function forDiscussion(int $discussionId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.id, c.parent_id, c.body, c.created_at, u.username AS author, u.avatar
               FROM comments c
               JOIN users u ON u.id = c.user_id
              WHERE c.discussion_id = :discussion_id
           ORDER BY c.created_at, c.id'
        );
        $stmt->execute(['discussion_id' => $discussionId]);
        return $stmt->fetchAll();
    }


    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, discussion_id, parent_id FROM comments WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }


    /**
     * Displayed depth of a comment (top-level = 0), found by walking up parent_id.
     * Returns null if a cycle is found or the walk exceeds $limit steps.
     */
    public function depthOf(int $commentId, int $limit = 100): ?int
    {
        $stmt  = Database::pdo()->prepare('SELECT parent_id FROM comments WHERE id = :id');
        $seen  = [];
        $depth = 0;
        $current = $commentId;


        while (true) {
            if (isset($seen[$current]) || $depth > $limit) {
                return null; // cycle or runaway chain
            }
            $seen[$current] = true;
            $stmt->execute(['id' => $current]);
            $parent = $stmt->fetchColumn();
            if ($parent === false) {
                return null;        // comment does not exist
            }
            if ($parent === null) {
                return $depth;      // reached a top-level comment
            }
            $current = (int) $parent;
            $depth++;
        }
    }


    public function create(int $discussionId, ?int $parentId, int $userId, string $body): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO comments (discussion_id, parent_id, user_id, body)
             VALUES (:discussion_id, :parent_id, :user_id, :body)'
        );
        $stmt->execute([
            'discussion_id' => $discussionId,
            'parent_id'     => $parentId,
            'user_id'       => $userId,
            'body'          => $body,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }
}