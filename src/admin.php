<?php
require_once 'config.php';
session_start();
require_once 'db_connect.php';

// Проверка: только админ
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] ?? null) !== 1) {
    header('Location: index.php');
    exit;
}

try {
    // Все объявления
    $sql = "
        SELECT ads.*, users.user_name
        FROM ads
        JOIN users ON ads.user_id = users.user_id
        ORDER BY ads.is_verified ASC, ads.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $ads = $stmt->fetchAll();

} catch (PDOException $e) {
    $ads = [];
    error_log($e->getMessage());
}
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
    <div class="container header-top">
        <span class="user-welcome">
            Администратор: <?= htmlspecialchars($_SESSION['user_name']) ?>
        </span>
        <div>
            <a href="index.php" class="auth-btn">На сайт</a>
            <a href="logout.php" class="auth-btn logout-btn">Выход</a>
        </div>
    </div>
</header>

<main class="main">
    <div class="container">
        <h1 class="section-title">Модерация объявлений</h1>

        <?php if (empty($ads)): ?>
            <p>Объявлений нет</p>
        <?php else: ?>
            <table border="1" cellpadding="10" cellspacing="0" width="100%">
                <tr>
                    <th>ID</th>
                    <th>Заголовок</th>
                    <th>Автор</th>
                    <th>Цена</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>

                <?php foreach ($ads as $ad): ?>
                    <tr>
                        <td><?= $ad['ads_id'] ?></td>
                        <td><?= htmlspecialchars($ad['ads_title']) ?></td>
                        <td><?= htmlspecialchars($ad['user_name']) ?></td>
                        <td><?= number_format($ad['ads_price'], 0, '', ' ') ?> ₽</td>
                        <td>
                            <?= $ad['is_verified'] ? '🟢 Одобрено' : '🔴 На модерации' ?>
                        </td>
                        <td>
                            <a href="detail.php?id=<?= $ad['ads_id'] ?>">Просмотр</a>
                            <?php if (!$ad['is_verified']): ?>
                                | <a href="approve_ad.php?id=<?= $ad['ads_id'] ?>">Одобрить</a>
                            <?php endif; ?>
                            | <a href="delete_ad.php?id=<?= $ad['ads_id'] ?>"
                                 onclick="return confirm('Удалить объявление?')">
                                Удалить
                              </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
