<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
$pdo = connectToDb('documents.db');
$csrfToken = $_SESSION['csrf_token'] ?? '';

function ensureDirDocs($p){ if(!is_dir($p)) @mkdir($p,0775,true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && in_array(currentUser()['role'], ['teacher','admin'])) {
    $action = $_POST['action'] ?? '';
    if ($action === 'upload_document') {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $title = trim($_POST['doc_title'] ?? '');
        if ($title !== '' && !empty($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
            $docsDir = __DIR__ . '/../documents';
            ensureDirDocs($docsDir);
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $tmp = $_FILES['doc_file']['tmp_name'];
                $ok = is_uploaded_file($tmp);
                if (function_exists('finfo_open')) {
                    $f = finfo_open(FILEINFO_MIME_TYPE);
                    if ($f) {
                        $mime = @finfo_file($f, $tmp) ?: '';
                        finfo_close($f);
                        $allowedMime = ['application/pdf', 'application/x-pdf'];
                        if (!in_array($mime, $allowedMime, true)) { $ok = false; }
                    }
                }
                if ($ok) {
                    $newName = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
                    $dest = rtrim($docsDir, '/') . '/' . $newName;
                    if (move_uploaded_file($tmp, $dest)) {
                        $st = $pdo->prepare('INSERT INTO documents (title, file_path) VALUES (?, ?)');
                        $st->execute([$title, $newName]);
                    }
                }
            }
        }
        header('Location: /documents.php');
        exit;
    }
    if ($action === 'delete_document') {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $st = $pdo->prepare('SELECT file_path FROM documents WHERE id = ?');
            $st->execute([$id]);
            if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['file_path'])) @unlink(__DIR__ . '/../documents/' . $row['file_path']);
            }
            $pdo->prepare('DELETE FROM documents WHERE id = ?')->execute([$id]);
        }
        header('Location: /documents.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Документы</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    </style>
</head>
<body class="font-sans">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mx-auto p-6">
        <h2 class="text-3xl font-bold text-indigo-700 mb-6 text-center">Документы</h2>

        <?php if (isLoggedIn() && in_array(currentUser()['role'], ['teacher','admin'])): ?>
        <section class="bg-white rounded-xl shadow p-5 mb-6">
            <h3 class="text-lg font-semibold text-indigo-700 mb-3">Загрузить документ</h3>
            <form method="POST" enctype="multipart/form-data" class="grid md:grid-cols-3 gap-3 items-end">
                <input type="hidden" name="action" value="upload_document">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input class="border border-indigo-200 rounded p-2" name="doc_title" placeholder="Название документа" required>
                <input type="file" name="doc_file" accept=".pdf" required>
                <button class="btn-primary">Загрузить</button>
            </form>
        </section>
        <?php endif; ?>

        <div class="space-y-4">
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM documents ORDER BY id DESC");
                while ($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div class="bg-white rounded-lg shadow p-4 flex items-center justify-between">';
                    echo '  <div>';
                    echo '    <h3 class="text-lg font-semibold text-indigo-600">' . htmlspecialchars($doc['title']) . '</h3>';
                    echo '    <p class="text-indigo-900/70">PDF</p>';
                    echo '  </div>';
                    echo '  <div class="flex items-center gap-3">';
                    echo '    <a href="/documents/' . htmlspecialchars($doc['file_path']) . '" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Скачать</a>';
                    if (isLoggedIn() && in_array(currentUser()['role'], ['teacher','admin'])) {
                        echo '    <form method="POST" onsubmit="return confirm(\'Удалить документ?\');">';
                        echo '      <input type="hidden" name="action" value="delete_document">';
                        echo '      <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
                        echo '      <input type="hidden" name="id" value="' . (int)$doc['id'] . '">';
                        echo '      <button class="text-red-600 hover:underline">Удалить</button>';
                        echo '    </form>';
                    }
                    echo '  </div>';
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

