<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
$pdo = connectToDb('tests.db');
$csrfToken = $_SESSION['csrf_token'] ?? '';

// CRUD только для преподавателя
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['create','update','delete'], true)) {
        if (!(isLoggedIn() && currentUser()['role'] === 'teacher')) {
            header('Location: /tests.php');
            exit;
        }
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        if ($action === 'create') {
            $question = trim($_POST['question'] ?? '');
            $opt1 = trim($_POST['opt1'] ?? '');
            $opt2 = trim($_POST['opt2'] ?? '');
            $opt3 = trim($_POST['opt3'] ?? '');
            $opt4 = trim($_POST['opt4'] ?? '');
            $correct = (int)($_POST['correct'] ?? 0);
            if ($question && $opt1 && $opt2 && $opt3 && $opt4 && $correct >=0 && $correct <=3) {
                $options = json_encode([$opt1,$opt2,$opt3,$opt4], JSON_UNESCAPED_UNICODE);
                $stmt = $pdo->prepare('INSERT INTO tests (question, options, correct_answer) VALUES (?, ?, ?)');
                $stmt->execute([$question, $options, $correct]);
            }
            header('Location: /tests.php'); exit;
        }
        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $question = trim($_POST['question'] ?? '');
            $opt1 = trim($_POST['opt1'] ?? '');
            $opt2 = trim($_POST['opt2'] ?? '');
            $opt3 = trim($_POST['opt3'] ?? '');
            $opt4 = trim($_POST['opt4'] ?? '');
            $correct = (int)($_POST['correct'] ?? 0);
            if ($id>0 && $question && $opt1 && $opt2 && $opt3 && $opt4 && $correct >=0 && $correct <=3) {
                $options = json_encode([$opt1,$opt2,$opt3,$opt4], JSON_UNESCAPED_UNICODE);
                $stmt = $pdo->prepare('UPDATE tests SET question = ?, options = ?, correct_answer = ? WHERE id = ?');
                $stmt->execute([$question, $options, $correct, $id]);
            }
            header('Location: /tests.php'); exit;
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id>0) { $pdo->prepare('DELETE FROM tests WHERE id = ?')->execute([$id]); }
            header('Location: /tests.php'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тесты</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    </style>
</head>
<body class="font-sans">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mx-auto p-6">
        <h2 class="text-3xl font-bold text-indigo-700 mb-6 text-center">Тесты</h2>
        <?php if (isLoggedIn() && currentUser()['role'] === 'teacher'): ?>
        <section class="bg-white rounded-xl shadow p-5 mb-8">
            <h3 class="text-xl font-semibold text-indigo-700 mb-3">Добавить тест</h3>
            <form method="POST" class="grid md:grid-cols-2 gap-3">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input class="border border-indigo-200 rounded p-2" name="question" placeholder="Вопрос" required>
                <input class="border border-indigo-200 rounded p-2" name="opt1" placeholder="Вариант 1" required>
                <input class="border border-indigo-200 rounded p-2" name="opt2" placeholder="Вариант 2" required>
                <input class="border border-indigo-200 rounded p-2" name="opt3" placeholder="Вариант 3" required>
                <input class="border border-indigo-200 rounded p-2" name="opt4" placeholder="Вариант 4" required>
                <input class="border border-indigo-200 rounded p-2" type="number" min="0" max="3" name="correct" placeholder="Правильный индекс (0-3)" required>
                <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Создать</button>
            </form>
        </section>
        <?php endif; ?>
        <div class="space-y-6">
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM tests");
                while ($test = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $options = json_decode($test['options'], true);
                    echo '
                    <div class="bg-white rounded-lg shadow-lg p-4">
                        <h3 class="text-xl font-semibold text-indigo-600">' . htmlspecialchars($test['question']) . '</h3>
                        <form action="tests.php" method="POST">
                            <input type="hidden" name="action" value="answer">
                            <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">
                            <input type="hidden" name="test_id" value="' . $test['id'] . '">';
                            foreach ($options as $index => $option) {
                                echo '
                            <div class="mb-2">
                                <input type="radio" name="answer" value="' . $index . '" id="option' . $test['id'] . '_' . $index . '" required>
                                <label for="option' . $test['id'] . '_' . $index . '">' . htmlspecialchars($option) . '</label>
                            </div>';
                            }
                            echo '
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Ответить</button>
                        </form>';
                    if (isLoggedIn() && currentUser()['role'] === 'teacher') {
                        echo '<form method="POST" class="mt-3" onsubmit="return confirm(\'Удалить тест?\');">'
                           . '<input type="hidden" name="action" value="delete">'
                           . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">'
                           . '<input type="hidden" name="id" value="' . (int)$test['id'] . '">'
                           . '<button class="text-red-600 hover:underline text-sm">Удалить</button>'
                           . '</form>';
                        echo '<details class="mt-2">'
                           . '<summary class="cursor-pointer text-indigo-600 text-sm">Редактировать</summary>'
                           . '<form method="POST" class="mt-2 grid md:grid-cols-2 gap-2">'
                           . '<input type="hidden" name="action" value="update">'
                           . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">'
                           . '<input type="hidden" name="id" value="' . (int)$test['id'] . '">'
                           . '<input class="border border-indigo-200 rounded p-2" name="question" value="' . htmlspecialchars($test['question']) . '" required>'
                           . '<input class="border border-indigo-200 rounded p-2" name="opt1" value="' . htmlspecialchars($options[0] ?? '') . '" required>'
                           . '<input class="border border-indigo-200 rounded p-2" name="opt2" value="' . htmlspecialchars($options[1] ?? '') . '" required>'
                           . '<input class="border border-indigo-200 rounded p-2" name="opt3" value="' . htmlspecialchars($options[2] ?? '') . '" required>'
                           . '<input class="border border-indigo-200 rounded p-2" name="opt4" value="' . htmlspecialchars($options[3] ?? '') . '" required>'
                           . '<input class="border border-indigo-200 rounded p-2" type="number" min="0" max="3" name="correct" value="' . (int)$test['correct_answer'] . '" required>'
                           . '<button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Сохранить</button>'
                           . '</form>'
                           . '</details>';
                    }
                    echo '</div>';
                }
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $test_id = (int)($_POST['test_id'] ?? 0);
                    $answer = (int)($_POST['answer'] ?? -1);
                    $stmt = $pdo->prepare("SELECT correct_answer FROM tests WHERE id = ?");
                    $stmt->execute([$test_id]);
                    $correct = $stmt->fetchColumn();
                    echo '<p class="text-center mt-4 ' . ($answer == $correct ? 'text-green-500' : 'text-red-500') . '">';
                    echo ($answer == $correct) ? "Правильно!" : "Неправильно. Попробуйте еще!";
                    echo '</p>';
                }
            } catch (PDOException $e) {
                echo "<p class='text-red-500'>Ошибка: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>