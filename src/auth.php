<?php
require_once 'config.php';
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['errors'][] = 'Неверный метод запроса';
    echo json_encode($response);
    exit;
}

// Получаем данные
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Валидация
if (empty($email)) {
    $response['errors']['email'] = 'Email не может быть пустым';
}

if (empty($password)) {
    $response['errors']['password'] = 'Пароль не может быть пустым';
}

if (!empty($response['errors'])) {
    echo json_encode($response);
    exit;
}

try {
    // Ищем пользователя
    $sql = "SELECT user_id, user_name, user_email, user_phone, user_password 
            FROM users WHERE user_email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $response['errors']['email'] = 'Пользователь не найден';
        echo json_encode($response);
        exit;
    }
    
    // Проверяем пароль
    if (!password_verify($password, $user['user_password'])) {
        $response['errors']['password'] = 'Неверный пароль';
        echo json_encode($response);
        exit;
    }
    
    // Авторизуем
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['user_name'];
    $_SESSION['user_email'] = $user['user_email'];
    $_SESSION['user_phone'] = $user['user_phone'];
    
    $response['success'] = true;
    $response['message'] = 'Авторизация успешна';
    $response['user'] = [
        'id' => $user['user_id'],
        'name' => $user['user_name'],
        'email' => $user['user_email']
    ];
    
} catch (PDOException $e) {
    $response['errors'][] = 'Ошибка базы данных';
    // Для отладки:
    $response['debug'] = $e->getMessage();
} catch (Exception $e) {
    $response['errors'][] = 'Произошла ошибка: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>