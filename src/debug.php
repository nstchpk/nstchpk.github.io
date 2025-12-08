<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Отладка сайта</h1>";

// Проверяем сессии
echo "<h2>Проверка сессий</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✓ Сессия активна<br>";
} else {
    echo "✗ Сессия не активна<br>";
}

// Проверяем конфигурацию
echo "<h2>Конфигурация</h2>";
require_once 'config.php';
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";

// Проверяем подключение к БД
echo "<h2>Подключение к БД</h2>";
try {
    require_once 'db_connect.php';
    echo "✓ Подключение к БД успешно<br>";
    
    // Тест запроса
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Пользователей в БД: " . $result['count'] . "<br>";
    
} catch (PDOException $e) {
    echo "✗ Ошибка подключения: " . $e->getMessage() . "<br>";
}

// Тест регистрации
echo "<h2>Тест регистрации (через CURL)</h2>";
?>
<form method="post" action="test_register_endpoint.php">
    <input type="text" name="name" placeholder="Имя" value="Тестовый" required><br>
    <input type="email" name="email" placeholder="Email" value="test@example.com" required><br>
    <input type="tel" name="phone" placeholder="Телефон" value="89001234567" required><br>
    <input type="password" name="password" placeholder="Пароль" value="123456" required><br>
    <input type="password" name="confirm_password" placeholder="Повторите пароль" value="123456" required><br>
    <input type="submit" value="Тест регистрации напрямую">
</form>

<hr>

<h2>Проверка файлов</h2>
<?php
$files = [
    'config.php',
    'db_connect.php',
    'register.php',
    'auth.php',
    'index.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "✓ $file ($size байт)<br>";
    } else {
        echo "✗ $file - не найден<br>";
    }
}
?>