<?php
// ВСЕГДА в самом начале файла
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
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Валидация
$errors = [];

if (empty($name)) $errors['name'] = 'Имя не может быть пустым';
if (empty($email)) $errors['email'] = 'Email не может быть пустым';
if (empty($phone)) $errors['phone'] = 'Телефон не может быть пустым';
if (empty($password)) $errors['password'] = 'Пароль не может быть пустым';
if ($password !== $confirm_password) $errors['confirm_password'] = 'Пароли не совпадают';

if (!empty($errors)) {
    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}

try {
    // Проверка существования пользователя
    $checkSql = "SELECT user_id FROM users WHERE user_email = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetch()) {
        $response['errors']['email'] = 'Пользователь с таким email уже существует';
        echo json_encode($response);
        exit;
    }
    
    // Хеширование пароля
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Вставка пользователя
    $sql = "INSERT INTO users (user_name, user_email, user_phone, user_password) 
            VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$name, $email, $phone, $hashedPassword])) {
        // Авторизуем пользователя
        $user_id = $pdo->lastInsertId();
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_phone'] = $phone;
        
        $response['success'] = true;
        $response['message'] = 'Регистрация успешна';
        $response['user'] = [
            'id' => $user_id,
            'name' => $name,
            'email' => $email
        ];
    } else {
        $response['errors'][] = 'Ошибка при сохранении пользователя';
    }
    
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