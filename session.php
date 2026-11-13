<?php
declare(strict_types=1);


namespace App;


/** Session, login state, CSRF tokens and flash messages. */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }


        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';


        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,     // JavaScript cannot read the cookie
            'samesite' => 'Lax',    // not sent on cross-site POSTs
            'secure'   => $https,   // Secure flag whenever the site runs on HTTPS
        ]);
        session_start();
    }


    // ---------- Authentication ----------


    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }


    public static function username(): ?string
    {
        return $_SESSION['username'] ?? null;
    }


    public static function login(int $userId, string $username): void
    {
        session_regenerate_id(true);             // prevent session fixation
        $_SESSION['user_id']  = $userId;
        $_SESSION['username'] = $username;
        unset($_SESSION['csrf_token']);           // new token for the new session
    }


    public static function logout(): void
    {
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'],
        ]);
        session_destroy();
    }


    // ---------- CSRF ----------


    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }


    public static function checkCsrf(mixed $submitted): bool
    {
        return is_string($submitted)
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $submitted);
    }


    // ---------- Flash messages ----------


    public static function flash(string $message): void
    {
        $_SESSION['flash'] = $message;
    }


    public static function takeFlash(): ?string
    {
        $m = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $m;
    }
}