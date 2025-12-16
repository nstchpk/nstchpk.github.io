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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // === ПРОВЕРКА ФАЙЛА ===
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        die('Ошибка загрузки изображения');
    }

    $file = $_FILES['photo'];

    // Папка для изображений
    $uploadDir = __DIR__ . '/images/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Расширение файла
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

    // Уникальное имя
    $fileName = uniqid('ad_', true) . '.' . $ext;

    $filePath = $uploadDir . $fileName;

    // Сохраняем файл
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        die('Не удалось сохранить файл');
    }

    // === СОХРАНЕНИЕ В БД ===
    $sql = "INSERT INTO ads (user_id, ads_title, ads_description, ads_price, ads_photo)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['user_id'],
        $title,
        $description,
        $price,
        $fileName
    ]);

    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Добавить объявление</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <img src="images/logoo.svg" class="logo-image" alt="Логотип">
                    <span class="logo-text">Объявления</span>
                </div>

                <div class="auth-buttons">
                    <span class="user-welcome">Здравствуйте, <?= htmlspecialchars($user_name) ?></span>
                    <a href="logout.php" class="auth-btn logout-btn">Выход</a>
                </div>
            </div>
        </div>
    </header>

    <main class="add-ad-page">
        <div class="container">
            <form class="add-ad-form" method="POST" action="add_ad.php" enctype="multipart/form-data">

                <div class="add-ad-grid">

                    <!-- Загрузка изображения -->
                    <div class="photo-upload">

                        <!-- ПЛЕЙСХОЛДЕР -->
                        <label for="photoInput" class="photo-placeholder" id="photoPlaceholder">
                            <div class="photo-plus">+</div>
                            <div class="photo-text">Загрузите изображение</div>
                        </label>

                        <!-- PREVIEW -->
                        <div class="photo-preview" id="photoPreview" style="display: none;">
                            <img id="previewImage" src="" alt="Предпросмотр">
                            <button type="button" class="remove-photo" onclick="removePhoto()">×</button>
                        </div>

                        <!-- INPUT -->
                        <input type="file" name="photo" id="photoInput" accept="image/*" hidden required>
                    </div>


                    <!-- Поля -->
                    <div class="add-ad-fields">
                        <input type="text" name="title" placeholder="Название" required>
                        <input type="number" name="price" placeholder="Цена" required>
                        <textarea name="description" placeholder="Описание" required></textarea>
                    </div>

                </div>

                <button type="submit" class="publish-btn">
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

        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });

        function removePhoto() {
            photoInput.value = '';
            previewImg.src = '';
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
        }
    </script>

</body>

</html>