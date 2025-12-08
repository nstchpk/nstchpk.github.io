<?php
// Тест сессий с подробной информацией
echo "<h1>Тест сессий</h1>";

// Параметры сессии перед стартом
ini_set('session.cookie_lifetime', 86400); // 24 часа
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // 0 для localhost, 1 для HTTPS
ini_set('session.use_only_cookies', 1);

echo "<h2>Параметры сессии:</h2>";
echo "session.save_path: " . session_save_path() . "<br>";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "<br>";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "<br>";

// Стартуем сессию
session_start();

echo "<h2>Информация о сессии:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Status: " . session_status() . "<br>";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✓ Сессия активна<br>";
    
    // Устанавливаем тестовые данные
    $_SESSION['test_time'] = time();
    $_SESSION['test_message'] = 'Сессия работает!';
    $_SESSION['user_id'] = 999;
    $_SESSION['user_name'] = 'Тестовый пользователь';
    
    echo "<h2>Данные в сессии:</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<h2>Cookie:</h2>";
    if (isset($_COOKIE[session_name()])) {
        echo "✓ Cookie '" . session_name() . "' установлен: " . $_COOKIE[session_name()] . "<br>";
    } else {
        echo "✗ Cookie сессии не установлен<br>";
    }
    
    echo "<h2>Заголовки:</h2>";
    echo "Заголовки отправлены: " . (headers_sent() ? 'Да' : 'Нет') . "<br>";
    if (headers_sent()) {
        echo "Место отправки: " . headers_sent($file, $line) . " в файле $file на строке $line<br>";
    }
    
} else {
    echo "✗ Сессия не активна<br>";
}

echo "<br><a href='test_session_fix2.php'>Перейти на вторую страницу</a>";
?>