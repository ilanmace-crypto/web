<?php
function connectToDb($dbName) {
    $dbPath = __DIR__ . '/../db/' . $dbName;
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Ошибка подключения к БД $dbName: " . $e->getMessage());
    }
}

function initDatabases() {
    $pdo = connectToDb('models.db');
    $pdo->exec("CREATE TABLE IF NOT EXISTS models (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        file_path TEXT NOT NULL,
        image_path TEXT NOT NULL
    )");

    $pdo = connectToDb('lectures.db');
    $pdo->exec("CREATE TABLE IF NOT EXISTS lectures (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT,
        file_path TEXT,
        video_url TEXT
    )");
    $pdo = connectToDb('labs.db');
    $pdo->exec("CREATE TABLE IF NOT EXISTS labs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        file_path TEXT NOT NULL
    )");

    $pdo = connectToDb('tests.db');
    $pdo->exec("CREATE TABLE IF NOT EXISTS tests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        question TEXT NOT NULL,
        options TEXT NOT NULL, 
        correct_answer INTEGER NOT NULL
    )");

    $pdo = connectToDb('resources.db');
    $pdo->exec("CREATE TABLE IF NOT EXISTS resources (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        url TEXT NOT NULL,
        type TEXT NOT NULL 
    )");

    $pdo = connectToDb('polls.db');
    $pdo->exec("CREATE TABLE IF NOT EXISTS polls (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        question TEXT NOT NULL,
        options TEXT NOT NULL 
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS votes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        poll_id INTEGER,
        option_index INTEGER,
        user_id INTEGER,
        FOREIGN KEY (poll_id) REFERENCES polls(id)
    )");

    $pdo = connectToDb('comments.db');
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        model_id INTEGER,
        lecture_id INTEGER,
        user_id INTEGER,
        comment TEXT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo = connectToDb('documents.db');
    $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        file_path TEXT NOT NULL
    )");

    $pdo = connectToDb('users.db');
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'student' 
    )");
    try {
        $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
        $hasRole = false;
        foreach ($cols as $col) { if ($col['name'] === 'role') { $hasRole = true; break; } }
        if (!$hasRole) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'student'");
        }
    } catch (Throwable $e) { /* ignore */ }

    $pdo = connectToDb('polls.db');
    $pdo->exec("CREATE TABLE IF NOT EXISTS ratings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        model_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        stars INTEGER NOT NULL CHECK(stars BETWEEN 1 AND 5),
        UNIQUE(model_id, user_id)
    )");
}

if (!function_exists('bootstrapInitDatabases')) {
    function bootstrapInitDatabases() {
        static $done = false;
        if ($done) return;
        $done = true;
        initDatabases();
    }
    bootstrapInitDatabases();
}
