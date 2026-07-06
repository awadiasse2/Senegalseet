<?php
// admin/calendrier.php
session_start();

// 1. SÉCURITÉ : Connexion et rôle Admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
$user_role = trim(strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? ''));
if ($user_role !== 'admin') {
    header('Location: ../client/espace_client.php');
    exit();
}

require_once '../config/config.php';

// Variables Administrateur connecté
$admin_name = $_SESSION['user_name'] ?? 'Admin Sénégalset';
$admin_avatar = !empty($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80';

$error_msg = "";

// 2. RÉCUPÉRATION DES INTERVENTIONS PLANIFIÉES
try {
    $query = "SELECT i.id as int_id, i.title, i.start_time, i.end_time, i.status as int_status,
                     t.name as team_name,
                     z.name as zone_name,
                     c.name as category_name
              FROM interventions i
              LEFT JOIN teams t ON i.team_id = t.id
              LEFT JOIN reports r ON i.report_id = r.id
              LEFT JOIN zones z ON r.zone_id = z.id
              LEFT JOIN categories c ON r.category_id = c.id
              ORDER BY i.start_time ASC";
              
    $calendar_events = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $calendar_events = [];
    $error_msg = "Erreur lors du chargement de l'agenda : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Calendrier des Interventions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-sidebar: #06152d; --bg-body: #f3f6f9; --emerald: #00a651; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); font-size: 0.82rem; color: #334155; }
        
        .sidebar { width: 260px; min-height: 100vh; background-color: var(--bg-sidebar); padding: 20px 15px; position: fixed; top: 0; left: 0; z-index: 1000; transition: 0.3s; }
        main { margin-left: 260px; padding: 24px; transition: 0.3s; }
        .sidebar .nav-link { color: #94a3b8; padding: 8px 12px; border-radius: 6px; display: flex; align-items: center; text-decoration: none; margin-bottom: 2px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: var(--emerald); }
        .sidebar-title { font-size: 0.68rem; font-weight: 700; color: #475569; text-uppercase; letter-spacing: 0.05em; padding: 12px 12px 4px; }
        .card-custom { background: #fff; border-radius: 12px; padding: 24px; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .event-card { border-left: 4px solid #64748b; background-color: #f8fafc; padding: 12px 16px; border-radius: 0 8px 8px 0; margin-bottom: 12px; transition: transform 0.2s; }
        
        /* Mobile Responsive */
        @media (max-width: 991px) {
            .sidebar { left: -260px; }
            .sidebar.active { left: 0; }
            main { margin-left: 0; width: 100% !important; }
            .menu-toggle { display: block !important; }
        }
        .menu-toggle { display: none; position: fixed; top: 15px; right: 15px; z-index: 1100; }
    </style>
</head>
<body>

<button class="btn btn-success menu-toggle" id="toggleBtn"><i class="bi bi-list"></i></button>

<div class="d-flex">
    <aside class="sidebar d-flex flex-column justify-content-between" id="sidebar">
        <div>
            <div class="d-flex align-items-center gap-2 mb-4 px-2">
                <div class="rounded bg-success px-2 py-1 text-white fw-bold" style="font-size:14px;">SS</div>
                <div><h6 class="m-0 fw-bold text-white">SENEGALSET</h6></div>
            </div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item"><a href="Dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill me-2"></i> Tableau de bord</a></li>
            </ul>
            <div class="sidebar-title">Opérations Terrain</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item"><a href="signalements.php" class="nav-link"><i class="bi bi-exclamation-triangle-fill me-2"></i> Signalements</a></li>
                <li class="nav-item"><a href="equipes_terrain.php" class="nav-link"><i class="bi bi-shield-shaded me-2"></i> Équipes</a></li>
                <li class="nav-item"><a href="interventions.php" class="nav-link"><i class="bi bi-activity me-2"></i> Interventions</a></li>
            </ul>
        </div>
    </aside>

    <main class="flex-grow-1 p-4">
        <header class="mb-4">
            <h4 class="fw-bold m-0 text-dark">Planning des Missions d'Assainissement</h4>
        </header>

        <div class="card-custom">
            <div class="timeline">
                <?php foreach($calendar_events as $event): 
                    $status = trim($event['int_status'] ?? 'À venir');
                ?>
                <div class="event-card">
                    <h6 class="fw-bold"><?= htmlspecialchars($event['title'] ?? '') ?></h6>
                    <small><?= htmlspecialchars($event['start_time'] ?? '') ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<script>
    document.getElementById('toggleBtn').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('active'); });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>