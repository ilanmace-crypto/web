<?php
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
requireRole(['student','teacher','admin']);

$csrfToken = $_SESSION['csrf_token'] ?? '';

$modelsPdo = connectToDb('models.db');
try {
    $cols = $modelsPdo->query("PRAGMA table_info(models)")->fetchAll(PDO::FETCH_ASSOC);
    $hasUploader = false; $hasTags = false;
    foreach ($cols as $c) {
        $n = $c['name'] ?? '';
        if ($n === 'uploader_id') $hasUploader = true;
        if ($n === 'tags') $hasTags = true;
    }
    if (!$hasUploader) { $modelsPdo->exec("ALTER TABLE models ADD COLUMN uploader_id INTEGER DEFAULT 0"); }
    if (!$hasTags) { $modelsPdo->exec("ALTER TABLE models ADD COLUMN tags TEXT DEFAULT ''"); }
} catch (Throwable $e) { /* ignore */ }

$success = '';
$error = '';

$maxSizeBytes = (int)($_ENV['MAX_UPLOAD_SIZE_MB'] ?? 50) * 1024 * 1024;
$allowedModelExt = array_map('strtolower', explode(',', $_ENV['ALLOWED_MODEL_EXTENSIONS'] ?? 'obj,fbx,stl,glb,gltf,blend,dae'));
$allowedImageExt = array_map('strtolower', explode(',', $_ENV['ALLOWED_IMAGE_EXTENSIONS'] ?? 'jpg,jpeg,png,webp'));

function ensureDirectory(string $path): void {
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

function uploadErrorText($code) {
    return match ($code) {
        UPLOAD_ERR_INI_SIZE => 'Файл превышает upload_max_filesize в php.ini',
        UPLOAD_ERR_FORM_SIZE => 'Файл превышает MAX_FILE_SIZE из формы',
        UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
        UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
        UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
        UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
        UPLOAD_ERR_EXTENSION => 'PHP-расширение остановило загрузку файла',
        default => 'Неизвестная ошибка загрузки',
    };
}

function validateFile($file, $allowedExt, $maxSize) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Ошибка загрузки файла: ' . uploadErrorText($file['error']);
    }
    if ($file['size'] > $maxSize) {
        return 'Файл слишком большой (макс. ' . round($maxSize / 1024 / 1024) . ' МБ)';
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return 'Неверный формат файла';
    }
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) {
            $mime = @finfo_file($f, $file['tmp_name']) ?: '';
            finfo_close($f);
            // Базовая проверка для glb/gltf и изображений
            $ok = true;
            if (in_array($ext, ['glb','gltf'], true)) {
                $ok = (str_contains($mime, 'model') || str_contains($mime, 'application') || str_contains($mime, 'octet-stream'));
            }
            if (in_array($ext, ['jpg','jpeg','png','webp'], true)) {
                $ok = str_starts_with($mime, 'image/');
            }
            if (!$ok) {
                return 'Неверный MIME тип файла';
            }
        }
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit;
    }
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Укажите название модели';
    }

    $uploadsDir = __DIR__ . '/uploads';
    $imagesDir = __DIR__ . '/images';
    ensureDirectory($uploadsDir);
    ensureDirectory($imagesDir);

    $savedModelRel = '';
    $savedImageRel = '';

    if (!$error && isset($_FILES['model_file'])) {
        $modelError = validateFile($_FILES['model_file'], $allowedModelExt, $maxSizeBytes);
        if ($modelError) {
            $error = $modelError;
        } else {
            $modelTmp = $_FILES['model_file']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['model_file']['name'], PATHINFO_EXTENSION));
            $newName = 'model_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = rtrim($uploadsDir, '/') . '/' . $newName;
            if (!is_uploaded_file($modelTmp) || !move_uploaded_file($modelTmp, $dest)) {
                $error = 'Не удалось сохранить файл модели (нет доступа к папке или tmp-файл). Папка: ' . $uploadsDir;
            } else {
                $savedModelRel = $newName;
            }
        }
    } else {
        if (!$error) $error = 'Загрузите файл модели';
    }

    if (!$error && isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $imgError = validateFile($_FILES['image_file'], $allowedImageExt, $maxSizeBytes);
        if ($imgError) {
            $error = $imgError;
        } else {
            $imgTmp = $_FILES['image_file']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $newName = 'preview_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = rtrim($imagesDir, '/') . '/' . $newName;
            if (!is_uploaded_file($imgTmp) || !move_uploaded_file($imgTmp, $dest)) {
                $error = 'Не удалось сохранить изображение (нет доступа к папке или tmp-файл). Папка: ' . $imagesDir;
            } else {
                $savedImageRel = $newName;
            }
        }
    }

    if (!$error) {
        $uploaderId = isLoggedIn() ? (int)currentUser()['id'] : 0;
        $raw = strtolower($name . ' ' . ($savedModelRel ?: ''));
        $parts = preg_split('/[^a-zа-я0-9]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $parts = array_filter($parts, function($w){ return mb_strlen($w) >= 3; });
        $parts = array_slice(array_values(array_unique($parts)), 0, 8);
        $tagsStr = implode(',', $parts);

        $hasTags = false;
        try {
            $cols = $modelsPdo->query("PRAGMA table_info(models)")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $c) { if (($c['name'] ?? '') === 'tags') { $hasTags = true; break; } }
        } catch (Throwable $e) { /* ignore */ }

        if ($hasTags) {
            $stmt = $modelsPdo->prepare('INSERT INTO models (name, description, file_path, image_path, uploader_id, tags) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $description, $savedModelRel, $savedImageRel, $uploaderId, $tagsStr]);
        } else {
            $stmt = $modelsPdo->prepare('INSERT INTO models (name, description, file_path, image_path, uploader_id) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $description, $savedModelRel, $savedImageRel, $uploaderId]);
        }
        $success = 'Модель успешно загружена';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загрузка модели</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans">
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container mx-auto p-6 max-w-2xl">
    <h2 class="text-3xl font-bold text-indigo-700 mb-6">Загрузка модели</h2>
    <div class="grid md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <div class="text-4xl">📦</div>
            <div class="mt-2 font-medium text-indigo-700">Форматы</div>
            <div class="text-sm text-indigo-900/70">GLB, GLTF</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <div class="text-4xl">⏱️</div>
            <div class="mt-2 font-medium text-indigo-700">Совет</div>
            <div class="text-sm text-indigo-900/70">Добавьте краткое описание модели</div>
        </div>
    </div>
    <?php if ($success): ?><p class="text-green-600 mb-4"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
    <?php if ($error): ?><p class="text-red-600 mb-4"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div>
            <label class="block mb-1 font-medium text-indigo-700">Название</label>
            <input name="name" class="w-full border border-indigo-200 rounded p-3" placeholder="Например: Домик" required>
        </div>
        <div>
            <label class="block mb-1 font-medium text-indigo-700">Описание</label>
            <textarea name="description" class="w-full border border-indigo-200 rounded p-3" rows="3" placeholder="Краткое описание"></textarea>
        </div>
        <div>
            <label class="block mb-1 font-medium text-indigo-700">Файл модели (.glb, .gltf)</label>
            <input type="file" name="model_file" accept=".glb,.gltf" required>
        </div>
        <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Загрузить</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

