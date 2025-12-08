<?php
// В Docker MySQL находится по имени сервиса "mysql"
define("DB_HOST", "mysql"); 
define("DB_NAME", "db_ads");
define("DB_USER", "root");
define("DB_PASSWORD", "root");

// Включаем отладку
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// НИКАКОГО вывода перед session_start()!
// session_start() будет вызываться в каждом файле где нужны сессии
?>