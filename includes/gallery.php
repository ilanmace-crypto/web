<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$modelsPdo = connectToDb('models.db');
$commentsPdo = connectToDb('comments.db');
$ratingsPdo = connectToDb('polls.db');
$csrfToken = $_SESSION['csrf_token'] ?? '';
try {
    $ratingsPdo->exec("CREATE TABLE IF NOT EXISTS ratings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        model_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        stars INTEGER NOT NULL CHECK(stars BETWEEN 1 AND 5),
        UNIQUE(model_id, user_id)
    )");
} catch (Throwable $e) { /* ignore */ }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rate') {
    if (isLoggedIn()) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $modelId = (int)($_POST['model_id'] ?? 0);
        $stars = (int)($_POST['stars'] ?? 0);
        if ($modelId > 0 && $stars >= 1 && $stars <= 5) {
            $stmt = $ratingsPdo->prepare('INSERT INTO ratings (model_id, user_id, stars) VALUES (?, ?, ?) ON CONFLICT(model_id, user_id) DO UPDATE SET stars = excluded.stars');
            $stmt->execute([$modelId, currentUser()['id'], $stars]);
        }
    }
    header('Location: /gallery.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'comment') {
    if (isLoggedIn()) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $modelId = (int)($_POST['model_id'] ?? 0);
        $commentText = trim($_POST['comment'] ?? '');
        if ($modelId > 0 && $commentText !== '') {
            $stmt = $commentsPdo->prepare('INSERT INTO comments (model_id, user_id, comment) VALUES (?, ?, ?)');
            $stmt->execute([$modelId, currentUser()['id'], $commentText]);
        }
    }
    header('Location: /gallery.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_comment') {
    if (isLoggedIn() && in_array(currentUser()['role'], ['admin','teacher'])) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $commentId = (int)($_POST['comment_id'] ?? 0);
        if ($commentId > 0) {
            $commentsPdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$commentId]);
        }
    }
    header('Location: /gallery.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_model') {
    if (isLoggedIn() && in_array(currentUser()['role'], ['admin','teacher'])) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
        $modelId = (int)($_POST['model_id'] ?? 0);
        if ($modelId > 0) {
            $m = $modelsPdo->prepare('SELECT file_path, image_path FROM models WHERE id = ?');
            $m->execute([$modelId]);
            $model = $m->fetch(PDO::FETCH_ASSOC);
            if ($model) {
                @unlink(__DIR__ . '/uploads/' . $model['file_path']);
                @unlink(__DIR__ . '/images/' . $model['image_path']);
                $modelsPdo->prepare('DELETE FROM models WHERE id = ?')->execute([$modelId]);
                $commentsPdo->prepare('DELETE FROM comments WHERE model_id = ?')->execute([$modelId]);
                $ratingsPdo->prepare('DELETE FROM ratings WHERE model_id = ?')->execute([$modelId]);
            }
        }
    }
    header('Location: /gallery.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Галерея моделей</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .models-grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 1.5rem !important;
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.model-3d-container').forEach(container => {
            const btn = container.querySelector('.view-3d-btn');
            if (btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const modelPath = container.dataset.model;
                    const modelName = container.dataset.name;
                    
                    container.innerHTML = '<div class="flex items-center justify-center h-full"><div class="text-center"><div class="text-4xl mb-2">⏳</div><div class="text-sm text-indigo-600">Загрузка 3D...</div></div></div>';
                    
                    setTimeout(() => {
                        const viewerId = 'mv_' + Math.random().toString(36).slice(2);
                        container.innerHTML = `
                          <div class="w-full h-full flex flex-col">
                            <model-viewer id="${viewerId}" src="${modelPath}" alt="${modelName}" camera-controls autoplay animation-loop ar ar-modes="webxr scene-viewer quick-look" style="width:100%;height:100%;background:linear-gradient(135deg, #f8fafc, #e2e8f0);"></model-viewer>
                            <div class="mt-2 flex items-center gap-2 text-xs">
                              <button class="px-2 py-1 rounded border border-indigo-200 hover:bg-indigo-50" data-act="play">▶️ Пуск</button>
                              <button class="px-2 py-1 rounded border border-indigo-200 hover:bg-indigo-50" data-act="pause">⏸ Пауза</button>
                              <label>Анимация:</label>
                              <select class="border border-indigo-200 rounded p-1" data-role="animSel"><option value="">—</option></select>
                            </div>
                          </div>`;
                        const mv = container.querySelector('#' + viewerId);
                        const sel = container.querySelector('[data-role="animSel"]');
                        const onLoad = () => {
                          try {
                            const anims = mv.availableAnimations || [];
                            if (anims && anims.length) {
                              sel.innerHTML = '<option value="">Все</option>' + anims.map(n => `<option value="${n}">${n}</option>`).join('');
                              sel.addEventListener('change', () => {
                                const name = sel.value;
                                if (name) mv.animationName = name; else mv.animationName = null;
                                if (typeof mv.play === 'function') mv.play();
                              });
                            } else {
                              sel.disabled = true;
                            }
                          } catch(e) { sel.disabled = true; }
                        };
                        mv.addEventListener('load', onLoad, { once: true });
                        const onBtn = (e) => {
                          const act = e.target.getAttribute('data-act');
                          if (!act) return;
                          if (act === 'play' && typeof mv.play === 'function') mv.play();
                          if (act === 'pause' && typeof mv.pause === 'function') mv.pause();
                        };
                        container.addEventListener('click', onBtn);
                    }, 100);
                });
            }
        });
    });
    </script>
