<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
requireRole(['student','teacher','admin']);

$user = currentUser();
$modelsPdo = connectToDb('models.db');
$ratingsPdo = connectToDb('polls.db');
$commentsPdo = connectToDb('comments.db');

$stmt = $modelsPdo->prepare('SELECT COUNT(*) FROM models WHERE uploader_id = ?');
$stmt->execute([$user['id']]);
$myModelsCount = (int)$stmt->fetchColumn();

$stmt = $ratingsPdo->prepare('SELECT COUNT(*) FROM ratings WHERE user_id = ?');
$stmt->execute([$user['id']]);
$myRatingsCount = (int)$stmt->fetchColumn();

$stmt = $commentsPdo->prepare('SELECT COUNT(*) FROM comments WHERE user_id = ?');
$stmt->execute([$user['id']]);
$myCommentsCount = (int)$stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет студента</title>
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
        <!-- Welcome Header -->
        <div class="card p-8 mb-8">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gradient mb-2">Добро пожаловать, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                    <p class="text-indigo-600/80">Ваш личный кабинет студента</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="text-6xl">🎓</div>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card card p-6 text-center">
                <div class="text-4xl font-bold text-indigo-600 mb-2"><?php echo $myModelsCount; ?></div>
                <div class="text-indigo-700 font-medium">Загружено моделей</div>
            </div>
            <div class="stat-card card p-6 text-center">
                <div class="text-4xl font-bold text-indigo-600 mb-2"><?php echo $myRatingsCount; ?></div>
                <div class="text-indigo-700 font-medium">Поставлено оценок</div>
            </div>
            <div class="stat-card card p-6 text-center">
                <div class="text-4xl font-bold text-indigo-600 mb-2"><?php echo $myCommentsCount; ?></div>
                <div class="text-indigo-700 font-medium">Написано комментариев</div>
            </div>
        </div>

        <div class="card p-8 mb-8">
            <h2 class="text-2xl font-bold text-indigo-700 mb-6">Быстрые действия</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="/upload.php" class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg group">
                    <div class="text-3xl mb-2 group-hover:animate-bounce">📦</div>
                    <div class="font-semibold">Загрузить модель</div>
                    <div class="text-sm opacity-90 mt-1">Поделитесь своей работой</div>
                </a>
                <a href="/gallery.php" class="bg-gradient-to-br from-purple-500 to-pink-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg group">
                    <div class="text-3xl mb-2 group-hover:animate-bounce">🖼️</div>
                    <div class="font-semibold">Галерея</div>
                    <div class="text-sm opacity-90 mt-1">Посмотреть модели</div>
                </a>
                <a href="/lectures.php" class="bg-gradient-to-br from-pink-500 to-red-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg group">
                    <div class="text-3xl mb-2 group-hover:animate-bounce">📚</div>
                    <div class="font-semibold">Лекции</div>
                    <div class="text-sm opacity-90 mt-1">Изучить материалы</div>
                </a>
                <a href="/tests.php" class="bg-gradient-to-br from-green-500 to-blue-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg group">
                    <div class="text-3xl mb-2 group-hover:animate-bounce">🧪</div>
                    <div class="font-semibold">Тесты</div>
                    <div class="text-sm opacity-90 mt-1">Проверить знания</div>
                </a>
            </div>
        </div>

        <div class="card p-8">
            <h2 class="text-2xl font-bold text-indigo-700 mb-6">Недавняя активность</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-indigo-50/50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="text-2xl">📝</div>
                        <div>
                            <div class="font-medium text-indigo-700">Загружено моделей</div>
                            <div class="text-sm text-indigo-600/70">Последние работы</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-indigo-600"><?php echo $myModelsCount; ?></div>
                        <div class="text-sm text-indigo-600/70">всего</div>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 bg-purple-50/50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="text-2xl">⭐</div>
                        <div>
                            <div class="font-medium text-indigo-700">Оценок поставлено</div>
                            <div class="text-sm text-indigo-600/70">Помогли другим студентам</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-indigo-600"><?php echo $myRatingsCount; ?></div>
                        <div class="text-sm text-indigo-600/70">всего</div>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 bg-pink-50/50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="text-2xl">💬</div>
                        <div>
                            <div class="font-medium text-indigo-700">Комментариев написано</div>
                            <div class="text-sm text-indigo-600/70">Обсудили работы</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-indigo-600"><?php echo $myCommentsCount; ?></div>
                        <div class="text-sm text-indigo-600/70">всего</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

