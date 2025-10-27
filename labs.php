<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

function ensureDir($p){ if(!is_dir($p)) @mkdir($p,0775,true); }

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrfToken = $_SESSION['csrf_token'];
define('MAX_PDF_SIZE', 100 * 1024 * 1024);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_lab') {
    if (isLoggedIn() && currentUser()['role'] === 'teacher') {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $title = trim($_POST['lab_title'] ?? '');
        $desc  = trim($_POST['lab_desc'] ?? '');
        if ($title !== '' && !empty($_FILES['lab_file']) && $_FILES['lab_file']['error'] === UPLOAD_ERR_OK) {
            $docsDir = __DIR__ . '/../documents';
            ensureDir($docsDir);
            $ext = strtolower(pathinfo($_FILES['lab_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') { http_response_code(415); exit; }
            if (($_FILES['lab_file']['size'] ?? 0) > MAX_PDF_SIZE) { http_response_code(413); exit; }
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($_FILES['lab_file']['tmp_name']);
            if ($mime !== 'application/pdf') { http_response_code(415); exit; }
            $new = 'lab_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $dest = rtrim($docsDir,'/') . '/' . $new;
            if (move_uploaded_file($_FILES['lab_file']['tmp_name'], $dest)) {
                $pdo = connectToDb('labs.db');
                $stmt = $pdo->prepare('INSERT INTO labs (title, description, file_path) VALUES (?, ?, ?)');
                $stmt->execute([$title, $desc, $new]);
            }
        }
    }
    header('Location: /labs.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_lab') {
    if (isLoggedIn() && currentUser()['role'] === 'teacher') {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo = connectToDb('labs.db');
            $st = $pdo->prepare('SELECT file_path FROM labs WHERE id = ?');
            $st->execute([$id]);
            if ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($r['file_path'])) @unlink(__DIR__ . '/../documents/' . $r['file_path']);
            }
            $pdo->prepare('DELETE FROM labs WHERE id = ?')->execute([$id]);
        }
    }
    header('Location: /root/labs.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_lab') {
    if (isLoggedIn() && currentUser()['role'] === 'teacher') {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['lab_title'] ?? '');
        $desc  = trim($_POST['lab_desc'] ?? '');
        if ($id > 0 && $title !== '') {
            $pdo = connectToDb('labs.db');
            $st = $pdo->prepare('SELECT file_path FROM labs WHERE id = ?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
            $filePath = $row['file_path'] ?? '';

            if (!empty($_FILES['lab_file']) && $_FILES['lab_file']['error'] === UPLOAD_ERR_OK) {
                $docsDir = __DIR__ . '/../documents';
                ensureDir($docsDir);
                $ext = strtolower(pathinfo($_FILES['lab_file']['name'], PATHINFO_EXTENSION));
                if ($ext !== 'pdf') { http_response_code(415); exit; }
                if (($_FILES['lab_file']['size'] ?? 0) > MAX_PDF_SIZE) { http_response_code(413); exit; }
                $fi = new finfo(FILEINFO_MIME_TYPE);
                $mime = $fi->file($_FILES['lab_file']['tmp_name']);
                if ($mime !== 'application/pdf') { http_response_code(415); exit; }
                $new = 'lab_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                $dest = rtrim($docsDir,'/') . '/' . $new;
                if (move_uploaded_file($_FILES['lab_file']['tmp_name'], $dest)) {
                    if (!empty($filePath)) @unlink($docsDir . '/' . $filePath);
                    $filePath = $new;
                }
            }

            $upd = $pdo->prepare('UPDATE labs SET title = ?, description = ?, file_path = ? WHERE id = ?');
            $upd->execute([$title, $desc, $filePath, $id]);
        }
    }
    header('Location: /root/labs.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторные</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    </style>
</head>
<body class="font-sans">
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container mx-auto p-6">
    <h2 class="text-3xl font-bold text-indigo-700 mb-6 text-center">Лабораторные работы</h2>
    <?php if (isLoggedIn() && in_array(currentUser()['role'], ['teacher','admin'])): ?>
        <div class="card p-4 mb-6">
            <h3 class="text-lg font-semibold text-indigo-700 mb-3">Добавить лабораторную</h3>
            <form method="POST" enctype="multipart/form-data" class="grid md:grid-cols-4 gap-2 items-end">
                <input type="hidden" name="action" value="upload_lab">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input class="border border-indigo-200 rounded p-2 md:col-span-1" name="lab_title" placeholder="Название" required>
                <input class="border border-indigo-200 rounded p-2 md:col-span-2" name="lab_desc" placeholder="Краткое описание">
                <input type="file" name="lab_file" accept=".pdf" class="md:col-span-1" required>
                <button class="btn-primary md:col-span-1">Загрузить</button>
            </form>
        </div>
    <?php endif; ?>
    <div class="space-y-4">
        <?php
        $pdo = connectToDb('labs.db');
        try {
            $stmt = $pdo->query("SELECT * FROM labs ORDER BY id DESC");
            while ($lab = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<div class="bg-white rounded-lg shadow p-4 flex items-center justify-between">';
                echo '  <div>';
                echo '    <h3 class="text-lg font-semibold text-indigo-600">' . htmlspecialchars($lab['title']) . '</h3>';
                echo '    <p class="text-indigo-900/70">' . htmlspecialchars($lab['description']) . '</p>';
                echo '  </div>';
                echo '  <div class="flex items-center gap-3">';
                echo '    <a href="/documents/' . htmlspecialchars($lab['file_path']) . '" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Скачать</a>';
                if (isLoggedIn() && currentUser()['role'] === 'teacher') {
                    echo '    <form method="POST" onsubmit="return confirm(\'Удалить лабу?\');">';
                    echo '      <input type="hidden" name="action" value="delete_lab">';
                    echo '      <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
                    echo '      <input type="hidden" name="id" value="' . (int)$lab['id'] . '">';
                    echo '      <button class="text-red-600 hover:underline">Удалить</button>';
                    echo '    </form>';
                }
                echo '  </div>';
                echo '</div>';

                if (isLoggedIn() && currentUser()['role'] === 'teacher') {
                    echo '<details class="bg-white rounded-lg shadow p-4 mt-2">';
                    echo '  <summary class="cursor-pointer text-indigo-600 text-sm">Редактировать</summary>';
                    echo '  <form method="POST" enctype="multipart/form-data" class="mt-2 grid md:grid-cols-4 gap-2 items-end">';
                    echo '    <input type="hidden" name="action" value="update_lab">';
                    echo '    <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
                    echo '    <input type="hidden" name="id" value="' . (int)$lab['id'] . '">';
                    echo '    <input class="border border-indigo-200 rounded p-2" name="lab_title" value="' . htmlspecialchars($lab['title']) . '" required>';
                    echo '    <input class="border border-indigo-200 rounded p-2" name="lab_desc" value="' . htmlspecialchars($lab['description']) . '">';
                    echo '    <input type="file" name="lab_file" accept=".pdf">';
                    echo '    <button class="btn-primary">Сохранить</button>';
                    echo '  </form>';
                    echo '</details>';
                }
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