</head>
<body class="font-sans">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mx-auto p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-3xl font-bold text-indigo-700">Галерея моделей</h2>
            <?php if (isLoggedIn()): ?>
            <a href="/upload.php" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-800">Загрузить модель</a>
            <?php else: ?>
            <a href="/login.php" class="bg-white border border-indigo-200 text-indigo-700 px-4 py-2 rounded hover:bg-indigo-50">Войти, чтобы загружать</a>
            <?php endif; ?>
        </div>

        <form method="GET" class="bg-white rounded-xl shadow p-4 mb-6 grid md:grid-cols-5 gap-3">
            <input class="border border-indigo-200 rounded p-2" type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" placeholder="Поиск по названию">
            <select class="border border-indigo-200 rounded p-2" name="sort">
                <?php $s = $_GET['sort'] ?? 'date_desc'; ?>
                <option value="date_desc" <?php echo $s==='date_desc'?'selected':''; ?>>Сначала новые</option>
                <option value="date_asc" <?php echo $s==='date_asc'?'selected':''; ?>>Сначала старые</option>
                <option value="rating_desc" <?php echo $s==='rating_desc'?'selected':''; ?>>По рейтингу</option>
            </select>
            <select class="border border-indigo-200 rounded p-2" name="owner">
                <?php $o = $_GET['owner'] ?? 'all'; ?>
                <option value="all" <?php echo $o==='all'?'selected':''; ?>>Все</option>
                <option value="mine" <?php echo $o==='mine'?'selected':''; ?>>Мои</option>
            </select>
            <select class="border border-indigo-200 rounded p-2" name="type">
                <?php $t = $_GET['type'] ?? 'all'; ?>
                <option value="all" <?php echo $t==='all'?'selected':''; ?>>Все типы</option>
                <option value="3d" <?php echo $t==='3d'?'selected':''; ?>>Только 3D</option>
                <option value="image" <?php echo $t==='image'?'selected':''; ?>>Только изображения</option>
            </select>
            <button class="bg-indigo-600 text-white px-4 py-2 rounded">Применить</button>
        </form>

        <div class="models-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
            <?php
            try {
                $where = [];
                $params = [];
                if (!empty($_GET['q'])) { $where[] = 'm.name LIKE ?'; $params[] = '%' . $_GET['q'] . '%'; }
                if (!empty($_GET['owner']) && $_GET['owner'] === 'mine' && isLoggedIn()) { $where[] = 'm.uploader_id = ?'; $params[] = (int)currentUser()['id']; }
                // Фильтр по типу
                $type = $_GET['type'] ?? 'all';
                if ($type === '3d') {
                    $where[] = "(m.file_path LIKE '%.glb' OR m.file_path LIKE '%.gltf')";
                } elseif ($type === 'image') {
                    $where[] = "(m.file_path NOT LIKE '%.glb' AND m.file_path NOT LIKE '%.gltf')";
                }
                $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
                $order = 'm.id DESC';
                $sort = $_GET['sort'] ?? 'date_desc';
                if ($sort === 'date_asc') { $order = 'm.id ASC'; }

                // Формируем SQL с учётом сортировки по рейтингу
                if ($sort === 'rating_desc') {
                    $sql = "SELECT m.*, IFNULL(r.avg_rating, 0) AS rating_avg
                            FROM models m
                            LEFT JOIN (
                                SELECT model_id, AVG(stars) AS avg_rating
                                FROM ratings
                                GROUP BY model_id
                            ) r ON r.model_id = m.id
                            $whereSql
                            ORDER BY rating_avg DESC, m.id DESC";
                } else {
                    $sql = "SELECT m.* FROM models m $whereSql ORDER BY $order";
                }

                $stmt = $modelsPdo->prepare($sql);
                $stmt->execute($params);
                while ($model = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $avg = $ratingsPdo->prepare('SELECT ROUND(AVG(stars),1) as avg, COUNT(*) as cnt FROM ratings WHERE model_id = ?');
                    $avg->execute([$model['id']]);
                    $row = $avg->fetch(PDO::FETCH_ASSOC) ?: ['avg'=>null,'cnt'=>0];
                    $avgText = $row['avg'] ? ($row['avg'] . ' / 5 (' . $row['cnt'] . ')') : 'нет оценок';

                    $cstmt = $commentsPdo->prepare('SELECT id, comment, timestamp FROM comments WHERE model_id = ? ORDER BY timestamp DESC LIMIT 3');
                    $cstmt->execute([$model['id']]);
                    $comments = $cstmt->fetchAll(PDO::FETCH_ASSOC);

                    echo '<div class="bg-white rounded-xl shadow-lg overflow-hidden relative">';
                    $isPreview3D = preg_match('/\.(glb|gltf)$/i', $model['file_path']);
                    if ($isPreview3D) {
                        echo '<script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>';
                        echo '<div class="relative w-full h-60 bg-gradient-to-br from-indigo-50 to-purple-50 flex items-center justify-center group cursor-pointer model-3d-container" data-model="/uploads/' . htmlspecialchars($model['file_path']) . '" data-name="' . htmlspecialchars($model['name']) . '">';
                        echo '<span class="absolute top-2 left-2 text-[10px] bg-indigo-600 text-white px-2 py-1 rounded z-10">3D Model</span>';
                        echo '<div class="text-center">';
                        echo '<div class="text-6xl mb-3">🎨</div>';
                        echo '<button class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-all shadow-lg view-3d-btn">Показать 3D</button>';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        $imgSrc = 'https://via.placeholder.com/600x300?text=No+Preview';
                        if (!empty($model['image_path'])) {
                            $abs = __DIR__ . '/images/' . $model['image_path'];
                            if (file_exists($abs) && @filesize($abs) > 0) {
                                $imgSrc = '/images/' . htmlspecialchars($model['image_path']);
                            }
                        }
                        echo '<img src="' . $imgSrc . '" alt="' . htmlspecialchars($model['name']) . '" class="w-full h-48 object-cover" loading="lazy">';
                    }
                    echo '<div class="p-4">';
                    echo '<h3 class="text-xl font-semibold text-indigo-600">' . htmlspecialchars($model['name']) . '</h3>';
                    if (!empty($model['tags'])) {
                        $tagsArr = array_filter(array_map('trim', explode(',', $model['tags'])));
                        if ($tagsArr) {
                            echo '<div class="mt-2 text-xs text-indigo-700/80 flex flex-wrap gap-1">';
                            foreach ($tagsArr as $tg) {
                                echo '<span class="px-2 py-0.5 rounded-full border border-indigo-200 bg-indigo-50">' . htmlspecialchars($tg) . '</span>';
                            }
                            echo '</div>';
                        }
                    }
                    echo '<p class="text-indigo-900/80 mt-1">' . htmlspecialchars($model['description']) . '</p>';
                    echo '<div class="mt-2 text-sm text-indigo-900/70">Рейтинг: ' . htmlspecialchars($avgText) . '</div>';
                    echo '<div class="mt-3 flex gap-3 items-center">';
                    echo '<a href="/uploads/' . htmlspecialchars($model['file_path']) . '" class="text-indigo-600 hover:underline">Скачать модель</a>';
                    if (isLoggedIn() && in_array(currentUser()['role'], ['admin','teacher'])) {
                        echo '<form method="POST" action="/gallery.php" onsubmit="return confirm(\'Удалить модель?\');">';
                        echo '<input type="hidden" name="action" value="delete_model">';
                        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
                        echo '<input type="hidden" name="model_id" value="' . (int)$model['id'] . '">';
                        echo '<button class="text-red-600 hover:underline">Удалить</button>';
                        echo '</form>';
                    }
                    echo '</div>';

                    echo '<div class="mt-4">';
                    if (isLoggedIn()) {
                        echo '<form method="POST" class="flex items-center gap-2">';
                        echo '<input type="hidden" name="action" value="rate">';
                        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
                        echo '<input type="hidden" name="model_id" value="' . (int)$model['id'] . '">';
                        for ($i=1; $i<=5; $i++) {
                            echo '<button name="stars" value="' . $i . '" class="px-2 py-1 rounded border border-indigo-200 hover:bg-indigo-50">' . str_repeat('★', $i) . '</button>';
                        }
                        echo '</form>';
                    } else {
                        echo '<a class="text-indigo-600 hover:underline" href="/login.php">Войдите, чтобы оценить</a>';
                    }
                    echo '</div>';

                    try {
                        if (!empty($model['tags'])) {
                            $tagsArr = array_slice(array_filter(array_map('trim', explode(',', $model['tags']))), 0, 3);
                            if ($tagsArr) {
                                $likeW = [];
                                $wParams = [];
                                foreach ($tagsArr as $tg) { $likeW[] = 'tags LIKE ?'; $wParams[] = '%' . $tg . '%'; }
                                $wSql = implode(' OR ', $likeW);
                                $sim = $modelsPdo->prepare("SELECT id, name, file_path FROM models WHERE id <> ? AND ($wSql) ORDER BY id DESC LIMIT 3");
                                $sim->execute(array_merge([(int)$model['id']], $wParams));
                                $similar = $sim->fetchAll(PDO::FETCH_ASSOC);
                                if ($similar) {
                                    echo '<div class="mt-4">';
                                    echo '<div class="text-sm text-indigo-900/70 mb-2">Похожие модели:</div>';
                                    echo '<div class="flex flex-wrap gap-2">';
                                    foreach ($similar as $sm) {
                                        $is3d = preg_match('/\.(glb|gltf)$/i', $sm['file_path']);
                                        $badge = $is3d ? '3D' : 'IMG';
                                        echo '<a class="text-xs px-2 py-1 rounded border border-indigo-200 hover:bg-indigo-50" href="/gallery.php?q=' . urlencode($sm['name']) . '">' . htmlspecialchars($sm['name']) . ' <span class="text-[10px] text-indigo-500">' . $badge . '</span></a>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                }
                            }
                        }
                    } catch (Throwable $e) { /* ignore */ }

                    echo '<div class="mt-4">';
                    echo '<div class="text-sm text-indigo-900/70 mb-2">Последние комментарии:</div>';
                    if ($comments) {
                        foreach ($comments as $c) {
                            echo '<div class="text-sm bg-indigo-50/40 border border-indigo-100 rounded p-2 mb-2 flex items-start justify-between gap-2">';
                            echo '<div>' . htmlspecialchars($c['comment']) . '<div class="text-[10px] text-indigo-900/50 mt-1">' . htmlspecialchars($c['timestamp']) . '</div></div>';
                            if (isLoggedIn() && in_array(currentUser()['role'], ['admin','teacher'])) {
                                echo '<form method="POST" action="/gallery.php" onsubmit="return confirm(\'Удалить комментарий?\');">';
                                echo '<input type="hidden" name="action" value="delete_comment">';
                                echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
                                echo '<input type="hidden" name="comment_id" value="' . (int)$c['id'] . '">';
                                echo '<button class="text-red-600 hover:underline text-xs">Удалить</button>';
                                echo '</form>';
                            }
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="text-sm text-indigo-900/60">Комментариев пока нет</div>';
                    }
                    if (isLoggedIn()) {
                        echo '<form method="POST" class="mt-2">';
                        echo '<input type="hidden" name="action" value="comment">';
                        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
                        echo '<input type="hidden" name="model_id" value="' . (int)$model['id'] . '">';
                        echo '<textarea name="comment" rows="2" class="w-full border border-indigo-200 rounded p-2" placeholder="Напишите комментарий..."></textarea>';
                        echo '<button class="mt-2 bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-800">Отправить</button>';
                        echo '</form>';
                    } else {
                        echo '<a class="text-indigo-600 hover:underline" href="/login.php">Войдите, чтобы комментировать</a>';
                    }
                    echo '</div>'; // закрываем mt-4 (комментарии)
                    echo '</div>'; // закрываем p-4 (контент карточки)
                    echo '</div>'; // закрываем bg-white (саму карточку)
                }
                echo '</div>'; // закрываем grid
            } catch (PDOException $e) {
                echo "<p class='text-red-500'>Ошибка: " . $e->getMessage() . "</p>";
            }
            ?>
        
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

