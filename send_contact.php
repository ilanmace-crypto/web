<?php
require_once __DIR__ . '/../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contact.php');
    exit;
}

$tokenOk = hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '');
if (!$tokenOk) { http_response_code(403); exit; }

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

$error = '';
if ($name === '' || $email === '' || $message === '') {
    $error = 'Пожалуйста, заполните все поля формы';
}
if (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Некорректный email';
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отправка сообщения</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
</style>
</head>
<body class="font-sans">
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container mx-auto p-6 max-w-2xl">
    <h1 class="text-3xl font-bold text-indigo-700 mb-6">Отправка сообщения</h1>
    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded p-4 mb-6"><?php echo htmlspecialchars($error); ?></div>
        <a class="text-indigo-600 hover:underline" href="/contact.php">Вернуться к форме</a>
    <?php else: ?>
        <div class="bg-green-50 border border-green-200 text-green-700 rounded p-4 mb-6">Сообщение отправлено. Спасибо!</div>
        <div class="bg-white rounded-xl shadow p-5 space-y-2">
            <div><span class="font-medium text-indigo-700">Имя:</span> <?php echo htmlspecialchars($name); ?></div>
            <div><span class="font-medium text-indigo-700">Email:</span> <?php echo htmlspecialchars($email); ?></div>
            <div><span class="font-medium text-indigo-700">Сообщение:</span> <div class="mt-1 text-indigo-900/80 whitespace-pre-line"><?php echo htmlspecialchars($message); ?></div></div>
        </div>
        <div class="mt-6">
            <a class="text-indigo-600 hover:underline" href="/index.php">На главную</a>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
