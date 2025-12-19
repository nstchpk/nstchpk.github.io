<?php
require_once 'config.php';
session_start();
require_once 'db_connect.php';

// Проверка: только админ
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] ?? null) !== 1) {
    header('Location: index.php');
    exit;
}

try {
    // Получаем статистику
    $total_ads = $pdo->query("SELECT COUNT(*) as count FROM ads")->fetch()['count'];
    $pending_ads = $pdo->query("SELECT COUNT(*) as count FROM ads WHERE is_verified = 0")->fetch()['count'];
    $total_users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    $total_responses = $pdo->query("SELECT COUNT(*) as count FROM responses")->fetch()['count'];

    // Все объявления с пагинацией
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $sql = "
        SELECT ads.*, users.user_name, users.user_email,
               COUNT(responses.responses_id) as response_count
        FROM ads
        JOIN users ON ads.user_id = users.user_id
        LEFT JOIN responses ON ads.ads_id = responses.ads_id
        GROUP BY ads.ads_id
        ORDER BY ads.is_verified ASC, ads.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $ads = $stmt->fetchAll();

    // Общее количество для пагинации
    $total_sql = "SELECT COUNT(*) as count FROM ads";
    $total_count = $pdo->query($total_sql)->fetch()['count'];
    $total_pages = ceil($total_count / $limit);

} catch (PDOException $e) {
    $ads = [];
    $total_ads = $total_users = $pending_ads = $total_responses = 0;
    $total_pages = 1;
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Сайт объявлений</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-page">
    <!-- Хедер админки -->
    <header class="admin-header">
        <div class="container admin-nav">
            <div class="admin-user-info">
                <div class="admin-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                </div>
                <span class="admin-name">
                    Администратор: <?= htmlspecialchars($_SESSION['user_name']) ?>
                </span>
            </div>
            
            <div class="admin-links">
                <a href="index.php" class="admin-link">На сайт</a>
                <a href="admin.php" class="admin-link">Панель управления</a>
                <a href="logout.php" class="admin-link">Выход</a>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <div class="admin-container">
            <h1 class="admin-title">Панель управления</h1>
            
            <!-- Статистика -->
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-value"><?= $total_ads ?></div>
                    <div class="stat-label">Всего объявлений</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $pending_ads ?></div>
                    <div class="stat-label">На модерации</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $total_users ?></div>
                    <div class="stat-label">Пользователей</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $total_responses ?></div>
                    <div class="stat-label">Откликов</div>
                </div>
            </div>
            
            <!-- Быстрые действия -->
            <div class="quick-actions">
                <a href="add.php" class="quick-action-btn">
                    <span>➕</span>
                    <span>Добавить объявление</span>
                </a>
                <a href="admin_users.php" class="quick-action-btn">
                    <span>👥</span>
                    <span>Управление пользователями</span>
                </a>
                <a href="admin_settings.php" class="quick-action-btn">
                    <span>⚙️</span>
                    <span>Настройки</span>
                </a>
            </div>
            
            <!-- Фильтры и поиск -->
            <div class="admin-filters">
                <div class="filter-group">
                    <span class="filter-label">Статус:</span>
                    <select class="filter-select" onchange="filterByStatus(this.value)">
                        <option value="all">Все</option>
                        <option value="pending">На модерации</option>
                        <option value="approved">Одобренные</option>
                    </select>
                </div>
                
                <input type="text" class="search-input" placeholder="Поиск по названию..." id="searchInput">
                <button class="search-btn" onclick="searchAds()">Поиск</button>
            </div>
            
            <!-- Таблица объявлений -->
            <div class="admin-table-container">
                <?php if (empty($ads)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <h3>Объявлений нет</h3>
                        <p>Пока нет объявлений для модерации.</p>
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Заголовок</th>
                                <th>Автор</th>
                                <th>Цена</th>
                                <th>Отклики</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ads as $ad): ?>
                                <tr>
                                    <td>#<?= $ad['ads_id'] ?></td>
                                    <td>
                                        <a href="detail.php?id=<?= $ad['ads_id'] ?>" class="ad-link">
                                            <?= htmlspecialchars($ad['ads_title']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($ad['user_name']) ?></td>
                                    <td><?= number_format($ad['ads_price'], 0, '', ' ') ?> ₽</td>
                                    <td><?= $ad['response_count'] ?></td>
                                    <td>
                                        <span class="status-badge <?= $ad['is_verified'] ? 'status-approved' : 'status-pending' ?>">
                                            <?= $ad['is_verified'] ? '✅ Одобрено' : '⏳ На модерации' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d.m.Y', strtotime($ad['created_at'])) ?></td>
                                    <td class="actions-cell">
                                        <a href="detail.php?id=<?= $ad['ads_id'] ?>" 
                                           class="action-btn view-btn" 
                                           title="Просмотр">
                                            👁️
                                        </a>
                                        
                                        <?php if (!$ad['is_verified']): ?>
                                            <a href="approve_ad.php?id=<?= $ad['ads_id'] ?>" 
                                               class="action-btn approve-btn"
                                               onclick="return confirm('Одобрить объявление?')"
                                               title="Одобрить">
                                                ✅
                                            </a>
                                        <?php else: ?>
                                            <a href="disapprove_ad.php?id=<?= $ad['ads_id'] ?>" 
                                               class="action-btn reject-btn"
                                               onclick="return confirm('Снять с публикации?')"
                                               title="Снять">
                                                ❌
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="edit_ad.php?id=<?= $ad['ads_id'] ?>" 
                                           class="action-btn edit-btn"
                                           title="Редактировать">
                                            ✏️
                                        </a>
                                        
                                        <a href="delete_ad.php?id=<?= $ad['ads_id'] ?>" 
                                           class="action-btn delete-btn"
                                           onclick="return confirm('Удалить объявление?')"
                                           title="Удалить">
                                            🗑️
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Пагинация -->
            <?php if ($total_pages > 1): ?>
            <div class="admin-pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="page-btn">←</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?= $i ?>" 
                           class="page-btn <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span class="page-btn disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="page-btn">→</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Фильтрация по статусу
        function filterByStatus(status) {
            if (status === 'all') {
                window.location.href = 'admin.php';
            } else {
                window.location.href = `admin.php?status=${status}`;
            }
        }
        
        // Поиск объявлений
        function searchAds() {
            const query = document.getElementById('searchInput').value;
            if (query.trim()) {
                window.location.href = `admin.php?search=${encodeURIComponent(query)}`;
            }
        }
        
        // Быстрая фильтрация по нажатию Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchAds();
            }
        });
        
        // Показ уведомлений
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `admin-notification notification-${type}`;
            notification.innerHTML = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Проверка параметров URL для показа уведомлений
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('approved')) {
            showNotification('Объявление успешно одобрено!', 'success');
        }
        if (urlParams.has('deleted')) {
            showNotification('Объявление успешно удалено!', 'info');
        }
        if (urlParams.has('error')) {
            showNotification('Произошла ошибка!', 'error');
        }
    </script>
</body>
</html>