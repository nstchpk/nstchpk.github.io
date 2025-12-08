<?php
require_once 'db_connect.php';

echo "<h1>Исправление паролей в БД</h1>";

try {
    // Получаем всех пользователей
    $stmt = $pdo->query("SELECT user_id, user_password FROM users");
    $users = $stmt->fetchAll();
    
    echo "<p>Найдено пользователей: " . count($users) . "</p>";
    
    foreach ($users as $user) {
        $current_password = $user['user_password'];
        $user_id = $user['user_id'];
        
        // Проверяем, не хеширован ли уже пароль
        if (strlen($current_password) < 60 && !password_verify('test', $current_password)) {
            // Пароль не хеширован - хешируем
            $hashed_password = password_hash($current_password, PASSWORD_DEFAULT);
            
            // Обновляем в БД
            $update_stmt = $pdo->prepare("UPDATE users SET user_password = ? WHERE user_id = ?");
            $update_stmt->execute([$hashed_password, $user_id]);
            
            echo "<p>✓ Пользователь ID {$user_id}: пароль исправлен</p>";
        } else {
            echo "<p>• Пользователь ID {$user_id}: пароль уже хеширован</p>";
        }
    }
    
    echo "<h2>Проверка:</h2>";
    
    // Проверяем результат
    $check_stmt = $pdo->query("SELECT user_id, user_name, LEFT(user_password, 20) as pass_start FROM users");
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Имя</th><th>Начало пароля</th></tr>";
    while ($row = $check_stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['user_name'] . "</td>";
        echo "<td>" . $row['pass_start'] . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Готово!</strong> Теперь можно тестировать авторизацию.</p>";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>