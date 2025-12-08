<?php
require_once 'db_connect.php';
require_once 'config.php';

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

// Проверяем данные
$ad_id = $_POST['ad_id'] ?? null;
if (!$ad_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Не указано объявление']);
    exit;
}

try {
    // Проверяем существование объявления
    $check_ad = $pdo->prepare("SELECT ads_id FROM ads WHERE ads_id = ?");
    $check_ad->execute([$ad_id]);
    
    if (!$check_ad->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Объявление не найдено']);
        exit;
    }

    // Проверяем, не откликался ли уже пользователь
    $check_response = $pdo->prepare("SELECT responses_id FROM responses WHERE ads_id = ? AND user_id = ?");
    $check_response->execute([$ad_id, $_SESSION['user_id']]);
    
    if ($check_response->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Вы уже откликнулись на это объявление']);
        exit;
    }

    // Получаем цену из объявления для сохранения в отклик
    $get_price = $pdo->prepare("SELECT ads_price FROM ads WHERE ads_id = ?");
    $get_price->execute([$ad_id]);
    $price_row = $get_price->fetch();
    $ads_price = $price_row['ads_price'];

    // Сохраняем отклик
    $sql = "INSERT INTO responses (ads_id, user_id, ads_price) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$ad_id, $_SESSION['user_id'], $ads_price])) {
        echo json_encode(['success' => true, 'message' => 'Отклик успешно сохранен']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка сохранения отклика']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>