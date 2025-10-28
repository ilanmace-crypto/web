<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
requireRole(['teacher','admin']);

$pdo = connectToDb('ktp.db');

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ktp_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        type TEXT NOT NULL, 
        file_path TEXT,
        planned_date TEXT,
        is_published INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Throwable $e) { /* ignore */ }

function ensureDir($p){ if(!is_dir($p)) @mkdir($p,0775,true); }

$notice = '';
$error = '';
$csrfToken = $_SESSION['csrf_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_ktp') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'lecture';
    $date = $_POST['planned_date'] ?? '';
    $filePath = '';

    if ($title !== '' && !empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $docsDir = __DIR__ . '/../documents';
        ensureDir($docsDir);
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $size = (int)($_FILES['file']['size'] ?? 0);
        if ($size > 100 * 1024 * 1024) {
            $error = 'Файл превышает 100 МБ';
        }
        $allowedExt = ['pdf','doc','docx','ppt','pptx'];
        $allowedMime = [
            'application/pdf', 'application/x-pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ];
        if (!$error && !in_array($ext, $allowedExt, true)) {
            $error = 'Недопустимый формат файла';
        }
        if (!$error && function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) { $mime = @finfo_file($f, $_FILES['file']['tmp_name']) ?: ''; finfo_close($f);
                if (!in_array($mime, $allowedMime, true)) { $error = 'Неверный MIME тип файла'; }
            }
        }
        $newName = 'ktp_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $dest = rtrim($docsDir,'/') . '/' . $newName;
        if (!$error && move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            $filePath = $newName;
            $stmt = $pdo->prepare('INSERT INTO ktp_items (title, description, type, file_path, planned_date) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$title, $desc, $type, $filePath, $date]);
            $notice = 'Материал добавлен в КТП';
        } else {
            $error = 'Не удалось сохранить файл';
        }
    } elseif ($title === '') {
        $error = 'Введите название';
    } else {
        $error = 'Загрузите файл';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'publish') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
    $id = (int)($_POST['id'] ?? 0);
    $type = $_POST['item_type'] ?? '';
    
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM ktp_items WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            if ($item['type'] === 'lecture') {
                $lecturesPdo = connectToDb('lectures.db');
                $st = $lecturesPdo->prepare('INSERT INTO lectures (title, content, file_path) VALUES (?, ?, ?)');
                $st->execute([$item['title'], $item['description'], $item['file_path']]);
            } elseif ($item['type'] === 'lab') {
                $labsPdo = connectToDb('labs.db');
                $st = $labsPdo->prepare('INSERT INTO labs (title, description, file_path) VALUES (?, ?, ?)');
                $st->execute([$item['title'], $item['description'], $item['file_path']]);
            }
            
            $pdo->prepare('UPDATE ktp_items SET is_published = 1 WHERE id = ?')->execute([$id]);
            $notice = 'Материал опубликован студентам!';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_ktp') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT file_path FROM ktp_items WHERE id = ?');
        $stmt->execute([$id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['file_path'])) @unlink(__DIR__ . '/../documents/' . $row['file_path']);
        }
        $pdo->prepare('DELETE FROM ktp_items WHERE id = ?')->execute([$id]);
        $notice = 'Удалено из КТП';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>КТП - Календарно-тематический план</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans">
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container mx-auto p-6">
  <h2 class="text-3xl font-bold text-indigo-700 mb-6">Календарно-тематический план (КТП)</h2>
  
  <?php if ($notice): ?><div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded mb-4"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="card p-6 mb-6">
    <h3 class="text-xl font-semibold text-indigo-700 mb-4">Добавить материал в КТП</h3>
    <form method="POST" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">
      <input type="hidden" name="action" value="add_ktp">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
      <div class="md:col-span-2">
        <label class="block mb-1 font-medium text-indigo-700">Название</label>
        <input class="w-full border border-indigo-200 rounded p-3" name="title" required>
      </div>
      <div class="md:col-span-2">
        <label class="block mb-1 font-medium text-indigo-700">Описание</label>
        <textarea class="w-full border border-indigo-200 rounded p-3" name="description" rows="2"></textarea>
      </div>
      <div>
        <label class="block mb-1 font-medium text-indigo-700">Тип материала</label>
        <select class="w-full border border-indigo-200 rounded p-3" name="type" required>
          <option value="lecture">Лекция</option>
          <option value="lab">Лабораторная</option>
          <option value="practice">Практика</option>
        </select>
      </div>
      <div>
        <label class="block mb-1 font-medium text-indigo-700">Планируемая дата</label>
        <input type="date" class="w-full border border-indigo-200 rounded p-3" name="planned_date">
      </div>
      <div class="md:col-span-2">
        <label class="block mb-1 font-medium text-indigo-700">Файл (PDF, DOCX, и т.д.)</label>
        <input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx" required>
      </div>
      <div class="md:col-span-2">
        <button class="btn-primary">Добавить в КТП</button>
      </div>
    </form>
  </div>

  <div class="space-y-4">
    <h3 class="text-2xl font-semibold text-indigo-700">Запланированные материалы</h3>
    <?php
    $stmt = $pdo->query('SELECT * FROM ktp_items ORDER BY planned_date ASC, id DESC');
    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)):
        $typeLabels = ['lecture' => 'Лекция', 'lab' => 'Лабораторная', 'practice' => 'Практика'];
        $typeLabel = $typeLabels[$item['type']] ?? $item['type'];
        $statusClass = $item['is_published'] ? 'bg-green-50 border-green-200' : 'bg-white border-indigo-200';
        $statusText = $item['is_published'] ? '✓ Опубликовано' : 'Черновик';
    ?>
    <div class="card p-4 <?php echo $statusClass; ?>">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-2">
            <h4 class="text-lg font-semibold text-indigo-600"><?php echo htmlspecialchars($item['title']); ?></h4>
            <span class="text-xs px-2 py-1 rounded bg-indigo-100 text-indigo-700"><?php echo $typeLabel; ?></span>
            <span class="text-xs px-2 py-1 rounded <?php echo $item['is_published'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?>"><?php echo $statusText; ?></span>
          </div>
          <?php if ($item['description']): ?>
            <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
          <?php endif; ?>
          <div class="text-sm text-gray-500">
            <?php if ($item['planned_date']): ?>
              <span>📅 Планируется: <?php echo htmlspecialchars($item['planned_date']); ?></span>
            <?php endif; ?>
            <?php if ($item['file_path']): ?>
              <a href="/documents/<?php echo htmlspecialchars($item['file_path']); ?>" class="ml-3 text-indigo-600 hover:underline" target="_blank">📎 Файл</a>
            <?php endif; ?>
          </div>
        </div>
        <div class="flex items-center gap-2 ml-4">
          <?php if (!$item['is_published']): ?>
            <form method="POST" onsubmit="return confirm('Опубликовать материал студентам?');">
              <input type="hidden" name="action" value="publish">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
              <input type="hidden" name="item_type" value="<?php echo $item['type']; ?>">
              <button class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm">Опубликовать</button>
            </form>
          <?php endif; ?>
          <form method="POST" onsubmit="return confirm('Удалить из КТП?');">
            <input type="hidden" name="action" value="delete_ktp">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
            <button class="px-3 py-1 text-red-600 hover:underline text-sm">Удалить</button>
          </form>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
