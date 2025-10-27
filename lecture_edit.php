<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
requireRole(['teacher','admin']);

$pdo = connectToDb('lectures.db');
$notice = '';
$error = '';
$csrfToken = $_SESSION['csrf_token'] ?? '';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /lectures.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM lectures WHERE id = ?');
$stmt->execute([$id]);
$lecture = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lecture) { header('Location: /lectures.php'); exit; }

function ensureDir($p){ if(!is_dir($p)) @mkdir($p,0775,true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $video = trim($_POST['video_url'] ?? '');
    $filePath = $lecture['file_path'] ?? '';

    if (!empty($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $docsDir = __DIR__ . '/../documents';
        ensureDir($docsDir);
        $ext = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $error = 'Только PDF файлы поддерживаются';
        } else {
            if (($_FILES['pdf']['size'] ?? 0) > 100 * 1024 * 1024) {
                $error = 'PDF превышает 100 МБ';
            }
            if (!$error && function_exists('finfo_open')) {
                $f = finfo_open(FILEINFO_MIME_TYPE);
                if ($f) { $mime = @finfo_file($f, $_FILES['pdf']['tmp_name']) ?: ''; finfo_close($f);
                    $allowedMime = ['application/pdf', 'application/x-pdf'];
                    if (!in_array($mime, $allowedMime, true)) { $error = 'Неверный MIME тип PDF'; }
                }
            }
            $newName = 'lecture_' . time() . '_' . bin2hex(random_bytes(3)) . '.pdf';
            $dest = rtrim($docsDir,'/') . '/' . $newName;
            if (!$error && (!is_uploaded_file($_FILES['pdf']['tmp_name']) || !move_uploaded_file($_FILES['pdf']['tmp_name'], $dest))) {
                $error = 'Не удалось сохранить PDF';
            } else {
                // Удаляем старый при наличии
                if (!empty($filePath)) @unlink($docsDir . '/' . $filePath);
                $filePath = $newName;
            }
        }
    }

    if (!$error && $title !== '') {
        $st = $pdo->prepare('UPDATE lectures SET title = ?, content = ?, file_path = ?, video_url = ? WHERE id = ?');
        $st->execute([$title, $content, $filePath, $video, $id]);
        $notice = 'Изменения сохранены';
        $lecture['title'] = $title; $lecture['content'] = $content; $lecture['file_path'] = $filePath; $lecture['video_url'] = $video;
    } elseif (!$error) {
        $error = 'Введите название лекции';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Редактировать лекцию</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans">
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container mx-auto p-6 max-w-2xl">
  <h2 class="text-3xl font-bold text-indigo-700 mb-6">Редактировать лекцию</h2>
  <?php if ($notice): ?><div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded mb-4"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow p-6 space-y-4">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <div>
      <label class="block mb-1 font-medium text-indigo-700">Название</label>
      <input class="w-full border border-indigo-200 rounded p-3" name="title" value="<?php echo htmlspecialchars($lecture['title']); ?>" required>
    </div>
    <div>
      <label class="block mb-1 font-medium text-indigo-700">Содержание</label>
      <textarea class="w-full border border-indigo-200 rounded p-3" name="content" rows="4"><?php echo htmlspecialchars($lecture['content']); ?></textarea>
    </div>
    <div>
      <label class="block mb-1 font-medium text-indigo-700">Ссылка на видео (YouTube)</label>
      <input class="w-full border border-indigo-200 rounded p-3" name="video_url" value="<?php echo htmlspecialchars($lecture['video_url']); ?>">
    </div>
    <div>
      <label class="block mb-1 font-medium text-indigo-700">Заменить PDF (опционально)</label>
      <?php if (!empty($lecture['file_path'])): ?>
        <div class="text-sm mb-1">Текущий файл: <a class="text-indigo-600 hover:underline" href="/documents/<?php echo htmlspecialchars($lecture['file_path']); ?>" target="_blank">скачать</a></div>
      <?php endif; ?>
      <input type="file" name="pdf" accept=".pdf">
    </div>
    <div class="flex gap-2">
      <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Сохранить</button>
      <a href="/lectures.php" class="px-4 py-2 rounded border border-indigo-200 text-indigo-700">Назад</a>
    </div>
  </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
