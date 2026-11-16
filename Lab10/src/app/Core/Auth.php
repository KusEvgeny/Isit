<?php
// app/Core/Auth.php
declare(strict_types=1);
namespace App\Core;

use App\Models\EmployeeRepository; // <-- ЗАМЕНИТЬ ЗДЕСЬ

final class Auth
{
    public static function issueToken(int $userId): array
    {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (int) config('token_ttl');

        $pdo = Database::connection();
        $pdo->exec('DELETE FROM auth_tokens WHERE expires_at < now()');
        $stmt = $pdo->prepare('INSERT INTO auth_tokens (token_hash, user_id, expires_at) VALUES (:hash, :user_id, to_timestamp(:expires))');
        $stmt->execute(['hash' => hash('sha256', $token), 'user_id' => $userId, 'expires' => $expires]);

        return ['token' => $token, 'expires_at' => date('c', $expires)];
    }

    public static function userByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT user_id FROM auth_tokens WHERE token_hash = :hash AND expires_at > now()');
        $stmt->execute(['hash' => hash('sha256', $token)]);
        $userId = $stmt->fetchColumn();
        // ЗАМЕНИТЬ UserRepository на EmployeeRepository
        return $userId === false ? null : (new EmployeeRepository())->find((int) $userId);
    }

    public static function revoke(string $token): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM auth_tokens WHERE token_hash = :hash');
        $stmt->execute(['hash' => hash('sha256', $token)]);
    }
}