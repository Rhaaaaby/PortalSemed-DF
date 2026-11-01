<?php

namespace App\Security;

use App\Models\Users;

class Auth
{
    public static function currentUser(): ?array
    {
        $now = time();
        $lastActivity = $_SESSION['last_activity'] ?? null;
        $sessionStarted = $_SESSION['session_started'] ?? null;
        if ((is_int($lastActivity) && $now - $lastActivity > 1800) ||
            (is_int($sessionStarted) && $now - $sessionStarted > 28800)) {
            self::clearSession();
            return null;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!is_int($userId) && !(is_string($userId) && ctype_digit($userId))) {
            return null;
        }

        $user = (new Users())->buscarPorId((int) $userId);
        if (!$user || !in_array($user['role'], ['admin', 'funcionario'], true) || !self::isActive($user['status'])) {
            self::clearSession();
            return null;
        }

        $_SESSION['last_activity'] = $now;
        return $user;
    }

    public static function isActive(mixed $status): bool
    {
        return in_array($status, [true, 1, '1', 't', 'true'], true);
    }

    public static function clearSession(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}
