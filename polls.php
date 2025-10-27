<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pdo = connectToDb('polls.db');
$csrfToken = $_SESSION['csrf_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create' && isLoggedIn() && currentUser()['role'] === 'teacher') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
    $question = trim($_POST['question'] ?? '');
    $options = array_filter(array_map('trim', explode("\n", $_POST['options'] ?? '')));
    if ($question !== '' && count($options) >= 2) {
        $stmt = $pdo->prepare('INSERT INTO polls (question, options) VALUES (?, ?)');
        $stmt->execute([$question, json_encode(array_values($options), JSON_UNESCAPED_UNICODE)]);
    }
    header('Location: /polls.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update' && isLoggedIn() && currentUser()['role'] === 'teacher') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
    $pollId = (int)($_POST['poll_id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $options = array_filter(array_map('trim', explode("\n", $_POST['options'] ?? '')));
    if ($pollId > 0 && $question !== '' && count($options) >= 2) {
        $stmt = $pdo->prepare('UPDATE polls SET question = ?, options = ? WHERE id = ?');
        $stmt->execute([$question, json_encode(array_values($options), JSON_UNESCAPED_UNICODE), $pollId]);

        $pdo->prepare('DELETE FROM votes WHERE poll_id = ?')->execute([$pollId]);
    }
    header('Location: /polls.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && isLoggedIn() && currentUser()['role'] === 'teacher') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
    $pollId = (int)($_POST['poll_id'] ?? 0);
    if ($pollId > 0) {
        $pdo->prepare('DELETE FROM votes WHERE poll_id = ?')->execute([$pollId]);
        $pdo->prepare('DELETE FROM polls WHERE id = ?')->execute([$pollId]);
    }
    header('Location: /root/polls.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'vote' && isLoggedIn()) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
    $pollId = (int)($_POST['poll_id'] ?? 0);
    $idx = (int)($_POST['option_index'] ?? -1);
    if ($pollId > 0 && $idx >= 0) {
        $pdo->prepare('DELETE FROM votes WHERE poll_id = ? AND user_id = ?')->execute([$pollId, currentUser()['id']]);
        $pdo->prepare('INSERT INTO votes (poll_id, option_index, user_id) VALUES (?, ?, ?)')->execute([$pollId, $idx, currentUser()['id']]);
    }
    header('Location: /root/polls.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Опросы</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans">
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container mx-auto p-6 max-w-3xl">
    <h1 class="text-3xl font-bold text-indigo-700 mb-6">Опросы</h1>

    <?php if (isLoggedIn() && currentUser()['role'] === 'teacher'): ?>
    <section class="bg-white rounded-xl shadow p-5 mb-8">
        <h2 class="text-xl font-semibold text-indigo-700 mb-3">Создать опрос</h2>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input class="w-full border border-indigo-200 rounded p-3" name="question" placeholder="Вопрос" required>
            <textarea class="w-full border border-indigo-200 rounded p-3" name="options" rows="4" placeholder="Варианты (каждый с новой строки)" required></textarea>
            <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Создать</button>
        </form>
    </section>
    <?php endif; ?>

    <section class="space-y-5">
        <?php
        $polls = $pdo->query('SELECT id, question, options FROM polls ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($polls as $p):
            $opts = json_decode($p['options'] ?? '[]', true) ?: [];
            $votes = $pdo->prepare('SELECT option_index, COUNT(*) as cnt FROM votes WHERE poll_id = ? GROUP BY option_index');
            $votes->execute([$p['id']]);
            $counts = array_fill(0, count($opts), 0);
            $total = 0;
            while ($row = $votes->fetch(PDO::FETCH_ASSOC)) { $counts[(int)$row['option_index']] = (int)$row['cnt']; $total += (int)$row['cnt']; }
        ?>
        <div class="bg-white rounded-xl shadow p-5">
            <div class="text-lg font-medium text-indigo-800"><?php echo htmlspecialchars($p['question']); ?></div>
            <?php if (isLoggedIn() && currentUser()['role'] === 'teacher'): ?>
            <div class="mt-2 flex items-center gap-3">
                <form method="POST" onsubmit="return confirm('Удалить опрос?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="poll_id" value="<?php echo (int)$p['id']; ?>">
                    <button class="text-red-600 hover:underline text-sm">Удалить</button>
                </form>
                <details>
                    <summary class="cursor-pointer text-indigo-600 text-sm">Редактировать</summary>
                    <form method="POST" class="mt-2 space-y-2">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="poll_id" value="<?php echo (int)$p['id']; ?>">
                        <input class="w-full border border-indigo-200 rounded p-2" name="question" value="<?php echo htmlspecialchars($p['question']); ?>" required>
                        <textarea class="w-full border border-indigo-200 rounded p-2" name="options" rows="4" placeholder="Варианты (по одному в строке)" required><?php echo htmlspecialchars(implode("\n", $opts)); ?></textarea>
                        <button class="bg-indigo-600 text-white px-3 py-1 rounded">Сохранить</button>
                    </form>
                </details>
            </div>
            <?php endif; ?>
            <div class="mt-3 grid gap-2">
                <?php foreach ($opts as $i => $opt): 
                    $c = $counts[$i] ?? 0; $pct = $total ? round($c * 100 / $total) : 0; ?>
                    <div>
                        <div class="flex items-center justify-between text-sm text-indigo-900/80">
                            <div><?php echo htmlspecialchars($opt); ?></div>
                            <div><?php echo $c; ?> (<?php echo $pct; ?>%)</div>
                        </div>
                        <div class="w-full bg-indigo-100 rounded h-2 overflow-hidden"><div style="width: <?php echo $pct; ?>%" class="h-2 bg-indigo-500"></div></div>
                        <?php if (isLoggedIn()): ?>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="action" value="vote">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="poll_id" value="<?php echo (int)$p['id']; ?>">
                            <input type="hidden" name="option_index" value="<?php echo (int)$i; ?>">
                            <button class="text-indigo-600 hover:underline text-sm">Голосовать</button>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
