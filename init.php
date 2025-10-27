<?php
include __DIR__ . '/../includes/db_connect.php';

initDatabases();

try {
    $lecturesPdo = connectToDb('lectures.db');
    $cnt = (int)$lecturesPdo->query('SELECT COUNT(*) FROM lectures')->fetchColumn();
    if ($cnt === 0) {
        $stmt = $lecturesPdo->prepare('INSERT INTO lectures (title, content, file_path, video_url) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Введение в 3D‑моделирование', 'Основы полигонального моделирования, типы примитивов, навигация в 3D‑сцене.', null, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $stmt->execute(['Материалы и освещение', 'PBR‑материалы, источники света, HDRI‑окружение.', null, null]);
    }

    $resourcesPdo = connectToDb('resources.db');
    $rcnt = (int)$resourcesPdo->query('SELECT COUNT(*) FROM resources')->fetchColumn();
    if ($rcnt === 0) {
        $stmt = $resourcesPdo->prepare('INSERT INTO resources (title, url, type) VALUES (?, ?, ?)');
        $stmt->execute(['Blender Manual', 'https://docs.blender.org/manual/en/latest/', 'book']);
        $stmt->execute(['CG Geek — Tutorials', 'https://www.youtube.com/@CGGeek', 'video']);
    }

    $modelsPdo = connectToDb('models.db');
    $mcnt = (int)$modelsPdo->query('SELECT COUNT(*) FROM models')->fetchColumn();
    if ($mcnt === 0) {
        $stmt = $modelsPdo->prepare('INSERT INTO models (name, description, file_path, image_path, uploader_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(['Куб', 'Простая модель куба для начинающих.', 'cube.obj', 'cube.jpg', 1]);
        $stmt->execute(['Сфера', 'Модель сферы с текстурой.', 'sphere.obj', 'sphere.jpg', 1]);
    }

    $documentsPdo = connectToDb('documents.db');
    $dcnt = (int)$documentsPdo->query('SELECT COUNT(*) FROM documents')->fetchColumn();
    if ($dcnt === 0) {
        $stmt = $documentsPdo->prepare('INSERT INTO documents (title, file_path) VALUES (?, ?)');
        $stmt->execute(['Руководство по Blender', 'blender_guide.pdf']);
        $stmt->execute(['Техническое задание', 'tech_spec.pdf']);
    }

    $testsPdo = connectToDb('tests.db');
    $tcnt = (int)$testsPdo->query('SELECT COUNT(*) FROM tests')->fetchColumn();
    if ($tcnt === 0) {
        $stmt = $testsPdo->prepare('INSERT INTO tests (question, options, correct_answer) VALUES (?, ?, ?)');
        $stmt->execute(['Какой инструмент используется для выделения в Blender?', '["Lasso", "Box", "Circle"]', 1]);
        $stmt->execute(['Что такое UV‑развёртка?', '["Процесс текстурирования", "Тип освещения", "Метод анимации"]', 0]);
    }

    $pollsPdo = connectToDb('polls.db');
    $pcnt = (int)$pollsPdo->query('SELECT COUNT(*) FROM polls')->fetchColumn();
    if ($pcnt === 0) {
        $stmt = $pollsPdo->prepare('INSERT INTO polls (question, options) VALUES (?, ?)');
        $stmt->execute(['Какой 3D‑редактор предпочитаете?', '["Blender", "Maya", "3ds Max", "Другой"]']);
        $stmt->execute(['Насколько полезны лекции?', '["Очень полезны", "Полезны", "Не очень", "Бесполезны"]']);
    }
} catch (Throwable $e) {
}

echo "Инициализация завершена";
?>

