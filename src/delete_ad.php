<?php
require_once 'config.php';
session_start();
require_once 'db_connect.php';

// Только админ
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] ?? null) !== 1) {
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: admin.php');
    exit;
}

try {
    // Сначала удаляем отклики
    $stmt = $pdo->prepare("DELETE FROM responses WHERE ads_id = ?");
    $stmt->execute([$id]);

    // Затем само объявление
    $stmt = $pdo->prepare("DELETE FROM ads WHERE ads_id = ?");
    $stmt->execute([$id]);

} catch (PDOException $e) {
    error_log($e->getMessage());
}

header('Location: admin.php');
exit;
