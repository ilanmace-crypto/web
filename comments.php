<?php require_once __DIR__ . '/../includes/auth.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Комментарии</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mx-auto p-6">
        <h2 class="text-3xl font-bold text-indigo-700 mb-6 text-center">Комментарии</h2>
        <?php
        include __DIR__ . '/../includes/db_connect.php';
        $commentsPdo = connectToDb('comments.db');
        $modelsPdo = connectToDb('models.db');

        $modelId = isset($_GET['model_id']) ? (int)$_GET['model_id'] : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
            $modelId = (int)($_POST['model_id'] ?? 0);
            $commentText = trim($_POST['comment'] ?? '');
            if ($modelId > 0 && $commentText !== '') {
                $uid = isLoggedIn() ? (int)currentUser()['id'] : 0;
                $stmt = $commentsPdo->prepare("INSERT INTO comments (model_id, user_id, comment) VALUES (?, ?, ?)");
                $stmt->execute([$modelId, $uid, $commentText]);
            }
            header('Location: /comments.php?model_id=' . $modelId);
            exit;
        }

        if ($modelId) {
            $stmt = $modelsPdo->prepare("SELECT * FROM models WHERE id = ?");
            $stmt->execute([$modelId]);
            $model = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        ?>

        <?php if (!empty($model)) : ?>
            <div class="bg-white rounded-xl shadow p-4 mb-6">
                <div class="flex gap-4 items-center">
                    <img src="/images/<?php echo htmlspecialchars($model['image_path']); ?>" alt="<?php echo htmlspecialchars($model['name']); ?>" class="w-28 h-28 object-cover rounded">
                    <div>
                        <h3 class="text-xl font-semibold text-indigo-600"><?php echo htmlspecialchars($model['name']); ?></h3>
                        <p class="text-indigo-900/80"><?php echo htmlspecialchars($model['description']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($modelId): ?>
            <form action="/comments.php" method="POST" class="bg-white rounded-xl shadow p-4 mb-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="model_id" value="<?php echo $modelId; ?>">
                <label class="block mb-2 font-medium text-indigo-700">Ваш комментарий</label>
                <textarea name="comment" class="w-full border border-indigo-200 rounded p-3 focus:outline-none focus:ring-2 focus:ring-indigo-400" rows="3" placeholder="Напишите что-нибудь полезное..."></textarea>
                <button type="submit" class="mt-3 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Отправить</button>
            </form>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow p-4 mb-6">
                <p class="text-indigo-900/80">Выберите модель в <a class="text-indigo-600 hover:underline" href="/gallery.php">галерее</a>, чтобы оставить комментарий.</p>
            </div>
        <?php endif; ?>

        <?php if ($modelId): ?>
            <div class="space-y-4">
                <?php
                $stmt = $commentsPdo->prepare("SELECT * FROM comments WHERE model_id = ? ORDER BY timestamp DESC");
                $stmt->execute([$modelId]);
                while ($comment = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div class="bg-white rounded-lg shadow p-4">'
                        . '<div class="text-indigo-900/80">' . htmlspecialchars($comment['comment']) . '</div>'
                        . '<div class="text-xs text-indigo-900/60 mt-2">' . htmlspecialchars($comment['timestamp']) . '</div>'
                        . '</div>';
                }
                ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

