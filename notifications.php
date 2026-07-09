<?php
// client/notifications.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
require_once '../config/config.php';

$user_id = $_SESSION['user_id'];

// ==========================================
// RÉCUPÉRATION DU PROFIL RÉEL
// ==========================================
try {
    $stmt_user = $pdo->prepare("SELECT name, role, avatar FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $current_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $current_user = null;
}

$user_name = !empty($current_user['name']) ? $current_user['name'] : 'Citoyen';
$user_role = !empty($current_user['role']) ? $current_user['role'] : 'client';

if (!empty($current_user['avatar'])) {
    $user_avatar = (strpos($current_user['avatar'], 'http') === 0) ? $current_user['avatar'] : "../uploads/avatars/" . $current_user['avatar'];
} else {
    $user_avatar = "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";
}
// ==========================================

if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $update_stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ?");
    $update_stmt->execute([$user_id]);
    header('Location: notifications.php');
    exit();
}

$filter = $_GET['filter'] ?? 'all';

$query = "SELECT * FROM notifications WHERE user_id = :user_id";
if ($filter === 'unread') {
    $query .= " AND status = 'unread'";
} elseif ($filter === 'read') {
    $query .= " AND status = 'read'";
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute(['user_id' => $user_id]);
$notifications_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_all = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$count_all->execute([$user_id]);
$total_count = $count_all->fetchColumn();

$count_unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
$count_unread->execute([$user_id]);
$unread_count = $count_unread->fetchColumn();

$read_count = $total_count - $unread_count;

// Fonctions de simulation (Adapte-les si tu as ton propre fichier d'inclusion)
function getPeriodText($date) {
    $diff = time() - strtotime($date);
    if ($diff < 86400) return "Aujourd'hui";
    if ($diff < 172800) return "Hier";
    return "Plus ancien";
}
function formatNotifTime($date) {
    return date('H:i', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SenegalSet — Centre de Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #007f47;
            --primary-hover: #006639;
            --bg-light: #f8fafc;
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
            color: #0f172a;
        }

        /* SIDEBAR PREMIUM PC */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: var(--sidebar-width);
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            z-index: 100;
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 40px;
            padding-left: 12px;
            letter-spacing: -0.5px;
        }
        .sidebar-brand span { color: #0f172a; font-weight: 500; }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            font-size: 14.5px;
            border-radius: 12px;
            margin-bottom: 6px;
            transition: all 0.2s ease;
        }
        .sidebar-link i { font-size: 18px; }
        .sidebar-link:hover {
            background-color: #f1f5f9;
            color: var(--primary-color);
        }
        .sidebar-link.active {
            background-color: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 127, 71, 0.15);
        }

        /* MAIN CONTENT RESPONSIVE */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            min-height: 100vh;
        }

        /* TOP BAR PC */
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .user-badge {
            background-color: #e2f5ec;
            color: var(--primary-color);
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: capitalize;
        }

        /* CONTROL PANEL */
        .control-panel {
            background: #ffffff;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        
        /* FILTERS BAR ADJUSTMENT */
        .filters-container {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
        }
        .filters-container::-webkit-scrollbar {
            display: none; /* Cache la scrollbar moche sur mobile */
        }
        
        .filter-btn {
            border-radius: 10px;
            font-weight: 600;
            font-size: 13.5px;
            padding: 8px 16px;
            border: 1px solid transparent;
            background-color: #f1f5f9;
            color: #64748b;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .filter-btn:hover { background-color: #e2e8f0; color: #334155; }
        .filter-btn.active {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        .search-box {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }
        .search-box input {
            background: transparent;
            border: none;
            outline: none;
            font-size: 13.5px;
            width: 100%;
            color: #334155;
        }

        /* CARDS NOTIFICATIONS */
        .notification-card {
            border-radius: 16px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0 !important;
            padding: 20px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }
        .notification-card.unread-bg {
            background-color: #f8fafc;
            border-left: 4px solid var(--primary-color) !important;
        }

        .icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .dot-active {
            width: 8px;
            height: 8px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: inline-block;
        }

        /* EMPTY STATE */
        .empty-state-box {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 48px 24px;
            text-align: center;
        }
        .empty-state-icon {
            width: 64px;
            height: 64px;
            background-color: #f1f5f9;
            color: #94a3b8;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 16px;
        }

        /* MOBILE HEADER & NAVIGATION */
        .bottom-nav, .mobile-header { display: none; }

        /* KEY MEDIA QUERIES (BREAKPOINTS RESPONSIVE) */
        @media (max-width: 991px) {
            .sidebar { display: none; }
            .main-content { 
                margin-left: 0; 
                padding: 16px; 
                padding-bottom: 90px; 
            }
            .mobile-header {
                display: block;
                background-color: var(--primary-color);
                color: #ffffff;
                padding: 16px 20px;
                border-radius: 14px;
                margin-bottom: 20px;
            }
            .desktop-header { display: none; }
            
            .bottom-nav {
                display: flex; 
                position: fixed; 
                bottom: 0; 
                left: 0; 
                width: 100%;
                background: #ffffff; 
                border-top: 1px solid #e2e8f0; 
                z-index: 1000;
                justify-content: space-around; 
                padding: 10px 0;
                box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
            }
            .nav-item-box { 
                text-align: center; 
                color: #94a3b8; 
                text-decoration: none; 
                font-size: 11px; 
                font-weight: 500;
            }
            .nav-item-box i { font-size: 20px; display: block; margin-bottom: 2px; }
            .nav-item-box.active { color: var(--primary-color); font-weight: 600; }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR PC -->
    <aside class="sidebar">
        <a href="espace_client.php" class="sidebar-brand">
            Senegal<span>Set</span>
        </a>
        <ul class="sidebar-menu">
            <li><a href="espace_client.php" class="sidebar-link"><i class="bi bi-house-door"></i> Accueil</a></li>
            <li><a href="signalements.php" class="sidebar-link"><i class="bi bi-megaphone"></i> Signaler une anomalie</a></li>
            <li><a href="carte.php" class="sidebar-link"><i class="bi bi-map"></i> Carte Interactive</a></li>
            <li>
                <a href="notifications.php" class="sidebar-link active">
                    <i class="bi bi-bell"></i> Notifications
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger ms-auto rounded-pill"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="profil.php" class="sidebar-link"><i class="bi bi-person"></i> Mon Profil</a></li>
        </ul>
        <div class="pt-3 border-top">
            <a href="../auth/logout.php" class="sidebar-link text-danger mb-0"><i class="bi bi-box-arrow-left"></i> Déconnexion</a>
        </div>
    </aside>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">
        
        <!-- HEADER MOBILE -->
        <div class="mobile-header shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold">Notifications</h5>
                <div class="position-relative">
                    <i class="bi bi-bell-fill fs-4"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger" style="padding: 4px 6px; font-size: 9px;"><?= $unread_count ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- HEADER PC -->
        <div class="desktop-header d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="page-title mb-1">Centre de Notifications</h1>
                <p class="text-muted m-0">Gérez vos alertes de citoyenneté et le suivi de vos signalements.</p>
            </div>
            <div class="d-flex align-items-center gap-3 bg-white p-2 pe-3 border rounded-4 shadow-sm">
                <img src="<?= htmlspecialchars($user_avatar) ?>" class="rounded-3" width="42" height="42" alt="Avatar" style="object-fit: cover;">
                <div>
                    <span class="fw-bold d-block text-dark" style="font-size: 14px;"><?= htmlspecialchars($user_name) ?></span>
                    <span class="user-badge">Espace <?= htmlspecialchars($user_role) ?></span>
                </div>
            </div>
        </div>

        <!-- COMPOSANT RECHERCHE ET FILTRES REGROUPÉS (FIX RESPONSIVE GRIDS) -->
        <div class="control-panel mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-xl-7">
                    <div class="filters-container">
                        <a href="notifications.php?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                            Toutes <span class="badge <?= $filter === 'all' ? 'bg-white text-dark' : 'bg-light text-secondary' ?> rounded-pill"><?= $total_count ?></span>
                        </a>
                        <a href="notifications.php?filter=unread" class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>">
                            Non lues <span class="badge bg-warning text-white rounded-pill"><?= $unread_count ?></span>
                        </a>
                        <a href="notifications.php?filter=read" class="filter-btn <?= $filter === 'read' ? 'active' : '' ?>">
                            Lues <span class="badge bg-light text-secondary rounded-pill"><?= $read_count ?></span>
                        </a>
                    </div>
                </div>
                <div class="col-12 col-xl-5">
                    <div class="search-box">
                        <i class="bi bi-search text-muted"></i>
                        <input type="text" placeholder="Rechercher une notification...">
                    </div>
                </div>
            </div>
        </div>

        <!-- ZONE CHRONOLOGIE & ACTION RAPIDE -->
        <div class="d-flex justify-content-between align-items-center mb-3 px-1">
            <span class="fw-bold text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">Chronologie des événements</span>
            <?php if ($unread_count > 0): ?>
                <a href="notifications.php?action=mark_all_read" class="btn btn-link text-success fw-semibold p-0 text-decoration-none small d-flex align-items-center gap-1" style="font-size: 13px;">
                    <i class="bi bi-check2-all fs-5"></i> Tout marquer en lu
                </a>
            <?php endif; ?>
        </div>

        <!-- LISTE DES NOTIFICATIONS -->
        <div class="notifications-wrapper">
            <?php if (empty($notifications_db)): ?>
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-bell-slash"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Aucune notification pour le moment</h5>
                    <p class="text-muted mx-auto mb-0" style="max-width: 420px; font-size: 13.5px;">
                        Votre fil d'actualités est à jour. Les retours sur vos signalements et les alertes municipales apparaîtront ici.
                    </p>
                </div>
            <?php else: ?>
                <?php 
                $current_period = '';
                foreach ($notifications_db as $notif): 
                    $notif_period = getPeriodText($notif['created_at']);
                    if ($current_period !== $notif_period): 
                        $current_period = $notif_period;
                ?>
                    <h6 class="fw-bold mt-4 mb-3 text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; px-1"><?= htmlspecialchars($current_period) ?></h6>
                <?php endif; ?>

                    <div class="card notification-card <?= $notif['status'] === 'unread' ? 'unread-bg' : '' ?>">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="icon-box <?= htmlspecialchars($notif['icon_class'] ?? 'bg-light text-secondary') ?>">
                                <i class="bi <?= htmlspecialchars($notif['icon'] ?? 'bi-bell') ?>"></i>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;"> <!-- prevent flex overflow -->
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                                    <h6 class="fw-bold mb-0 text-dark text-truncate" style="font-size: 15px; max-width: 80%;"><?= htmlspecialchars($notif['title']) ?></h6>
                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        <small class="text-muted" style="font-size: 12px;"><?= formatNotifTime($notif['created_at']) ?></small>
                                        <?php if ($notif['status'] === 'unread'): ?>
                                            <span class="dot-active"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-muted mb-2 text-break" style="font-size: 13.5px; line-height: 1.5;">
                                    <?= $notif['description'] ?>
                                </p>
                                <?php if (!empty($notif['badge_text'])): ?>
                                    <span class="badge py-1.5 px-2.5 rounded-3 fw-semibold <?= htmlspecialchars($notif['badge_class']) ?>" style="font-size: 11px;">
                                        <?= $notif['badge_text'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <!-- FOOTER NAVIGATION MOBILE -->
    <nav class="bottom-nav">
        <a href="espace_client.php" class="nav-item-box"><i class="bi bi-house-door"></i> Accueil</a>
        <a href="signalements.php" class="nav-item-box"><i class="bi bi-megaphone"></i> Signaler</a>
        <a href="carte.php" class="nav-item-box"><i class="bi bi-map"></i> Carte</a>
        <a href="notifications.php" class="nav-item-box active position-relative">
            <i class="bi bi-bell-fill"></i> Notifications
            <?php if ($unread_count > 0): ?>
                <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-danger" style="margin-left: 10px; padding: 2px 5px; font-size: 8px;"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="profil.php" class="nav-item-box"><i class="bi bi-person"></i> Profil</a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>