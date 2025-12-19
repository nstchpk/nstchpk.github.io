<?php
// init.php

// 1. СНАЧАЛА config.php (настройки сессий ДО session_start)
require_once 'config.php';

// 2. Стартуем сессию (ОДИН раз)
session_start();

// 3. Подключаем БД
require_once 'db_connect.php';

// 4. Общие переменные (они берутся из сессии после session_start)
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$current_user_id = $_SESSION['user_id'] ?? null;
$user_role_id = $_SESSION['role_id'] ?? null;
$is_admin = $is_logged_in && $user_role_id == 1;
?>