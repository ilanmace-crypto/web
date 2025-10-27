<?php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О сайте</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    </style>
    <meta name="description" content="Информация о платформе 3D Лаборатория">
    <meta name="robots" content="noindex">
    </head>
<body class="font-sans">
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container mx-auto px-6 py-10 max-w-3xl">
    <h1 class="text-4xl font-extrabold text-indigo-700">О платформе</h1>
    <p class="mt-4 text-indigo-900/80">3D Лаборатория — учебная платформа для размещения 3D‑моделей, материалов и взаимодействия студентов и преподавателей.</p>

    <div class="mt-8 grid md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow p-5">
            <div class="text-2xl">📚</div>
            <div class="mt-2 font-semibold text-indigo-700">Материалы</div>
            <div class="text-indigo-900/70 text-sm mt-1">Лекции, ресурсы, документы</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <div class="text-2xl">🧩</div>
            <div class="mt-2 font-semibold text-indigo-700">Проекты</div>
            <div class="text-indigo-900/70 text-sm mt-1">Галерея моделей и отзывы</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <div class="text-2xl">🛡️</div>
            <div class="mt-2 font-semibold text-indigo-700">Роли</div>
            <div class="text-indigo-900/70 text-sm mt-1">Студенты и преподаватели</div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
