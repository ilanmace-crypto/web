<?php
$currentPage = basename($_SERVER['REQUEST_URI'], '.php');
if (empty($currentPage) || $currentPage === 'index') $currentPage = 'index';
require_once __DIR__ . '/auth.php';
if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: no-referrer-when-downgrade");
    header("X-Frame-Options: DENY");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    $csp = "default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "img-src 'self' data: https://via.placeholder.com; "
         . "font-src 'self' data: https://fonts.gstatic.com; "
         . "object-src 'none'; base-uri 'self'; frame-ancestors 'none'";
    header("Content-Security-Policy: $csp");
}
?>
<style>
body {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #dee2e6 100%);
    color: #1e293b;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
}
.dark body {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: #f1f5f9;
}
.dark .nav-link:hover { background-color: #4f46e5; color: #ffffff; }
.nav-link {
    display: inline-block !important;
    transition: all 0.3s ease !important;
    border-radius: 8px !important;
    font-weight: 500 !important;
    text-decoration: none !important;
    cursor: pointer !important;
}
.nav-link:hover {
    background-color: rgba(255, 255, 255, 0.2) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    text-decoration: none !important;
}
.nav-link.active {
    background: linear-gradient(135deg, #4f46e5, #7c3aed) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3) !important;
}
.dark .nav-link.active {
    background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
}
.header-gradient {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(226, 232, 240, 0.8);
    position: sticky;
    top: 0;
    z-index: 1000;
}
.dark .header-gradient {
    background: rgba(15, 23, 42, 0.98);
    border-bottom: 1px solid rgba(51, 65, 85, 0.8);
    position: sticky;
    top: 0;
    z-index: 1000;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}
.card {
    background: rgba(255, 255, 255, 0.98);
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid rgba(226, 232, 240, 0.8);
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}
.dark .card {
    background: rgba(30, 41, 59, 0.95);
    border: 1px solid rgba(51, 65, 85, 0.8);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
}
.dark .card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
}
.btn-primary {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #3730a3, #6d28d9);
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
}
.btn-secondary {
    background: rgba(255, 255, 255, 0.9);
    color: #4f46e5;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid rgba(79, 70, 229, 0.2);
}
.btn-secondary:hover {
    background: white;
    color: #3730a3;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}
.text-gradient {
    background: linear-gradient(135deg, #4f46e5, #7c3aed, #ec4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.dark .text-gradient {
    background: linear-gradient(135deg, #818cf8, #a78bfa, #f472b6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.dark .text-gradient {
    background: linear-gradient(135deg, #8b5cf6, #a855f7, #f472b6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
@media (max-width: 768px) {
    .container {
        padding: 0 0.5rem;
    }
    .nav {
        flex-wrap: wrap;
        gap: 0.25rem;
        justify-content: center;
        align-items: center;
    }
    .nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        text-align: center;
        min-width: fit-content;
        white-space: nowrap;
    }
    .text-2xl {
        font-size: 1.5rem;
    }
    .text-5xl {
        font-size: 2.5rem;
    }
    .text-6xl {
        font-size: 3rem;
    }
    .grid-cols-3 {
        grid-template-columns: 1fr;
    }
    .grid-cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }
    .flex-row {
        flex-direction: column;
    }
    .justify-between {
        justify-content: center;
        text-align: center;
    }
    .mb-8 {
        margin-bottom: 2rem;
    }
    .p-8 {
        padding: 1.5rem;
    }
    .gap-4 {
        gap: 0.75rem;
    }
    .rounded-xl {
        border-radius: 0.75rem;
    }
    .shadow-lg {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
}
@media (max-width: 480px) {
    .text-3xl {
        font-size: 1.5rem;
    }
    .text-4xl {
        font-size: 2rem;
    }
    .nav-link {
        padding: 0.375rem 0.5rem;
        font-size: 0.75rem;
    }
    .container {
        padding: 0 0.25rem;
    }
}
</style>
<header class="header-gradient shadow-sm">
    <div class="container mx-auto px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button id="menuToggle" aria-label="Открыть меню" class="md:hidden nav-link px-2 py-2">☰</button>
            <a href="/index.php" class="text-2xl font-extrabold text-gradient">3D Лаборатория</a>
        </div>
        <nav class="nav hidden md:flex items-center gap-2">
            <a href="/index.php" class="nav-link px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === 'index' ? 'active' : ''; ?>">Главная</a>
            <a href="/gallery.php" class="nav-link px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === 'gallery' ? 'active' : ''; ?>">Модели</a>
            <a href="/lectures.php" class="nav-link px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === 'lectures' ? 'active' : ''; ?>">Лекции</a>
            <a href="/resources.php" class="nav-link px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === 'resources' ? 'active' : ''; ?>">Ресурсы</a>
            <div class="relative" id="moreWrapper">
                <button id="moreMenuBtn" class="nav-link px-3 py-2 rounded-md text-sm font-medium" aria-haspopup="true" aria-expanded="false">Еще ▾</button>
                <div id="moreMenu" class="absolute right-0 mt-2 hidden z-50">
                    <div class="card p-2 min-w-[200px]">
                        <?php if (isLoggedIn() && in_array(currentUser()['role'], ['teacher','admin'])): ?>
                        <a href="/ktp.php" class="block px-3 py-2 rounded-md text-sm hover:bg-indigo-50 font-semibold text-indigo-700">📋 КТП</a>
                        <div class="border-t border-indigo-100 my-1"></div>
                        <?php endif; ?>
                        <a href="/tests.php" class="block px-3 py-2 rounded-md text-sm hover:bg-indigo-50">Тесты</a>
                        <a href="/polls.php" class="block px-3 py-2 rounded-md text-sm hover:bg-indigo-50">Опросы</a>
                        <a href="/labs.php" class="block px-3 py-2 rounded-md text-sm hover:bg-indigo-50">Лабы</a>
                        <a href="/documents.php" class="block px-3 py-2 rounded-md text-sm hover:bg-indigo-50">Документы</a>
                        <a href="/about.php" class="block px-3 py-2 rounded-md text-sm hover:bg-indigo-50">О сайте</a>
                        <a href="/contact.php" class="block px-3 py-2 rounded-md text-sm hover:bg-indigo-50">Контакты</a>
                    </div>
                </div>
            </div>
            <?php
            if (isLoggedIn()) {
                $role = currentUser()['role'];
                if (in_array($role, ['teacher','admin'])) {
                    echo '<a href="/teacher.php" class="nav-link px-3 py-2 rounded-md text-sm font-medium">Кабинет</a>';
                } else {
                    echo '<a href="/student.php" class="nav-link px-3 py-2 rounded-md text-sm font-medium">Кабинет</a>';
                }
                echo '<a href="/logout.php" class="nav-link px-3 py-2 rounded-md text-sm font-medium">Выйти</a>';
            } else {
                echo '<a href="/login.php" class="nav-link px-3 py-2 rounded-md text-sm font-medium">Войти</a> ';
                echo '<a href="/register.php" class="nav-link px-3 py-2 rounded-md text-sm font-medium">Регистрация</a>';
            }
            ?>
            <button id="themeToggle" class="ml-1 px-3 py-2 rounded-md text-sm font-medium border border-indigo-200 text-indigo-700 hover:bg-indigo-50 dark:border-indigo-500/40 dark:text-indigo-200 dark:hover:bg-white/10 transition-all whitespace-nowrap">Тема</button>
        </nav>
    </div>
    
    <div id="mobileMenu" class="md:hidden hidden border-t border-white/20">
        <div class="container mx-auto px-6 py-3 grid grid-cols-2 gap-2">
            <a href="/index.php" class="nav-link px-3 py-2 rounded text-sm <?php echo $currentPage === 'index' ? 'active' : ''; ?>">Главная</a>
            <a href="/gallery.php" class="nav-link px-3 py-2 rounded text-sm <?php echo $currentPage === 'gallery' ? 'active' : ''; ?>">Модели</a>
            <a href="/lectures.php" class="nav-link px-3 py-2 rounded text-sm <?php echo $currentPage === 'lectures' ? 'active' : ''; ?>">Лекции</a>
            <a href="/resources.php" class="nav-link px-3 py-2 rounded text-sm <?php echo $currentPage === 'resources' ? 'active' : ''; ?>">Ресурсы</a>
            <a href="/tests.php" class="nav-link px-3 py-2 rounded text-sm">Тесты</a>
            <a href="/polls.php" class="nav-link px-3 py-2 rounded text-sm">Опросы</a>
            <a href="/labs.php" class="nav-link px-3 py-2 rounded text-sm">Лабы</a>
            <a href="/documents.php" class="nav-link px-3 py-2 rounded text-sm">Документы</a>
            <a href="/about.php" class="nav-link px-3 py-2 rounded text-sm">О сайте</a>
            <a href="/contact.php" class="nav-link px-3 py-2 rounded text-sm">Контакты</a>
        </div>
    </div>
    <div class="h-1 bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400"></div>
</header>
<script>
(function(){
  const root = document.documentElement;
  const saved = localStorage.getItem('theme');
  if (saved === 'dark') root.classList.add('dark');
  const btn = document.getElementById('themeToggle');
  if (btn) btn.addEventListener('click', function(){
    root.classList.toggle('dark');
    localStorage.setItem('theme', root.classList.contains('dark') ? 'dark' : 'light');
  });
  const mt = document.getElementById('menuToggle');
  const mm = document.getElementById('mobileMenu');
  if (mt && mm) mt.addEventListener('click', function(){ mm.classList.toggle('hidden'); });

  const moreBtn = document.getElementById('moreMenuBtn');
  const moreMenu = document.getElementById('moreMenu');
  if (moreBtn && moreMenu) {
    moreBtn.addEventListener('click', (e)=>{
      e.stopPropagation();
      moreMenu.classList.toggle('hidden');
      moreBtn.setAttribute('aria-expanded', moreMenu.classList.contains('hidden') ? 'false' : 'true');
    });
    document.addEventListener('click', (e)=>{
      if (!moreMenu.classList.contains('hidden')) {
        const wrap = document.getElementById('moreWrapper');
        if (wrap && !wrap.contains(e.target)) {
          moreMenu.classList.add('hidden');
          moreBtn.setAttribute('aria-expanded', 'false');
        }
      }
    });
  }
})();
</script>

