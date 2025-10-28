<?php
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '1' : '0');
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getUsersPdo() {
    require_once __DIR__ . '/db_connect.php';
    $pdo = connectToDb('users.db');
    try {
        $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
        $hasRole = false;
        foreach ($cols as $col) { if (($col['name'] ?? '') === 'role') { $hasRole = true; break; } }
        if (!$hasRole) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'student'");
        }
    } catch (Throwable $e) {
    }
    return $pdo;
}

function isLoggedIn() {
    return !empty($_SESSION['user']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function requireRole($roles) {
    if (!is_array($roles)) $roles = [$roles];
    if (!isLoggedIn() || !in_array($_SESSION['user']['role'], $roles, true)) {
        header('Location: /login.php');
        exit;
    }
}

function login($username, $password) {
    $pdo = getUsersPdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        if (session_status() === PHP_SESSION_ACTIVE) { session_regenerate_id(true); }
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        return true;
    }
    return false;
}

function register($username, $password, $role = 'student') {
    $pdo = getUsersPdo();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
    try {
        $stmt->execute([$username, $hash, $role]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
?>

