<?php
// В Docker MySQL находится по имени сервиса "mysql"
define("DB_HOST", "mysql"); 
define("DB_NAME", "db_ads");
define("DB_USER", "root");
define("DB_PASSWORD", "root");

// Настройки сессий
ini_set('session.cookie_lifetime', 86400); // 24 часа
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // 0 для HTTP (localhost), 1 для HTTPS
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Путь для сохранения сессий (важно для Docker)
ini_set('session.save_path', '/tmp/sessions');

// Создаем директорию для сессий если ее нет
if (!file_exists('/tmp/sessions')) {
    mkdir('/tmp/sessions', 0777, true);
}

// Настройки отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// НЕ вызываем session_start() здесь!
// session_start() должен вызываться в каждом файле где нужны сессии
?>