<?php
require_once 'config.php';
session_start();
require_once 'db_connect.php';

// === ПРОВЕРКА АВТОРИЗАЦИИ ===
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'] ?? null;

// === ПРОВЕРКА ID ОБЪЯВЛЕНИЯ ===
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$ad_id = (int)$_GET['id'];

// === ПОЛУЧАЕМ ОБЪЯВЛЕНИЕ ===
$stmt = $pdo->prepare("SELECT * FROM ads WHERE ads_id = ?");
$stmt->execute([$ad_id]);
$ad = $stmt->fetch();

if (!$ad) {
    die('Объявление не найдено');
}

// === ПРОВЕРКА ПРАВ ===
$can_edit = false;

if ($role_id === 1) {
    // админ
    $can_edit = true;
} elseif ($ad['user_id'] == $user_id) {
    // автор
    $can_edit = true;
}

if (!$can_edit) {
    die('У вас нет прав для редактирования этого объявления');
}

// === ОБРАБОТКА ФОРМЫ ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);

    $photoName = $ad['ads_photo']; // по умолчанию старое фото

    // === ЕСЛИ ЗАГРУЗИЛИ НОВОЕ ФОТО ===
    if (!empty($_FILES['photo']['name'])) {

        $uploadDir = __DIR__ . '/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoName = uniqid('ad_', true) . '.' . $ext;

        move_uploaded_file(
            $_FILES['photo']['tmp_name'],
            $uploadDir . $photoName
        );
    }

    // === ЕСЛИ НЕ АДМИН → СБРАСЫВАЕМ МОДЕРАЦИЮ ===
    $verified = ($role_id === 1) ? $ad['is_verified'] : 0;

    $sql = "
        UPDATE ads 
        SET ads_title = ?, ads_description = ?, ads_price = ?, ads_photo = ?, is_verified = ?
        WHERE ads_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        $description,
        $price,
        $photoName,
        $verified,
        $ad_id
    ]);

    header("Location: detail.php?id=$ad_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать объявление</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <h1 class="section-title">Редактировать объявление</h1>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-row">
            <input type="text"
                   name="title"
                   class="form-input"
                   required
                   value="<?= htmlspecialchars($ad['ads_title']) ?>">
        </div>

        <div class="form-row">
            <input type="number"
                   name="price"
                   class="form-input"
                   required
                   value="<?= htmlspecialchars($ad['ads_price']) ?>">
        </div>

        <div class="form-row">
            <textarea name="description"
                      class="form-input"
                      rows="6"
                      required><?= htmlspecialchars($ad['ads_description']) ?></textarea>
        </div>

        <div class="form-row">
            <p>Текущее изображение:</p>
            <img src="images/<?= htmlspecialchars($ad['ads_photo']) ?>"
                 style="max-width:200px; border-radius:8px;">
        </div>

        <div class="form-row">
            <input type="file" name="photo" accept="image/*">
        </div>

        <button class="submit-btn">Сохранить изменения</button>

        <?php if ($role_id !== 1): ?>
            <p style="margin-top:10px;color:#999;font-size:14px;">
                После редактирования объявление отправится на модерацию
            </p>
        <?php endif; ?>

    </form>
</div>

</body>
</html>