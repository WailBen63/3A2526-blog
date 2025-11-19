<?php
namespace App\Core;

class SessionManager {
    private static ?self $instance = null;

    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set(string $key, mixed $value): void { $_SESSION[$key] = $value; }
    public function get(string $key, mixed $default = null): mixed { return $_SESSION[$key] ?? $default; }
    public function has(string $key): bool { return isset($_SESSION[$key]); }
    public function remove(string $key): void { unset($_SESSION[$key]); }

    public function destroy(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        self::$instance = null; // Important : force la recréation
        self::getInstance(); // Redémarre une session "propre"
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
