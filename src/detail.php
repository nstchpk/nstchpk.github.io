<?php
// Начинаем сессию и подключаем БД
require_once 'config.php';
require_once 'db_connect.php';

// Проверяем авторизацию
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$current_user_id = $_SESSION['user_id'] ?? null;

// Проверяем, передан ли ID объявления
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

try {
    // Получаем данные объявления
    $sql = "SELECT 
                ads.*, 
                users.user_name,
                users.user_phone,
                users.user_email
            FROM ads
            LEFT JOIN users ON ads.user_id = users.user_id
            WHERE ads.ads_id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $ad = $stmt->fetch();

    if (!$ad) {
        header("Location: index.php");
        exit();
    }

    // Проверяем, откликался ли текущий пользователь
    $has_responded = false;
    if ($current_user_id) {
        $check_sql = "SELECT responses_id FROM responses 
                      WHERE ads_id = :ad_id AND user_id = :user_id";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([
            ':ad_id' => $id,
            ':user_id' => $current_user_id
        ]);
        $has_responded = $check_stmt->rowCount() > 0;
    }

    // Получаем отклики на это объявление
    $responses_sql = "SELECT 
                        responses.*,
                        users.user_name,
                        users.user_phone
                    FROM responses
                    LEFT JOIN users ON responses.user_id = users.user_id
                    WHERE responses.ads_id = :id
                    ORDER BY responses.created_at DESC";
    
    $responses_stmt = $pdo->prepare($responses_sql);
    $responses_stmt->execute([':id' => $id]);
    $responses = $responses_stmt->fetchAll();

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ad['ads_title']) ?> - Сайт объявлений</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/detail.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="detail-page">
    <header class="header">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="index.php">
                        <img src="images/logo.svg" alt="Логотип" class="logo-image">
                    </a>
                </div>
                <div class="auth-buttons">
                    <?php if ($is_logged_in): ?>
                        <span class="user-welcome">Здравствуйте, <?= htmlspecialchars($user_name) ?></span>
                        <a href="logout.php" class="auth-btn logout-btn">Выход</a>
                    <?php else: ?>
                        <button class="auth-btn" data-tab="register">Регистрация</button>
                        <button class="auth-btn" data-tab="login">Вход</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="main detail-main">
        <div class="container">
            <div class="detail-wrapper">
                <!-- Левая колонка -->
                <div class="detail-left-column">
                    <div class="ad-photo-container">
                        <?php if (!empty($ad['ads_photo'])): ?>
                            <img src="images/<?= htmlspecialchars($ad['ads_photo']) ?>"
                                alt="<?= htmlspecialchars($ad['ads_title']) ?>" 
                                class="main-ad-photo">
                        <?php else: ?>
                            <div class="no-photo-placeholder">
                                Нет изображения
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Откликнувшиеся -->
                    <div class="responses-left-block">
                        <div class="responses-header">
                            <h2 class="block-title">Откликнулись</h2>
                            <?php if (!empty($responses)): ?>
                                <span class="responses-count"><?= count($responses) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($responses)): ?>
                            <div class="responses-list">
                                <?php foreach ($responses as $response): ?>
                                    <div class="response-person">
                                        <div class="response-person-name">
                                            <?= htmlspecialchars($response['user_name']) ?>
                                        </div>
                                        <div class="response-person-phone">
                                            <?= htmlspecialchars($response['user_phone']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-responses">
                                Пока никто не откликнулся. Будьте первым!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Правая колонка -->
                <div class="detail-info-column">
                    <div class="detail-top-block">
                        <div class="price-block">
                            <div class="ad-price-detail">
                                <?= number_format($ad['ads_price'], 0, '', ' ') ?> ₽
                            </div>
                            <a href="index.php" class="back-to-list-link">
                                ← Назад к списку
                            </a>
                        </div>
                    </div>

                    <h1 class="ad-title-detail">
                        <?= htmlspecialchars($ad['ads_title']) ?>
                    </h1>

                    <div class="author-contact-block">
                        <div class="author-contact-line">
                            <span class="author-phone">
                                <?= htmlspecialchars($ad['user_phone'] ?? 'Не указан') ?>
                            </span>
                            <span class="author-name">
                                <?= htmlspecialchars($ad['user_name'] ?? 'Неизвестный') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Кнопка отклика -->
                    <button class="respond-main-btn" 
                            id="respondButton" 
                            data-ad-id="<?= $id ?>"
                            <?= (!$is_logged_in || $has_responded) ? 'disabled' : '' ?>
                            onclick="handleResponse(<?= $id ?>)">
                        <?php if ($has_responded): ?>
                            ✓ Вы откликнулись на объявление
                        <?php elseif (!$is_logged_in): ?>
                            Войдите, чтобы откликнуться
                        <?php else: ?>
                            Откликнуться на объявление
                        <?php endif; ?>
                    </button>

                    <div class="description-block">
                        <h2 class="block-title">Описание</h2>
                        <div class="description-text">
                            <?= nl2br(htmlspecialchars($ad['ads_description'] ?? '')) ?>
                        </div>
                    </div>
                </div>
            </div>
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

    <script>
        function handleResponse(adId) {
            <?php if (!$is_logged_in): ?>
                alert('Для отклика необходимо войти в систему');
                // Здесь нужно открыть модальное окно
                // openModal('login');
                return;
            <?php endif; ?>

            if (!confirm('Вы уверены, что хотите откликнуться на это объявление?')) {
                return;
            }

            const button = document.getElementById('respondButton');
            button.disabled = true;
            button.innerHTML = 'Отправка...';

            // Отправляем запрос на сервер
            const formData = new FormData();
            formData.append('ad_id', adId);

            fetch('/respond.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.classList.add('responded');
                    button.innerHTML = '✓ Вы откликнулись на объявление';
                    alert('Вы успешно откликнулись на объявление!');
                    
                    // Обновляем страницу
                    location.reload();
                } else {
                    button.disabled = false;
                    button.innerHTML = 'Откликнуться на объявление';
                    alert(data.message || 'Ошибка при отклике');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                button.disabled = false;
                button.innerHTML = 'Откликнуться на объявление';
                alert('Произошла ошибка');
            });
        }
    </script>
</body>
</html>