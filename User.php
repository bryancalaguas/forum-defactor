<?php
declare(strict_types=1);


namespace App\Models;


use App\Database;


/** Data access for the users table. All SQL uses bound parameters. */
final class User
{
    public function findByUsername(string $username): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, username, email, password_hash FROM users WHERE username = :username'
        );
        $stmt->execute(['username' => $username]);
        return $stmt->fetch() ?: null;
    }


    public function usernameExists(string $username): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM users WHERE username = :u');
        $stmt->execute(['u' => $username]);
        return (bool) $stmt->fetchColumn();
    }


    public function emailExists(string $email): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM users WHERE email = :e');
        $stmt->execute(['e' => $email]);
        return (bool) $stmt->fetchColumn();
    }


    public function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hash)'
        );
        $stmt->execute(['username' => $username, 'email' => $email, 'hash' => $passwordHash]);
        return (int) Database::pdo()->lastInsertId();
    }
}