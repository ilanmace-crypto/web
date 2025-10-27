<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Лаборатория — Главная</title>
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
        .hero-bg { background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9), rgba(240, 147, 251, 0.9)); }
        .feature-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
    <meta name="description" content="Платформа для 3D‑моделирования: лекции, ресурсы, модели, комментарии и тесты.">
</head>
<body class="font-sans">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mx-auto px-6 py-14">
        <section class="text-center py-16 hero-bg rounded-2xl mb-16 shadow-2xl">
            <h1 class="text-5xl md:text-7xl font-extrabold text-white mb-6 drop-shadow-lg">3D Лаборатория</h1>
            <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto mb-8 drop-shadow">Загружайте модели, изучайте материалы, комментируйте и оценивайте работы в современной 3D-среде.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/gallery.php" class="btn-primary inline-block">Перейти к моделям</a>
                <a href="/lectures.php" class="btn-secondary inline-block">Изучить лекции</a>
            </div>
        </section>

        <!-- Features Grid -->
        <section class="grid md:grid-cols-3 gap-8 mb-16">
            <div class="feature-card card p-8 text-center group">
                <div class="text-6xl mb-4 group-hover:scale-110 transition-transform">🧩</div>
                <h3 class="text-2xl font-bold text-indigo-700 mb-4">Галерея моделей</h3>
                <p class="text-indigo-600/80">Загружайте и оценивайте 3D‑работы с интерактивным просмотром.</p>
                <a href="/gallery.php" class="btn-primary mt-6 inline-block">Посмотреть</a>
            </div>
            <div class="feature-card card p-8 text-center group">
                <div class="text-6xl mb-4 group-hover:scale-110 transition-transform">📚</div>
                <h3 class="text-2xl font-bold text-indigo-700 mb-4">Лекции и ресурсы</h3>
                <p class="text-indigo-600/80">PDF, видео и ссылки от экспертов в области 3D.</p>
                <a href="/lectures.php" class="btn-primary mt-6 inline-block">Изучить</a>
            </div>
            <div class="feature-card card p-8 text-center group">
                <div class="text-6xl mb-4 group-hover:scale-110 transition-transform">🛠️</div>
                <h3 class="text-2xl font-bold text-indigo-700 mb-4">Интерактивные тесты</h3>
                <p class="text-indigo-600/80">Проверьте знания с помощью наших тестов и опросов.</p>
                <a href="/tests.php" class="btn-primary mt-6 inline-block">Начать</a>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="card p-8 mb-16">
            <h2 class="text-3xl font-bold text-gradient text-center mb-8">Быстрые действия</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="/upload.php" class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg">
                    <div class="text-3xl mb-2">📦</div>
                    <div class="font-semibold">Загрузить модель</div>
                </a>
                <a href="/resources.php" class="bg-gradient-to-br from-purple-500 to-pink-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg">
                    <div class="text-3xl mb-2">🔗</div>
                    <div class="font-semibold">Ресурсы</div>
                </a>
                <a href="/polls.php" class="bg-gradient-to-br from-pink-500 to-red-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg">
                    <div class="text-3xl mb-2">📊</div>
                    <div class="font-semibold">Опросы</div>
                </a>
                <a href="/contact.php" class="bg-gradient-to-br from-green-500 to-blue-500 text-white p-6 rounded-xl text-center hover:scale-105 transition-all shadow-lg">
                    <div class="text-3xl mb-2">💬</div>
                    <div class="font-semibold">Контакты</div>
                </a>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

