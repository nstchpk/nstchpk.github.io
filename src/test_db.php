<?php
require_once 'config.php';
require_once 'db_connect.php';

echo "<h1>Тест подключения к БД</h1>";

// Тест подключения
try {
    echo "✓ Подключение к БД успешно<br>";
    
    // Проверяем таблицу users
    $stmt = $pdo->query("DESCRIBE users");
    echo "<h2>Структура таблицы users:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Проверяем существующих пользователей
    $stmt = $pdo->query("SELECT * FROM users LIMIT 5");
    echo "<h2>Пользователи в БД:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Имя</th><th>Email</th><th>Телефон</th><th>Пароль</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['user_name'] . "</td>";
        echo "<td>" . $row['user_email'] . "</td>";
        echo "<td>" . $row['user_phone'] . "</td>";
        echo "<td>" . substr($row['user_password'], 0, 20) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "✗ Ошибка подключения: " . $e->getMessage();
}
?>

<h2>Тест регистрации (вручную)</h2>
<form method="post" action="test_register.php">
    <input type="text" name="name" placeholder="Имя" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="tel" name="phone" placeholder="Телефон" required><br>
    <input type="password" name="password" placeholder="Пароль" required><br>
    <input type="submit" value="Тест регистрации">
</form>