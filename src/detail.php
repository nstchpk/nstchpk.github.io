<?php
require_once 'config.php';
session_start();
require_once 'db_connect.php';

// Проверяем авторизацию (ТОЧНО ТАК ЖЕ как в index.php)
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
    // В SQL запросе для получения объявления добавьте JOIN с категорией:
    $sql = "SELECT 
            ads.*, 
            users.user_name,
            users.user_phone,
            users.user_email,
            category.name_category  -- Добавляем название категории
        FROM ads
        LEFT JOIN users ON ads.user_id = users.user_id
        LEFT JOIN category ON ads.category_id = category.id_category  -- JOIN с категорией
        WHERE ads.ads_id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $ad = $stmt->fetch();

    $role_id = $_SESSION['role_id'] ?? null;

    $can_edit = false;

    if ($role_id === 1) {
        // Админ может всё
        $can_edit = true;
    } elseif ($is_logged_in && $ad['user_id'] == $_SESSION['user_id']) {
        // Автор может редактировать СВОЁ объявление
        $can_edit = true;
    }


    if (!$ad) {
        header("Location: index.php");
        exit();
    }

    // Ограничение доступа к непроверенным объявлениям
    if (
        $ad['is_verified'] == 0 &&
        (
            !$is_logged_in ||
            ($current_user_id != $ad['user_id'] && ($_SESSION['role_id'] ?? null) != 1)
        )
    ) {
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
                    <a class="logo-link" href="index.php">
                        <img src="images/logoo.svg" alt="Логотип сайта" class="logo-image">
                        <span class="logo-text">Объявления</span>
                    </a>
                </div>
                <!-- ТАК ЖЕ как в index.php -->
                <div class="auth-buttons">
                    <?php if ($is_logged_in): ?>
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

    <main class="main detail-main">
        <div class="container">
            <div class="detail-wrapper">
                <!-- Левая колонка -->
                <div class="detail-left-column">
                    <div class="ad-photo-container">
                        <?php if (!empty($ad['ads_photo'])): ?>
                            <img src="images/<?= htmlspecialchars($ad['ads_photo']) ?>"
                                alt="<?= htmlspecialchars($ad['ads_title']) ?>" class="main-ad-photo">
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
                    <div class="detail-top">
                        <?php if ($can_edit): ?>
                            <a href="edit_ad.php?id=<?= $ad['ads_id'] ?>" class="edit-link">
                                Редактировать
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="back-link">← Назад к списку</a>
                    </div>

                    <div class="ad-price-detail">
                        <?= number_format($ad['ads_price'], 0, '', ' ') ?> ₽
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
                    <button class="respond-main-btn <?= $has_responded ? 'responded' : '' ?>" id="respondButton"
                        data-ad-id="<?= $id ?>" <?= (!$is_logged_in || $has_responded) ? 'disabled' : '' ?>
                        onclick="handleResponse(<?= $id ?>)">
                        <?php if ($has_responded): ?>
                            ✓ Вы откликнулись на объявление
                        <?php elseif (!$is_logged_in): ?>
                            Войдите, чтобы откликнуться
                        <?php else: ?>
                            Откликнуться на объявление
                        <?php endif; ?>
                    </button>

                    <div class="description-card">
                        <h3>Описание</h3>
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

    <!-- Модальное окно авторизации (ТОЧНО ТАКОЕ ЖЕ как в index.php) -->
    <div class="modal-overlay" id="authModal" style="display: none;">
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal">
            <div class="modal-content">
                <div class="auth-tabs">
                    <button class="auth-tab" id="registerTabBtn" onclick="switchTab('register')">Регистрация</button>
                    <button class="auth-tab" id="loginTabBtn" onclick="switchTab('login')">Авторизация</button>
                </div>

                <!-- Форма регистрации -->
                <div class="auth-form-container register-form" id="registerForm">
                    <form class="auth-form" id="registerFormElement" onsubmit="handleRegister(event)">
                        <div class="form-row">
                            <input type="text" placeholder="Ваше имя" required class="form-input" id="regName">
                        </div>
                        <div class="form-row form-two-columns">
                            <input type="email" placeholder="Email" required class="form-input" id="regEmail">
                            <input type="tel" placeholder="Мобильный телефон" required class="form-input" id="regPhone">
                        </div>
                        <div class="form-row form-two-columns">
                            <input type="password" placeholder="Пароль" required class="form-input" id="regPassword">
                            <input type="password" placeholder="Повторите пароль" required class="form-input"
                                id="regConfirmPassword">
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" id="agree" required class="checkbox-input">
                            <label for="agree" class="checkbox-label">
                                Согласен на обработку персональных данных
                            </label>
                        </div>

                        <button type="submit" class="submit-btn">Зарегистрироваться</button>
                    </form>

                    <div class="form-footer">
                        <p>Все поля обязательны для заполнения</p>
                    </div>
                </div>

                <!-- Форма авторизации -->
                <div class="auth-form-container login-form" id="loginForm" style="display: none;">
                    <form class="auth-form" id="loginFormElement" onsubmit="handleLogin(event)">
                        <div class="form-row">
                            <input type="email" placeholder="Email" required class="form-input" id="loginEmail">
                        </div>
                        <div class="form-row">
                            <input type="password" placeholder="Пароль" required class="form-input" id="loginPassword">
                        </div>

                        <button type="submit" class="submit-btn">Войти</button>
                    </form>

                    <div class="form-footer">
                        <p>Все поля обязательны для заполнения</p>
                    </div>
                </div>

                <button class="close-btn" onclick="closeModal()">×</button>
            </div>
        </div>
    </div>

    <script>
        // === ОБРАБОТКА ОТКЛИКА ===
        function handleResponse(adId) {
            <?php if (!$is_logged_in): ?>
                alert('Для отклика необходимо войти в систему');
                openModal('login');
                return false;
            <?php else: ?>
                return performResponse(adId);
            <?php endif; ?>
        }

        // js/common.js
        function openModal(tab = 'register') {
            const modal = document.getElementById('authModal');
            if (!modal) return;

            const scrollY = window.scrollY;

            modal.style.display = 'flex';
            document.body.style.position = 'fixed';
            document.body.style.top = `-${scrollY}px`;
            document.body.style.width = '100%';

            switchTab(tab);
        }

        function closeModal() {
            const modal = document.getElementById('authModal');
            if (!modal) return;

            modal.style.display = 'none';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';

            if (scrollY) {
                window.scrollTo(0, parseInt(scrollY || '0') * -1);
            }
        }

        function switchTab(tab) {
            const registerForm = document.getElementById('registerForm');
            const loginForm = document.getElementById('loginForm');
            const registerTabBtn = document.getElementById('registerTabBtn');
            const loginTabBtn = document.getElementById('loginTabBtn');

            if (!registerForm || !loginForm) return;

            if (tab === 'register') {
                registerForm.style.display = 'block';
                loginForm.style.display = 'none';
                if (registerTabBtn) registerTabBtn.classList.add('active');
                if (loginTabBtn) loginTabBtn.classList.remove('active');
            } else {
                registerForm.style.display = 'none';
                loginForm.style.display = 'block';
                if (loginTabBtn) loginTabBtn.classList.add('active');
                if (registerTabBtn) registerTabBtn.classList.remove('active');
            }
        }

        function performResponse(adId) {
            const button = document.getElementById('respondButton');

            // Если уже откликался, ничего не делаем
            if (button.classList.contains('responded')) {
                return false;
            }

            if (!confirm('Вы уверены, что хотите откликнуться на это объявление?')) {
                return false;
            }

            button.disabled = true;
            button.innerHTML = 'Отправка...';

            // Отправляем запрос на сервер
            const formData = new FormData();
            formData.append('ad_id', adId);

            fetch('/respond.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Обновляем кнопку
                        button.classList.add('responded');
                        button.innerHTML = '✓ Вы откликнулись на объявление';
                        button.disabled = true;

                        // Обновляем блок с откликнувшимися без перезагрузки страницы
                        updateResponsesList(adId);

                        // Показываем уведомление
                        showNotification('Вы успешно откликнулись на объявление!', 'success');
                    } else {
                        button.disabled = false;
                        button.innerHTML = 'Откликнуться на объявление';

                        if (data.message === 'Требуется авторизация') {
                            alert('Сессия истекла. Пожалуйста, войдите снова.');
                            openModal('login');
                        } else {
                            showNotification(data.message || 'Ошибка при отклике', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    button.disabled = false;
                    button.innerHTML = 'Откликнуться на объявление';
                    showNotification('Произошла ошибка при отправке', 'error');
                });

            return false;
        }

        // Функция для обновления списка откликов без перезагрузки страницы
        function updateResponsesList(adId) {
            fetch(`/get_responses.php?ad_id=${adId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.responses) {
                        const responsesList = document.querySelector('.responses-list');
                        const responsesCount = document.querySelector('.responses-count');
                        const noResponses = document.querySelector('.no-responses');

                        // Обновляем счетчик
                        if (responsesCount) {
                            responsesCount.textContent = data.responses.length;
                        }

                        // Убираем сообщение "нет откликов"
                        if (noResponses) {
                            noResponses.style.display = 'none';
                        }

                        // Если список не существует, создаем его
                        if (!responsesList && data.responses.length > 0) {
                            const responsesLeftBlock = document.querySelector('.responses-left-block');
                            const responsesHeader = document.querySelector('.responses-header');

                            const newList = document.createElement('div');
                            newList.className = 'responses-list';

                            data.responses.forEach(response => {
                                const personDiv = document.createElement('div');
                                personDiv.className = 'response-person';
                                personDiv.innerHTML = `
                    <div class="response-person-name">${response.user_name}</div>
                    <div class="response-person-phone">${response.user_phone}</div>
                `;
                                newList.appendChild(personDiv);
                            });

                            responsesHeader.insertAdjacentElement('afterend', newList);
                        } else if (responsesList) {
                            // Очищаем и обновляем существующий список
                            responsesList.innerHTML = '';

                            data.responses.forEach(response => {
                                const personDiv = document.createElement('div');
                                personDiv.className = 'response-person';
                                personDiv.innerHTML = `
                    <div class="response-person-name">${response.user_name}</div>
                    <div class="response-person-phone">${response.user_phone}</div>
                `;
                                responsesList.appendChild(personDiv);
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Ошибка обновления списка откликов:', error);
                });
        }

        // Функция для показа уведомлений
        function showNotification(message, type = 'info') {
            // Удаляем старые уведомления
            const oldNotification = document.querySelector('.notification');
            if (oldNotification) {
                oldNotification.remove();
            }

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = message;

            // Стили для уведомления
            notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 10000;
    animation: slideIn 0.3s ease-out;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
`;

            if (type === 'success') {
                notification.style.background = '#4CAF50';
            } else if (type === 'error') {
                notification.style.background = '#f44336';
            } else {
                notification.style.background = '#2196F3';
            }

            document.body.appendChild(notification);

            // Автоматическое скрытие через 3 секунды
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Анимации для уведомлений
        const style = document.createElement('style');
        style.textContent = `
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
`;
        document.head.appendChild(style);
    </script>
</body>

</html>