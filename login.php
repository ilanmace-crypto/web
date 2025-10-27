<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) { header('Location: /index.php'); exit; }

$error = '';
$usernameErr = '';
$passwordErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit;
    }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '') {
        $usernameErr = 'Введите логин';
    }
    if ($password === '') {
        $passwordErr = 'Введите пароль';
    }
    if (!$usernameErr && !$passwordErr) {
        if (!login($username, $password)) {
            $error = 'Неверный логин или пароль';
        } else {
            header('Location: /index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    </style>
</head>
<body class="font-sans">
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container mx-auto p-6 max-w-md">
    <h2 class="text-3xl font-bold text-indigo-700 mb-6 text-center">Вход</h2>
    <?php if ($error): ?><p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="POST" class="bg-white rounded-xl shadow p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <div>
            <input class="w-full border <?php echo $usernameErr ? 'border-red-500' : 'border-indigo-200'; ?> rounded p-3" name="username" placeholder="Логин" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
            <?php if ($usernameErr): ?><p class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($usernameErr); ?></p><?php endif; ?>
        </div>
        <div>
            <input class="w-full border <?php echo $passwordErr ? 'border-red-500' : 'border-indigo-200'; ?> rounded p-3" name="password" type="password" placeholder="Пароль" required>
            <?php if ($passwordErr): ?><p class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($passwordErr); ?></p><?php endif; ?>
        </div>
        <button class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Войти</button>
        <p class="text-center text-sm text-indigo-900/70">Нет аккаунта? <a class="text-indigo-600 hover:underline" href="/register.php">Регистрация</a></p>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
