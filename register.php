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

$error = '';
$usernameErr = '';
$passwordErr = '';
$roleErr = '';
$teacherCodeErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit;
    }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? 'student';
    $teacherCode = trim($_POST['teacher_code'] ?? '');

    if ($username === '') {
        $usernameErr = 'Введите логин';
    }
    if ($password === '') {
        $passwordErr = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $passwordErr = 'Пароль должен быть не менее 6 символов';
    }
    if (!$usernameErr && !$passwordErr) {
        $finalRole = 'student';
        if ($selectedRole === 'teacher') {
            if ($teacherCode !== ($_ENV['TEACHER_SECRET_CODE'] ?? '')) {
                $teacherCodeErr = 'Неверный секретный код преподавателя';
            } else {
                $finalRole = 'teacher';
            }
        }
        if (!$teacherCodeErr) {
            if (!register($username, $password, $finalRole)) {
                $error = 'Пользователь уже существует';
            } else {
                login($username, $password);
                header('Location: /index.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    </style>
</head>
<body class="font-sans">
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container mx-auto p-6 max-w-md">
    <h2 class="text-3xl font-bold text-indigo-700 mb-6 text-center">Регистрация</h2>
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
        <div>
            <label class="block mb-1 font-medium text-indigo-700">Роль</label>
            <div class="flex gap-4 items-center">
                <label class="flex items-center gap-2 text-indigo-900/80"><input type="radio" name="role" value="student" <?php echo ($selectedRole ?? 'student') === 'student' ? 'checked' : ''; ?>> Студент</label>
                <label class="flex items-center gap-2 text-indigo-900/80"><input type="radio" name="role" value="teacher" <?php echo ($selectedRole ?? '') === 'teacher' ? 'checked' : ''; ?>> Преподаватель</label>
            </div>
        </div>
        <div>
            <input class="w-full border <?php echo $teacherCodeErr ? 'border-red-500' : 'border-indigo-200'; ?> rounded p-3" name="teacher_code" placeholder="Введите код, если вы преподаватель" value="<?php echo htmlspecialchars($teacherCode ?? ''); ?>">
            <?php if ($teacherCodeErr): ?><p class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($teacherCodeErr); ?></p><?php endif; ?>
            <p class="text-xs text-indigo-900/60 mt-1">Если вы студент — оставьте поле пустым.</p>
        </div>
        <button class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Зарегистрироваться</button>
        <p class="text-center text-sm text-indigo-900/70">Уже есть аккаунт? <a class="text-indigo-600 hover:underline" href="/login.php">Войти</a></p>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
