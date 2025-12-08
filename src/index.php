<?php
require_once 'config.php';
session_start();
require_once 'db_connect.php';

// Проверяем авторизацию
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

// Получаем все объявления
try {
    $sql = "SELECT * FROM ads ORDER BY created_at DESC LIMIT 15";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $ads = $stmt->fetchAll();
} catch (PDOException $e) {
    $ads = [];
    error_log("Ошибка БД: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сайт объявлений</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <img src="images/logo.svg" alt="Логотип" class="logo-image">
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

    <main class="main">
        <div class="container">
            <div class="section-header">
                <h1 class="section-title">Новые объявления</h1>
                <button class="add-btn" onclick="handleAddAd()">
                    <span class="add-icon">+</span>
                    <span class="add-text">Добавить объявление</span>
                </button>
            </div>

            <div class="ads-grid">
                <?php if (!empty($ads)): ?>
                    <?php foreach ($ads as $ad): ?>
                        <div class="ad-card">
                            <div class="ad-img">
                                <a href="detail.php?id=<?= $ad['ads_id'] ?>">
                                    <img src="images/<?= htmlspecialchars($ad['ads_photo']) ?>" 
                                         alt="<?= htmlspecialchars($ad['ads_title']) ?>" 
                                         class="ad-image">
                                </a>
                            </div>
                            <div class="ad-price"><?= number_format($ad['ads_price'], 0, '', ' ') ?> ₽</div>
                            <div class="ad-title"><?= htmlspecialchars($ad['ads_title']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; width: 100%; padding: 40px; color: #666;">
                        Пока нет объявлений
                    </p>
                <?php endif; ?>
            </div>

            <div class="load-more">
                <button class="show-more-btn" onclick="loadMoreAds()">
                    Показать ещё
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e91e63" stroke-width="2">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </button>
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

    <!-- Модальное окно авторизации -->
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
                            <input type="password" placeholder="Повторите пароль" required class="form-input" id="regConfirmPassword">
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
        // === МОДАЛЬНОЕ ОКНО ===
        function openModal(tab = 'register') {
            const modal = document.getElementById('authModal');
            if (!modal) return;
            
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            switchTab(tab);
        }

        function closeModal() {
            const modal = document.getElementById('authModal');
            if (!modal) return;
            
            modal.style.display = 'none';
            document.body.style.overflow = '';
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

        // === ОБРАБОТКА РЕГИСТРАЦИИ ===
        function handleRegister(event) {
            event.preventDefault();

            const name = document.getElementById('regName')?.value.trim();
            const email = document.getElementById('regEmail')?.value.trim();
            const phone = document.getElementById('regPhone')?.value.trim();
            const password = document.getElementById('regPassword')?.value;
            const confirmPassword = document.getElementById('regConfirmPassword')?.value;
            const agree = document.getElementById('agree')?.checked;

            // Валидация на клиенте
            if (!name || !email || !phone || !password || !confirmPassword) {
                alert('Все поля обязательны для заполнения');
                return;
            }

            if (password !== confirmPassword) {
                alert('Пароли не совпадают');
                return;
            }

            if (password.length < 6) {
                alert('Пароль должен быть не менее 6 символов');
                return;
            }

            if (!agree) {
                alert('Необходимо согласие на обработку персональных данных');
                return;
            }

            // Отправка данных на сервер
            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('password', password);
            formData.append('confirm_password', confirmPassword);

            fetch('/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Ответ регистрации:', data);
                
                if (data.success) {
                    alert('Регистрация успешна!');
                    closeModal();
                    location.reload(); // Перезагружаем страницу
                } else {
                    if (data.errors) {
                        let errorMessage = 'Ошибки:\n';
                        for (let field in data.errors) {
                            errorMessage += `• ${data.errors[field]}\n`;
                        }
                        alert(errorMessage);
                    } else {
                        alert(data.message || 'Ошибка регистрации');
                    }
                }
            })
            .catch(error => {
                console.error('Ошибка регистрации:', error);
                alert('Произошла ошибка при регистрации');
            });
        }

        // === ОБРАБОТКА АВТОРИЗАЦИИ ===
        function handleLogin(event) {
            event.preventDefault();

            const email = document.getElementById('loginEmail')?.value.trim();
            const password = document.getElementById('loginPassword')?.value;

            if (!email || !password) {
                alert('Все поля обязательны для заполнения');
                return;
            }

            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);

            fetch('/auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Ответ авторизации:', data);
                
                if (data.success) {
                    alert('Вход выполнен успешно!');
                    closeModal();
                    location.reload(); // Перезагружаем страницу
                } else {
                    if (data.errors) {
                        let errorMessage = 'Ошибки:\n';
                        for (let field in data.errors) {
                            errorMessage += `• ${data.errors[field]}\n`;
                        }
                        alert(errorMessage);
                    } else {
                        alert(data.message || 'Ошибка авторизации');
                    }
                }
            })
            .catch(error => {
                console.error('Ошибка авторизации:', error);
                alert('Произошла ошибка при входе');
            });
        }

        // === ДОБАВЛЕНИЕ ОБЪЯВЛЕНИЯ ===
        function handleAddAd() {
            <?php if ($is_logged_in): ?>
                alert('Функция добавления объявления в разработке');
            <?php else: ?>
                alert('Для добавления объявления необходимо войти в систему');
                openModal('login');
            <?php endif; ?>
        }

        // === ЗАГРУЗКА ЕЩЕ ОБЪЯВЛЕНИЙ ===
        function loadMoreAds() {
            const btn = document.querySelector('.show-more-btn');
            btn.disabled = true;
            btn.innerHTML = 'Загрузка...';
            
            // Здесь будет AJAX запрос для загрузки дополнительных объявлений
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = 'Показать ещё <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e91e63" stroke-width="2"><path d="M6 9l6 6 6-6" /></svg>';
                alert('Функция "Показать еще" в разработке');
            }, 1000);
        }

        // === ИНИЦИАЛИЗАЦИЯ ===
        document.addEventListener('DOMContentLoaded', function() {
            // Обработчики для кнопок авторизации
            document.querySelectorAll('.auth-btn[data-tab]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tab = this.getAttribute('data-tab');
                    openModal(tab);
                });
            });

            // Закрытие модалки по Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>