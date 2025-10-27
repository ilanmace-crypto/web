<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
requireRole(['teacher','admin']);

$user = currentUser();

$lecturesPdo = connectToDb('lectures.db');
$documentsPdo = connectToDb('documents.db');
$resourcesPdo = connectToDb('resources.db');
$testsPdo = connectToDb('tests.db');
$labsPdo = connectToDb('labs.db');
$modelsPdo = connectToDb('models.db');
$commentsPdo = connectToDb('comments.db');

$lecturesCount = (int)$lecturesPdo->query('SELECT COUNT(*) FROM lectures')->fetchColumn();
$documentsCount = (int)$documentsPdo->query('SELECT COUNT(*) FROM documents')->fetchColumn();
$resourcesCount = (int)$resourcesPdo->query('SELECT COUNT(*) FROM resources')->fetchColumn();
$testsCount = (int)$testsPdo->query('SELECT COUNT(*) FROM tests')->fetchColumn();
$labsCount = (int)$labsPdo->query('SELECT COUNT(*) FROM labs')->fetchColumn();
$modelsCount = (int)$modelsPdo->query('SELECT COUNT(*) FROM models')->fetchColumn();

function ensureDirectoryTeacher(string $path): void {
    if (!is_dir($path)) { @mkdir($path, 0775, true); }
}

