<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
$pdo = connectToDb('resources.db');
$csrfToken = $_SESSION['csrf_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && currentUser()['role'] === 'teacher') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $type = $_POST['type'] ?? 'book';
        if ($title !== '' && $url !== '') {
            $st = $pdo->prepare('INSERT INTO resources (title, url, type) VALUES (?, ?, ?)');
            $st->execute([$title, $url, $type]);
        }
        header('Location: /resources.php');
        exit;
    }
    if ($action === 'update') {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $type = $_POST['type'] ?? 'book';
        if ($id > 0 && $title !== '' && $url !== '') {
            $st = $pdo->prepare('UPDATE resources SET title = ?, url = ?, type = ? WHERE id = ?');
            $st->execute([$title, $url, $type, $id]);
        }
        header('Location: /resources.php');
        exit;
    }
    if ($action === 'delete') {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM resources WHERE id = ?')->execute([$id]);
        }
        header('Location: /resources.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ресурсы</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mx-auto p-6">
        <h2 class="text-3xl font-bold text-indigo-700 mb-6 text-center">Книги и видео</h2>

        <?php if (isLoggedIn() && currentUser()['role'] === 'teacher'): ?>
        <section class="bg-white rounded-xl shadow p-5 mb-8">
            <h3 class="text-xl font-semibold text-indigo-700 mb-3">Добавить ресурс</h3>
            <form method="POST" class="grid md:grid-cols-3 gap-3 items-end">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input class="border border-indigo-200 rounded p-2" name="title" placeholder="Название" required>
                <input class="border border-indigo-200 rounded p-2" name="url" placeholder="Ссылка" required>
                <select class="border border-indigo-200 rounded p-2" name="type">
                    <option value="book">Книга</option>
                    <option value="video">Видео</option>
                </select>
                <button class="btn-primary">Добавить</button>
            </form>
        </section>
        <?php endif; ?>

        <div class="space-y-6">
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM resources ORDER BY id DESC");
                while ($resource = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div class="bg-white rounded-lg shadow-lg p-4">';
                    echo '  <div class="flex items-start justify-between gap-3">';
                    echo '    <div>';
                    echo '      <h3 class="text-xl font-semibold text-indigo-600">' . htmlspecialchars($resource['title']) . '</h3>';
                    echo '      <p class="text-gray-600">Тип: ' . ($resource['type'] == 'book' ? 'Книга' : 'Видео') . '</p>';
                    echo '      <a href="' . htmlspecialchars($resource['url']) . '" target="_blank" class="text-indigo-600 hover:underline">Перейти</a>';
                    echo '    </div>';
                    if (isLoggedIn() && currentUser()['role'] === 'teacher') {
                        echo '    <div class="flex flex-col items-end gap-2">';
                        echo '      <form method="POST" onsubmit="return confirm(\'Удалить ресурс?\');">';
                        echo '        <input type="hidden" name="action" value="delete">';
                        echo '        <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
                        echo '        <input type="hidden" name="id" value="' . (int)$resource['id'] . '">';
                        echo '        <button class="text-red-600 hover:underline">Удалить</button>';
                        echo '      </form>';
                        echo '    </div>';
                    }
                    echo '  </div>';
                    if (isLoggedIn() && currentUser()['role'] === 'teacher') {
                        echo '  <details class="mt-3">';
                        echo '    <summary class="cursor-pointer text-sm text-indigo-600">Редактировать</summary>';
                        echo '    <form method="POST" class="mt-2 grid md:grid-cols-3 gap-3 items-end">';
                        echo '      <input type="hidden" name="action" value="update">';
                        echo '      <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
                        echo '      <input type="hidden" name="id" value="' . (int)$resource['id'] . '">';
                        echo '      <input class="border border-indigo-200 rounded p-2" name="title" value="' . htmlspecialchars($resource['title']) . '" required>';
                        echo '      <input class="border border-indigo-200 rounded p-2" name="url" value="' . htmlspecialchars($resource['url']) . '" required>';
                        echo '      <select class="border border-indigo-200 rounded p-2" name="type">';
                        $selBook = $resource['type'] === 'book' ? 'selected' : '';
                        $selVideo = $resource['type'] === 'video' ? 'selected' : '';
                        echo '        <option value="book" ' . $selBook . '>Книга</option>';
                        echo '        <option value="video" ' . $selVideo . '>Видео</option>';
                        echo '      </select>';
                        echo '      <button class="btn-primary">Сохранить</button>';
                        echo '    </form>';
                        echo '  </details>';
                    }
                    echo '</div>';
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