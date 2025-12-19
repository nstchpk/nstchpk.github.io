<?php
// header.php - НЕ объявляйте переменные здесь, они уже есть в init.php
// УДАЛИТЕ эти строки:
// $user_id = $_SESSION['user_id'] ?? null;
// $user_name = $_SESSION['user_name'] ?? '';
// $role_id = $_SESSION['role_id'] ?? null;

// Просто используйте переменные из init.php
?>

<header class="header">
    <div class="container header-top">
        <a href="index.php" class="logo">
            <img src="images/logoo.svg" alt="Логотип" class="logo-image">
            <span class="logo-text">Объявления</span>
        </a>

        <?php if ($is_logged_in): ?>
            <div class="user-block">
                <span class="user-welcome">Здравствуйте, <?= htmlspecialchars($user_name) ?></span>

                <?php if ($is_admin): ?>
                    <a href="admin.php" class="admin-panel-link">👑 Админка</a>
                <?php endif; ?>

                <a href="add_ad.php" class="add-btn">
                    <span class="add-icon">＋</span>
                    <span class="add-text">Добавить</span>
                </a>

                <a href="logout.php" class="logout-link">Выход</a>
            </div>
        <?php else: ?>
            <div class="auth-buttons">
                <button class="auth-link" type="button" onclick="openModal('login')">
                    Вход
                </button>
                <button class="auth-link" type="button" onclick="openModal('register')">
                    Регистрация
                </button>
            </div>
        <?php endif; ?>
    </div>
</header>