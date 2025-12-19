<?php
require_once 'config.php';
session_start();
require_once 'db_connect.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$role_id = $_SESSION['role_id'] ?? null;

// Определяем, является ли пользователь администратором
$is_admin = ($role_id === 1);

// Только администратор
if (!$is_admin) {
    header('Location: index.php');
    exit;
}

// Статистика
$total_ads = $pdo->query("SELECT COUNT(*) FROM ads")->fetchColumn();
$pending_ads = $pdo->query("SELECT COUNT(*) FROM ads WHERE is_verified = 0")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_responses = $pdo->query("SELECT COUNT(*) FROM responses")->fetchColumn();

// Объявления
// Обновите SQL запрос:
$sql = "
SELECT ads.*, users.user_name, category.name_category,
       COUNT(responses.responses_id) AS response_count
FROM ads
JOIN users ON users.user_id = ads.user_id
LEFT JOIN category ON ads.category_id = category.id_category  -- Добавляем
LEFT JOIN responses ON responses.ads_id = ads.ads_id
GROUP BY ads.ads_id
ORDER BY ads.created_at DESC
";
$ads = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <img src="images/logoo.svg" alt="Логотип сайта" class="logo-image">
                    <span class="logo-text">Объявления</span>
                </div>

                <div class="auth-buttons">
                    <?php if ($is_logged_in): ?>
                        <?php if (($role_id ?? null) === 1): ?>
                            <!-- Кнопка админ-панели только для администратора -->
                            <a href="admin.php" class="admin-panel-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4" />
                                </svg>
                                <span>Админ-панель</span>
                            </a>
                        <?php endif; ?>
                        <span class="user-welcome">Здравствуйте, <?= htmlspecialchars($user_name) ?></span>
                        <a href="logout.php" class="logout-link">Выход</a>
                    <?php else: ?>
                        <button class="auth-link" onclick="openModal('register')">Регистрация</button>
                        <button class="auth-link" onclick="openModal('login')">Вход</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="container" style="padding: 40px 0;">
        <h1 class="section-title">Панель управления</h1>

        <!-- Статистика -->
        <div class="ads-grid">
            <div class="ad-card">
                <div class="ad-price"><?= $total_ads ?></div>
                <div class="ad-title">Всего объявлений</div>
            </div>
            <div class="ad-card">
                <div class="ad-price"><?= $pending_ads ?></div>
                <div class="ad-title">На модерации</div>
            </div>
            <div class="ad-card">
                <div class="ad-price"><?= $total_users ?></div>
                <div class="ad-title">Пользователей</div>
            </div>
            <div class="ad-card">
                <div class="ad-price"><?= $total_responses ?></div>
                <div class="ad-title">Откликов</div>
            </div>
        </div>

        <!-- Таблица -->
        <div class="description-card">
            <h3 style="margin-bottom: 20px;">Все объявления</h3>

            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #eee;">
                        <th>ID</th>
                        <th>Заголовок</th>
                        <th>Автор</th>
                        <th>Цена</th>
                        <th>Отклики</th>
                        <th>Категория</th> <!-- Здесь -->
                        <th>Статус</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ads as $ad): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td>#<?= $ad['ads_id'] ?></td>
                            <td>
                                <a href="detail.php?id=<?= $ad['ads_id'] ?>">
                                    <?= htmlspecialchars($ad['ads_title']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($ad['user_name']) ?></td>
                            <td><?= number_format($ad['ads_price'], 0, '', ' ') ?> ₽</td>
                            <td><?= $ad['response_count'] ?></td>
                            <td> <!-- Здесь -->
                                <?php if (!empty($ad['name_category'])): ?>
                                    <span class="category-badge"><?= htmlspecialchars($ad['name_category']) ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">Не указана</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ad['is_verified']): ?>
                                    <span class="ad-status approved">Одобрено</span>
                                <?php else: ?>
                                    <span class="ad-status pending">На модерации</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d.m.Y', strtotime($ad['created_at'])) ?></td>
                            <td>
                                <a href="detail.php?id=<?= $ad['ads_id'] ?>">👁</a>
                                <?php if (!$ad['is_verified']): ?>
                                    <a href="approve_ad.php?id=<?= $ad['ads_id'] ?>">✔</a>
                                <?php endif; ?>
                                <a href="delete_ad.php?id=<?= $ad['ads_id'] ?>" onclick="return confirm('Удалить?')">🗑</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="footer">
        <div class="container footer-inner">
            <div class="footer-email">info@gmail.com</div>
            <div class="footer-links">
                <a href="#">Информация о разработчике</a>
            </div>
        </div>
    </footer>
</body>

</html>