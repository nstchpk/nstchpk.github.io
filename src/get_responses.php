<?php
require_once 'config.php';
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['ad_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID объявления не указан']);
    exit;
}

$ad_id = intval($_GET['ad_id']);
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $_SESSION['user_id'] ?? null;

try {
    // Проверяем, имеет ли пользователь доступ к просмотру откликов
    $ad_sql = "SELECT ads.*, users.user_id as author_id 
               FROM ads 
               LEFT JOIN users ON ads.user_id = users.user_id 
               WHERE ads.ads_id = ?";
    $ad_stmt = $pdo->prepare($ad_sql);
    $ad_stmt->execute([$ad_id]);
    $ad = $ad_stmt->fetch();
    
    if (!$ad) {
        echo json_encode(['success' => false, 'message' => 'Объявление не найдено']);
        exit;
    }
    
    // Проверяем права доступа
    $can_view_responses = false;
    
    // Пользователь может видеть отклики если:
    // 1. Он автор объявления
    // 2. Он админ (role_id = 1)
    // 3. Он откликнулся на это объявление
    if ($is_logged_in) {
        // Проверяем, является ли пользователь автором
        if ($current_user_id == $ad['author_id']) {
            $can_view_responses = true;
        }
        
        // Проверяем, является ли пользователь админом
        if (($_SESSION['role_id'] ?? 0) == 1) {
            $can_view_responses = true;
        }
        
        // Проверяем, откликался ли пользователь на это объявление
        if (!$can_view_responses) {
            $check_response_sql = "SELECT responses_id FROM responses WHERE ads_id = ? AND user_id = ?";
            $check_response_stmt = $pdo->prepare($check_response_sql);
            $check_response_stmt->execute([$ad_id, $current_user_id]);
            
            if ($check_response_stmt->fetch()) {
                $can_view_responses = true;
            }
        }
    }
    
    if (!$can_view_responses) {
        echo json_encode([
            'success' => false, 
            'message' => 'Нет прав для просмотра откликов',
            'count' => 0
        ]);
        exit;
    }
    
    // Получаем отклики
    $responses_sql = "SELECT 
                        responses.*,
                        users.user_name,
                        users.user_phone,
                        DATE_FORMAT(responses.created_at, '%d.%m.%Y %H:%i') as formatted_date
                    FROM responses
                    LEFT JOIN users ON responses.user_id = users.user_id
                    WHERE responses.ads_id = ?
                    ORDER BY responses.created_at DESC";
    
    $responses_stmt = $pdo->prepare($responses_sql);
    $responses_stmt->execute([$ad_id]);
    $responses = $responses_stmt->fetchAll();
    
    echo json_encode([
        'success' => true, 
        'responses' => $responses,
        'count' => count($responses)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}
?>