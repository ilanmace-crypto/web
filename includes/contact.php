<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans">
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container mx-auto p-6 max-w-2xl">
    <h1 class="text-4xl font-extrabold text-indigo-700 mb-6">Контакты</h1>
    <form action="/send_contact.php" method="POST" class="bg-white rounded-xl shadow p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <input class="w-full border border-indigo-200 rounded p-3" name="name" placeholder="Ваше имя" required>
        <input class="w-full border border-indigo-200 rounded p-3" type="email" name="email" placeholder="Email" required>
        <textarea class="w-full border border-indigo-200 rounded p-3" name="message" rows="5" placeholder="Сообщение" required></textarea>
        <button class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Отправить</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