$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_document') {
        $title = trim($_POST['doc_title'] ?? '');
        if ($title === '' || empty($_FILES['doc_file']) || $_FILES['doc_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Укажите название и выберите файл документа';
        } else {
            $docsDir = __DIR__ . '/../documents';
            ensureDirectoryTeacher($docsDir);
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $error = 'Разрешены только PDF-файлы';
            } else {
                $tmp = $_FILES['doc_file']['tmp_name'];
                $ok = is_uploaded_file($tmp);
                if (function_exists('finfo_open')) {
                    $f = finfo_open(FILEINFO_MIME_TYPE);
                    if ($f) {
                        $mime = @finfo_file($f, $tmp) ?: '';
                        finfo_close($f);
                        $allowed = ['application/pdf', 'application/x-pdf'];
                        if (!in_array($mime, $allowed, true)) { $ok = false; }
                    }
                }
                if ($ok) {
                    $newName = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
                    $dest = rtrim($docsDir, '/') . '/' . $newName;
                    if (!move_uploaded_file($tmp, $dest)) {
                        $error = 'Не удалось сохранить документ';
                    } else {
                        $stmt = $documentsPdo->prepare('INSERT INTO documents (title, file_path) VALUES (?, ?)');
                        $stmt->execute([$title, $newName]);
                        $notice = 'Документ загружен';
                    }
                } else {
                    $error = 'Неверный формат файла (ожидается PDF)';
                }
            }
        }
    }

    if ($action === 'upload_lecture') {
        $title = trim($_POST['lec_title'] ?? '');
        $content = trim($_POST['lec_content'] ?? '');
        $video = trim($_POST['lec_video'] ?? '');
        $filePath = '';
        if (!empty($_FILES['lec_file']) && $_FILES['lec_file']['error'] === UPLOAD_ERR_OK) {
            $docsDir = __DIR__ . '/../documents';
            ensureDirectoryTeacher($docsDir);
            $ext = strtolower(pathinfo($_FILES['lec_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $tmp = $_FILES['lec_file']['tmp_name'];
                $ok = is_uploaded_file($tmp);
                if (function_exists('finfo_open')) {
                    $f = finfo_open(FILEINFO_MIME_TYPE);
                    if ($f) {
                        $mime = @finfo_file($f, $tmp) ?: '';
                        finfo_close($f);
                        $allowed = ['application/pdf', 'application/x-pdf'];
                        if (!in_array($mime, $allowed, true)) { $ok = false; }
                    }
                }
                if ($ok) {
                    $newName = 'lecture_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
                    $dest = rtrim($docsDir, '/') . '/' . $newName;
                    if (move_uploaded_file($tmp, $dest)) {
                        $filePath = $newName;
                    }
                }
            }
        }
        if ($title !== '') {
            $stmt = $lecturesPdo->prepare('INSERT INTO lectures (title, content, file_path, video_url) VALUES (?, ?, ?, ?)');
            $stmt->execute([$title, $content, $filePath, $video]);
            $notice = 'Лекция добавлена';
        } else {
            $error = 'Укажите название лекции';
        }
    }

    if ($action === 'add_resource') {
        $title = trim($_POST['res_title'] ?? '');
        $url = trim($_POST['res_url'] ?? '');
        $type = $_POST['res_type'] ?? 'book';
        if ($title && $url) {
            $stmt = $resourcesPdo->prepare('INSERT INTO resources (title, url, type) VALUES (?, ?, ?)');
            $stmt->execute([$title, $url, $type]);
            $notice = 'Ресурс добавлен';
        } else { $error = 'Заполните название и ссылку'; }
    }

    if ($action === 'add_test') {
        $question = trim($_POST['test_question'] ?? '');
        $opt1 = trim($_POST['opt1'] ?? '');
        $opt2 = trim($_POST['opt2'] ?? '');
        $opt3 = trim($_POST['opt3'] ?? '');
        $opt4 = trim($_POST['opt4'] ?? '');
        $correct = (int)($_POST['correct'] ?? 0);
        if ($question && $opt1 && $opt2 && $opt3 && $opt4 && $correct >=0 && $correct <=3) {
            $options = json_encode([$opt1,$opt2,$opt3,$opt4], JSON_UNESCAPED_UNICODE);
            $stmt = $testsPdo->prepare('INSERT INTO tests (question, options, correct_answer) VALUES (?, ?, ?)');
            $stmt->execute([$question, $options, $correct]);
            $notice = 'Тест добавлен';
        } else { $error = 'Заполните вопрос, 4 варианта и правильный индекс (0-3)'; }
    }

    if ($action === 'upload_lab') {
        $title = trim($_POST['lab_title'] ?? '');
        $desc = trim($_POST['lab_desc'] ?? '');
        if ($title && !empty($_FILES['lab_file']) && $_FILES['lab_file']['error'] === UPLOAD_ERR_OK) {
            $docsDir = __DIR__ . '/../documents';
            ensureDirectoryTeacher($docsDir);
            $ext = strtolower(pathinfo($_FILES['lab_file']['name'], PATHINFO_EXTENSION));
            $newName = 'lab_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = rtrim($docsDir, '/') . '/' . $newName;
            $tmp = $_FILES['lab_file']['tmp_name'];
            if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $dest)) {
                $stmt = $labsPdo->prepare('INSERT INTO labs (title, description, file_path) VALUES (?, ?, ?)');
                $stmt->execute([$title, $desc, $newName]);
                $notice = 'Лаба загружена';
            } else { $error = 'Не удалось сохранить файл лабы'; }
        } else { $error = 'Заполните название и выберите файл'; }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет преподавателя</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); color: #1e3a8a; font-family: 'Inter', sans-serif; min-height: 100vh; }
        .nav-link:hover { background-color: rgba(255, 255, 255, 0.2); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .nav-link.active { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #ffffff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
        .card { background: rgba(255, 255, 255, 0.95); border-radius: 12px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; border: 1px solid rgba(255, 255, 255, 0.2); }
        .card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15); }
        .btn-primary { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; }
        .btn-primary:hover { background: linear-gradient(135deg, #3730a3, #6d28d9); transform: translateY(-1px); box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4); }
        .btn-secondary { background: rgba(255, 255, 255, 0.9); color: #4f46e5; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; transition: all 0.3s ease; border: 1px solid rgba(79, 70, 229, 0.2); }
        .btn-secondary:hover { background: white; color: #3730a3; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); }
        .text-gradient { background: linear-gradient(135deg, #4f46e5, #7c3aed, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .stat-card { background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(124, 58, 237, 0.1)); }
    </style>
</head>
<body class="font-sans">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mx-auto px-6 py-8">
        <div class="card p-8 mb-8">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gradient mb-2">Добро пожаловать, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                    <p class="text-indigo-600/80">Ваш административный кабинет преподавателя</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="text-6xl">👨‍🏫</div>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="stat-card card p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600 mb-1"><?php echo $lecturesCount; ?></div>
                <div class="text-indigo-700 text-sm font-medium">Лекций</div>
            </div>
            <div class="stat-card card p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600 mb-1"><?php echo $documentsCount; ?></div>
                <div class="text-indigo-700 text-sm font-medium">Документов</div>
            </div>
            <div class="stat-card card p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600 mb-1"><?php echo $resourcesCount; ?></div>
                <div class="text-indigo-700 text-sm font-medium">Ресурсов</div>
            </div>
            <div class="stat-card card p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600 mb-1"><?php echo $testsCount; ?></div>
                <div class="text-indigo-700 text-sm font-medium">Тестов</div>
            </div>
            <div class="stat-card card p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600 mb-1"><?php echo $labsCount; ?></div>
                <div class="text-indigo-700 text-sm font-medium">Лаб</div>
            </div>
            <div class="stat-card card p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600 mb-1"><?php echo $modelsCount; ?></div>
                <div class="text-indigo-700 text-sm font-medium">Моделей</div>
            </div>
        </div>

        <div class="card p-8 mb-8">
            <h2 class="text-2xl font-bold text-indigo-700 mb-6">Быстрые действия</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <button onclick="showModal('modal-lecture')" class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg group">
                    <div class="text-3xl mb-2 group-hover:animate-bounce">📚</div>
                    <div class="font-semibold">Добавить лекцию</div>
                    <div class="text-sm opacity-90 mt-1">Создайте новую лекцию</div>
                </button>
                <button onclick="showModal('modal-doc')" class="bg-gradient-to-br from-purple-500 to-pink-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg group">
                    <div class="text-3xl mb-2 group-hover:animate-bounce">📄</div>
                    <div class="font-semibold">Загрузить документ</div>
                    <div class="text-sm opacity-90 mt-1">Добавьте PDF или файл</div>
                </button>
                <button onclick="showModal('modal-resource')" class="bg-gradient-to-br from-pink-500 to-red-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg group">
                    <div class="text-3xl mb-2 group-hover:animate-bounce">🔗</div>
                    <div class="font-semibold">Добавить ресурс</div>
                    <div class="text-sm opacity-90 mt-1">Книгу или видео</div>
                </button>
                <button onclick="showModal('modal-test')" class="bg-gradient-to-br from-green-500 to-blue-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg group">
                    <div class="text-3xl mb-2 group-hover:animate-bounce">🧪</div>
                    <div class="font-semibold">Создать тест</div>
                    <div class="text-sm opacity-90 mt-1">Новый вопрос с ответами</div>
                </button>
                <a href="/labs.php" class="bg-gradient-to-br from-blue-500 to-cyan-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg group">
                    <div class="text-3xl mb-2 group-hover:animate-bounce">🧫</div>
                    <div class="font-semibold">Лабораторные</div>
                    <div class="text-sm opacity-90 mt-1">Управление лабами</div>
                </a>
                <a href="/gallery.php" class="bg-gradient-to-br from-cyan-500 to-teal-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg group">
                    <div class="text-3xl mb-2 group-hover:animate-bounce">🖼️</div>
                    <div class="font-semibold">Модели студентов</div>
                    <div class="text-sm opacity-90 mt-1">Посмотреть галерею</div>
                </a>
            </div>
        </div>

        <?php if ($notice): ?>
        <div class="card p-4 mb-6 border-l-4 border-green-500 bg-green-50 text-green-700">
            <?php echo htmlspecialchars($notice); ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="card p-4 mb-6 border-l-4 border-red-500 bg-red-50 text-red-700">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div class="card p-6">
            <div class="flex flex-wrap gap-2 mb-6">
                <button data-tab="overview" class="tab-btn px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium">Обзор</button>
                <button data-tab="lectures" class="tab-btn px-4 py-2 rounded-lg bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50">Лекции</button>
                <button data-tab="documents" class="tab-btn px-4 py-2 rounded-lg bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50">Документы</button>
                <button data-tab="resources" class="tab-btn px-4 py-2 rounded-lg bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50">Ресурсы</button>
                <button data-tab="tests" class="tab-btn px-4 py-2 rounded-lg bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50">Тесты</button>
            </div>

            <div id="panel-overview" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-100 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-indigo-700 mb-3">📊 Статистика платформы</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Всего лекций:</span>
                                <span class="font-medium"><?php echo $lecturesCount; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Документов:</span>
                                <span class="font-medium"><?php echo $documentsCount; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Ресурсов:</span>
                                <span class="font-medium"><?php echo $resourcesCount; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Тестов:</span>
                                <span class="font-medium"><?php echo $testsCount; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-100 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-indigo-700 mb-3">🎯 Быстрые ссылки</h3>
                        <div class="space-y-3">
                            <a href="/upload.php" class="block p-3 bg-white rounded-lg hover:bg-indigo-50 transition-colors">
                                <div class="font-medium text-indigo-700">Загрузить модель</div>
                                <div class="text-sm text-indigo-600">Поделитесь своей работой</div>
                            </a>
                            <a href="/polls.php" class="block p-3 bg-white rounded-lg hover:bg-indigo-50 transition-colors">
                                <div class="font-medium text-indigo-700">Создать опрос</div>
                                <div class="text-sm text-indigo-600">Соберите отзывы</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div id="panel-lectures" class="hidden">
                <h3 class="text-xl font-semibold text-indigo-700 mb-4">Управление лекциями</h3>
                <p class="text-indigo-600/80 mb-4">Здесь вы можете добавлять новые лекции с описанием, файлами и видео.</p>
                <button onclick="showModal('modal-lecture')" class="btn-primary">Добавить лекцию</button>
                <div class="mt-6">
                    <p class="text-indigo-600/70">Всего лекций: <span class="font-medium"><?php echo $lecturesCount; ?></span></p>
                </div>
            </div>

            <div id="panel-documents" class="hidden">
                <h3 class="text-xl font-semibold text-indigo-700 mb-4">Управление документами</h3>
                <p class="text-indigo-600/80 mb-4">Загружайте PDF-файлы лабораторных работ и других материалов.</p>
                <button onclick="showModal('modal-doc')" class="btn-primary">Загрузить документ</button>
                <div class="mt-6">
                    <p class="text-indigo-600/70">Всего документов: <span class="font-medium"><?php echo $documentsCount; ?></span></p>
                </div>
            </div>

            <div id="panel-resources" class="hidden">
                <h3 class="text-xl font-semibold text-indigo-700 mb-4">Управление ресурсами</h3>
                <p class="text-indigo-600/80 mb-4">Добавляйте полезные ссылки на книги и видео.</p>
                <button onclick="showModal('modal-resource')" class="btn-primary">Добавить ресурс</button>
                <div class="mt-6">
                    <p class="text-indigo-600/70">Всего ресурсов: <span class="font-medium"><?php echo $resourcesCount; ?></span></p>
                </div>
            </div>

            <div id="panel-tests" class="hidden">
                <h3 class="text-xl font-semibold text-indigo-700 mb-4">Управление тестами</h3>
                <p class="text-indigo-600/80 mb-4">Создавайте вопросы с множественным выбором.</p>
                <button onclick="showModal('modal-test')" class="btn-primary">Добавить тест</button>
                <div class="mt-6">
                    <p class="text-indigo-600/70">Всего тестов: <span class="font-medium"><?php echo $testsCount; ?></span></p>
                </div>
            </div>
        </div>

        <div id="modal-doc" class="fixed inset-0 z-50 hidden">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="hideModal('modal-doc')"></div>
            <div class="relative mx-auto mt-16 w-11/12 max-w-lg">
                <div class="card p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-indigo-700">Загрузить документ</h3>
                        <button onclick="hideModal('modal-doc')" class="text-indigo-700 hover:text-indigo-900">✖</button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="upload_document">
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Название документа</label>
                            <input class="w-full border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="doc_title" placeholder="Название документа" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Файл (PDF)</label>
                            <input class="w-full" type="file" name="doc_file" accept=".pdf" required>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" onclick="hideModal('modal-doc')" class="btn-secondary">Отмена</button>
                            <button class="btn-primary">Загрузить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="modal-lecture" class="fixed inset-0 z-50 hidden">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="hideModal('modal-lecture')"></div>
            <div class="relative mx-auto mt-16 w-11/12 max-w-lg">
                <div class="card p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-indigo-700">Добавить лекцию</h3>
                        <button onclick="hideModal('modal-lecture')" class="text-indigo-700 hover:text-indigo-900">✖</button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="upload_lecture">
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Название лекции</label>
                            <input class="w-full border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="lec_title" placeholder="Название лекции" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Краткое содержание</label>
                            <textarea class="w-full border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="lec_content" rows="3" placeholder="Краткое содержание"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Файл лекции (PDF, опционально)</label>
                            <input class="w-full" type="file" name="lec_file" accept=".pdf">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Ссылка на видео (YouTube)</label>
                            <input class="w-full border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="lec_video" placeholder="Ссылка на видео (YouTube)">
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" onclick="hideModal('modal-lecture')" class="btn-secondary">Отмена</button>
                            <button class="btn-primary">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="modal-resource" class="fixed inset-0 z-50 hidden">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="hideModal('modal-resource')"></div>
            <div class="relative mx-auto mt-16 w-11/12 max-w-lg">
                <div class="card p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-indigo-700">Добавить ресурс</h3>
                        <button onclick="hideModal('modal-resource')" class="text-indigo-700 hover:text-indigo-900">✖</button>
                    </div>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_resource">
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Название</label>
                            <input class="w-full border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="res_title" placeholder="Название" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Ссылка</label>
                            <input class="w-full border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="res_url" placeholder="Ссылка" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Тип</label>
                            <select class="w-full border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="res_type">
                                <option value="book">Книга</option>
                                <option value="video">Видео</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" onclick="hideModal('modal-resource')" class="btn-secondary">Отмена</button>
                            <button class="btn-primary">Добавить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="modal-test" class="fixed inset-0 z-50 hidden">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="hideModal('modal-test')"></div>
            <div class="relative mx-auto mt-16 w-11/12 max-w-lg">
                <div class="card p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-indigo-700">Добавить тест</h3>
                        <button onclick="hideModal('modal-test')" class="text-indigo-700 hover:text-indigo-900">✖</button>
                    </div>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_test">
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Вопрос</label>
                            <input class="w-full border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="test_question" placeholder="Вопрос" required>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <input class="border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="opt1" placeholder="Вариант 1" required>
                            <input class="border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="opt2" placeholder="Вариант 2" required>
                            <input class="border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="opt3" placeholder="Вариант 3" required>
                            <input class="border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="opt4" placeholder="Вариант 4" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-indigo-700 mb-2">Индекс правильного ответа (0-3)</label>
                            <input class="w-full border border-indigo-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" name="correct" type="number" min="0" max="3" placeholder="Индекс правильного (0..3)" required>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" onclick="hideModal('modal-test')" class="btn-secondary">Отмена</button>
                            <button class="btn-primary">Сохранить тест</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            const tabButtons = document.querySelectorAll('.tab-btn');
            const panels = {
                overview: document.getElementById('panel-overview'),
                documents: document.getElementById('panel-documents'),
                lectures: document.getElementById('panel-lectures'),
                resources: document.getElementById('panel-resources'),
                tests: document.getElementById('panel-tests')
            };
            function setActive(tab){
                tabButtons.forEach(b=>{
                    const active = b.getAttribute('data-tab')===tab;
                    b.className = 'tab-btn px-4 py-2 rounded-lg ' + (active ? 'bg-indigo-600 text-white' : 'bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50');
                });
                Object.entries(panels).forEach(([k,el])=>{
                    if (!el) return;
                    el.classList.toggle('hidden', k!==tab);
                });
            }
            tabButtons.forEach(b=>b.addEventListener('click',()=>setActive(b.getAttribute('data-tab'))));
            setActive('overview');

            function showModal(id) {
                document.getElementById(id).classList.remove('hidden');
            }
            function hideModal(id) {
                document.getElementById(id).classList.add('hidden');
            }
        </script>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
