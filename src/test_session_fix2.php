<?php
// Вторая страница для проверки сохранения сессии
echo "<h1>Вторая страница - проверка сессии</h1>";

// Те же настройки
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);

session_start();

echo "Session ID: " . session_id() . "<br>";

echo "<h2>Данные в сессии:</h2>";
if (!empty($_SESSION)) {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    if (isset($_SESSION['test_message'])) {
        echo "✓ Сессия сохраняется между страницами!<br>";
        echo "Время установки: " . date('Y-m-d H:i:s', $_SESSION['test_time']) . "<br>";
        echo "Текущее время: " . date('Y-m-d H:i:s') . "<br>";
    }
} else {
    echo "✗ Сессия пустая<br>";
}

echo "<br><a href='test_session_fix.php'>Вернуться назад</a>";
?>