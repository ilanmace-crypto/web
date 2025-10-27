<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_lecture') {
    if (isLoggedIn() && in_array(currentUser()['role'], ['teacher','admin'])) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo = connectToDb('lectures.db');
            $stmt = $pdo->prepare('SELECT file_path FROM lectures WHERE id = ?');
            $stmt->execute([$id]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['file_path'])) {
                    @unlink(__DIR__ . '/../documents/' . $row['file_path']);
                }
            }
            $pdo->prepare('DELETE FROM lectures WHERE id = ?')->execute([$id]);
        }
    }
    header('Location: /lectures.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лекции</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
</style>
</head>
<body class="font-sans">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mx-auto p-6">
        <h2 class="text-3xl font-bold text-indigo-700 mb-6 text-center">Лекции</h2>
        <div class="space-y-4">
            <?php
            $pdo = connectToDb('lectures.db');
            try {
                $stmt = $pdo->query("SELECT * FROM lectures ORDER BY id DESC");
                while ($lecture = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div class="bg-white rounded-lg shadow p-4">';
                    echo '<button class="w-full flex justify-between items-center text-left" onclick="this.nextElementSibling.classList.toggle(\'hidden\')">';
                    echo '<span class="text-xl font-semibold text-indigo-600">' . htmlspecialchars($lecture['title']) . '</span>';
                    echo '<span class="text-indigo-500">▼</span>';
                    echo '</button>';
                    echo '<div class="mt-3 hidden">';
                    echo '<p class="text-indigo-900/80">' . htmlspecialchars($lecture['content']) . '</p>';
                    if ($lecture['file_path']) {
                        echo '<a href="documents/' . htmlspecialchars($lecture['file_path']) . '" class="text-indigo-600 hover:underline">Скачать PDF</a>';
                    }
                    if ($lecture['video_url']) {
                        echo '<div class="mt-2"><iframe width="100%" height="315" src="' . htmlspecialchars($lecture['video_url']) . '" frameborder="0" allowfullscreen></iframe></div>';
                    }
                    if (isLoggedIn() && in_array(currentUser()['role'], ['teacher','admin'])) {
                        echo '<div class="mt-3 flex items-center gap-3">';
                        echo '<a class="text-indigo-600 hover:underline" href="/lecture_edit.php?id=' . (int)$lecture['id'] . '">Редактировать</a>';
                        echo '<form method="POST" onsubmit="return confirm(\'Удалить лекцию?\');">';
                        echo '<input type="hidden" name="action" value="delete_lecture">';
                        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
                        echo '<input type="hidden" name="id" value="' . (int)$lecture['id'] . '">';
                        echo '<button class="text-red-600 hover:underline">Удалить</button>';
                        echo '</form>';
                        echo '</div>';
                    }
                    echo '</div></div>';
                }
            } catch (PDOException $e) {
                echo "<p class='text-red-500'>Ошибка: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>