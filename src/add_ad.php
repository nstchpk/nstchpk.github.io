<?php
require_once 'config.php';
session_start();
require_once 'db_connect.php';

// Только для авторизованных
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_name = $_SESSION['user_name'];
$role_id = $_SESSION['role_id'] ?? null;

// Получаем список категорий
$categories_sql = "SELECT * FROM category ORDER BY id_category";
$categories = $pdo->query($categories_sql)->fetchAll();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title'] ?? ''));
    $price = htmlspecialchars(trim($_POST['price'] ?? ''));
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $category_id = intval($_POST['category_id'] ?? 0); // НОВОЕ ПОЛЕ

    // Валидация данных
    if (empty($title)) {
        $errors[] = 'Введите название объявления';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Название слишком длинное (максимум 255 символов)';
    }

    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors[] = 'Введите корректную цену';
    } elseif ($price > 1000000000) {
        $errors[] = 'Цена слишком большая';
    }

    if (empty($description)) {
        $errors[] = 'Введите описание объявления';
    } elseif (strlen($description) > 2000) {
        $errors[] = 'Описание слишком длинное (максимум 2000 символов)';
    }

    // Валидация категории
    if ($category_id <= 0) {
        $errors[] = 'Выберите категорию объявления';
    } else {
        // Проверяем, существует ли категория
        $check_cat_sql = "SELECT id_category FROM category WHERE id_category = ?";
        $check_stmt = $pdo->prepare($check_cat_sql);
        $check_stmt->execute([$category_id]);
        if (!$check_stmt->fetch()) {
            $errors[] = 'Выбранная категория не существует';
        }
    }

    // Проверка файла (остается как есть)
    $fileName = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Разрешены только изображения (JPEG, PNG, GIF, WebP)';
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Размер файла не должен превышать 5MB';
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowedExt)) {
            $errors[] = 'Недопустимое расширение файла';
        }

        if (empty($errors)) {
            $uploadDir = __DIR__ . '/images/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid('ad_', true) . '.' . $ext;
            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                $errors[] = 'Не удалось сохранить файл';
            }
        }
    } else {
        $errors[] = 'Загрузите изображение объявления';
    }

    // Сохранение в БД с категорией
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO ads (user_id, ads_title, ads_description, ads_price, ads_photo, category_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $description,
                $price,
                $fileName,
                $category_id  // Добавляем категорию
            ]);

            $ad_id = $pdo->lastInsertId();
            $success = true;

            if ($role_id == 1) {
                $pdo->prepare("UPDATE ads SET is_verified = 1 WHERE ads_id = ?")
                    ->execute([$ad_id]);
            }

            header("Location: detail.php?id=" . $ad_id);
            exit;

        } catch (PDOException $e) {
            $errors[] = 'Ошибка при сохранении в базу данных: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить объявление - Сайт объявлений</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Дополнительные стили для страницы добавления */
        .add-ad-page {
            padding: 40px 0 60px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #666;
            font-size: 16px;
        }

        /* Ошибки валидации */
        .error-messages {
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 30px;
            color: #d33;
        }

        .error-messages ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .error-messages li {
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }

        .error-messages li:before {
            content: '✗';
            position: absolute;
            left: 0;
            color: #d33;
        }

        /* Уведомление об успехе */
        .success-message {
            background: #dfd;
            border: 1px solid #afa;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 30px;
            color: #3a3;
            text-align: center;
            font-weight: 500;
        }

        /* Форма добавления */
        .add-ad-form {
            max-width: 900px;
            margin: 0 auto;
        }

        .add-ad-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            align-items: flex-start;
            margin-bottom: 40px;
        }

        /* Загрузка фото */
        .photo-upload {
            flex: 1 1 300px;
            max-width: 400px;
        }

        .photo-placeholder {
            width: 100%;
            aspect-ratio: 1 / 1;
            max-height: 400px;
            border: 2px dashed #ccc;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #777;
            cursor: pointer;
            transition: all 0.3s;
            background: #f9f9f9;
        }

        .photo-placeholder:hover {
            border-color: #E91E63;
            background: #fff5f7;
            color: #E91E63;
        }

        .photo-plus {
            font-size: 48px;
            font-weight: 300;
            margin-bottom: 10px;
            color: #E91E63;
        }

        .photo-text {
            text-align: center;
            font-size: 16px;
            line-height: 1.4;
        }

        .photo-preview {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            max-height: 400px;
            border-radius: 16px;
            overflow: hidden;
            background: #f9f9f9;
        }

        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-photo {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .remove-photo:hover {
            background: #E91E63;
            transform: scale(1.1);
        }

        /* Поля формы */
        .add-ad-fields {
            flex: 2 1 300px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 15px;
        }

        .form-input,
        .form-textarea,
        select.form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.3s;
            background-color: white;
        }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 40px;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #E91E63;
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
            line-height: 1.5;
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #aaa;
        }

        /* Кнопка публикации */
        .publish-btn {
            background: #E91E63;
            color: white;
            border: none;
            padding: 16px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: block;
            margin: 0 auto 15px;
            width: 100%;
            max-width: 400px;
        }

        .publish-btn:hover {
            background: #C2185B;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.3);
        }

        .publish-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Подсказка формы */
        .form-hint {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-bottom: 40px;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .add-ad-grid {
                flex-direction: column;
                gap: 30px;
            }

            .photo-upload {
                max-width: 100%;
            }

            .photo-placeholder,
            .photo-preview {
                max-height: 300px;
            }

            .page-title {
                font-size: 24px;
            }

            .publish-btn {
                padding: 14px 30px;
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .add-ad-page {
                padding: 20px 0 40px;
            }

            .photo-placeholder,
            .photo-preview {
                max-height: 250px;
            }

            .photo-plus {
                font-size: 36px;
            }

            .photo-text {
                font-size: 14px;
            }

            .form-input,
            .form-textarea {
                padding: 12px 14px;
                font-size: 15px;
            }
        }

        /* Хлебные крошки */
        .breadcrumbs {
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }

        .breadcrumbs a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumbs a:hover {
            color: #E91E63;
        }

        .breadcrumbs span {
            margin: 0 8px;
            color: #999;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <!-- Логотип как ссылка на главную -->
                <a href="index.php" class="logo">
                    <img src="images/logoo.svg" class="logo-image" alt="Логотип сайта">
                    <span class="logo-text">Объявления</span>
                </a>

                <div class="user-block">
                    <span class="user-welcome">Здравствуйте, <?= htmlspecialchars($user_name) ?></span>
                    <a href="logout.php" class="logout-link">Выход</a>
                </div>
            </div>
        </div>
    </header>

    <main class="add-ad-page">
        <div class="container">
            <!-- Хлебные крошки -->
            <div class="breadcrumbs">
                <a href="index.php">Главная</a>
                <span>›</span>
                <span>Добавить объявление</span>
            </div>

            <!-- Заголовок страницы -->
            <div class="page-header">
                <h1 class="page-title">Добавить объявление</h1>
                <p class="page-subtitle">Заполните форму ниже, чтобы разместить новое объявление</p>
            </div>

            <!-- Сообщения об ошибках -->
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Сообщение об успехе -->
            <?php if ($success): ?>
                <div class="success-message">
                    ✅ Объявление успешно добавлено! Перенаправляем на страницу объявления...
                </div>
            <?php endif; ?>

            <!-- Форма добавления -->
            <form class="add-ad-form" method="POST" action="add_ad.php" enctype="multipart/form-data">
                <div class="add-ad-grid">
                    <!-- Загрузка изображения -->
                    <div class="photo-upload">
                        <!-- Плейсхолдер -->
                        <label for="photoInput" class="photo-placeholder" id="photoPlaceholder">
                            <div class="photo-plus">+</div>
                            <div class="photo-text">Загрузите изображение<br>объявления</div>
                        </label>

                        <!-- Превью -->
                        <div class="photo-preview" id="photoPreview" style="display: none;">
                            <img id="previewImage" src="" alt="Предпросмотр">
                            <button type="button" class="remove-photo" onclick="removePhoto()">×</button>
                        </div>

                        <!-- Input -->
                        <input type="file" name="photo" id="photoInput" accept="image/*" hidden required
                            onchange="validateFile(this)">
                    </div>

                    <!-- Поля формы -->
                    <div class="add-ad-fields">
                        <div class="form-group">
                            <label for="category_id" class="form-label">Категория *</label>
                            <select id="category_id" name="category_id" class="form-input" required>
                                <option value="">-- Выберите категорию --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id_category'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id_category']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name_category']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="title" class="form-label">Название объявления *</label>
                            <input type="text" id="title" name="title" class="form-input"
                                placeholder="Например: Кожаный кошелек"
                                value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" required
                                maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="price" class="form-label">Цена *</label>
                            <input type="number" id="price" name="price" class="form-input" placeholder="Например: 3590"
                                value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>" required
                                min="0" max="1000000000" step="1">
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Описание *</label>
                            <textarea id="description" name="description" class="form-textarea"
                                placeholder="Опишите ваше объявление подробно..." required
                                maxlength="2000"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="publish-btn" id="submitBtn">
                    Опубликовать объявление
                </button>

                <div class="form-hint">
                    ⓘ Все поля обязательны для заполнения
                </div>
            </form>
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
        const photoInput = document.getElementById('photoInput');
        const preview = document.getElementById('photoPreview');
        const previewImg = document.getElementById('previewImage');
        const placeholder = document.getElementById('photoPlaceholder');
        const submitBtn = document.getElementById('submitBtn');
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        // Валидация файла
        function validateFile(input) {
            const file = input.files[0];

            if (!file) {
                resetFileInput();
                return;
            }

            // Проверка типа файла
            if (!allowedTypes.includes(file.type)) {
                alert('Ошибка: Разрешены только изображения (JPEG, PNG, GIF, WebP)');
                resetFileInput();
                return;
            }

            // Проверка размера файла
            if (file.size > maxSize) {
                alert('Ошибка: Размер файла не должен превышать 5MB');
                resetFileInput();
                return;
            }

            // Показ превью
            showPreview(file);
        }

        // Показ превью
        function showPreview(file) {
            const reader = new FileReader();

            reader.onload = function (e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            };

            reader.readAsDataURL(file);
        }

        // Удаление фото
        function removePhoto() {
            photoInput.value = '';
            previewImg.src = '';
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
        }

        // Сброс файлового инпута
        function resetFileInput() {
            photoInput.value = '';
            removePhoto();
        }

        // Валидация формы перед отправкой
        document.querySelector('.add-ad-form').addEventListener('submit', function (e) {
            const title = document.getElementById('title').value.trim();
            const price = document.getElementById('price').value.trim();
            const description = document.getElementById('description').value.trim();

            if (!title || !price || !description || !photoInput.files[0]) {
                alert('Заполните все обязательные поля');
                e.preventDefault();
                return;
            }

            if (price <= 0) {
                alert('Цена должна быть больше 0');
                e.preventDefault();
                return;
            }

            // Блокировка кнопки на время отправки
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Публикация...';
        });

        // Счетчик символов для описания
        const descriptionTextarea = document.getElementById('description');
        const descriptionCount = document.createElement('div');
        descriptionCount.className = 'char-count';
        descriptionCount.style.cssText = 'text-align: right; font-size: 12px; color: #999; margin-top: 5px;';
        descriptionTextarea.parentNode.appendChild(descriptionCount);

        descriptionTextarea.addEventListener('input', function () {
            const maxLength = 2000;
            const currentLength = this.value.length;

            if (currentLength > maxLength) {
                this.value = this.value.substring(0, maxLength);
                descriptionCount.textContent = `${maxLength}/${maxLength}`;
                descriptionCount.style.color = '#f00';
            } else {
                descriptionCount.textContent = `${currentLength}/${maxLength}`;
                descriptionCount.style.color = currentLength > 1800 ? '#f90' : '#999';
            }
        });

        // Инициализация счетчика
        descriptionTextarea.dispatchEvent(new Event('input'));

        // Подсказки при фокусе
        const inputs = document.querySelectorAll('.form-input, .form-textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function () {
                this.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', function () {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>

</html>