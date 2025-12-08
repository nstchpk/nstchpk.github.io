<?php
session_start();

// Уничтожаем все данные сессии
$_SESSION = [];

// Если используется cookie сессии, удаляем его
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожаем сессию
session_destroy();

// Редирект на главную
header('Location: index.php');
exit;
?>