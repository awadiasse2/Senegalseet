<?php
// admin/signalements.php
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
$success_msg = "";

// 2. ACTIONS : UPDATE STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_report_status') {
    $report_id = intval($_POST['report_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');

    if ($report_id > 0 && !empty($new_status)) {
        try {
            $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $report_id]);
            $success_msg = "Le statut du signalement #$report_id a été mis à jour." ;
        } catch (PDOException $e) {
            $error_msg = "Erreur : " . $e->getMessage();
        }
    }
}

// 3. RÉCUPÉRATION DES DONNÉES
try {
    $query = "SELECT r.id as report_id, r.status as report_status, r.created_at as reported_at, r.description,
                     z.name as zone_name, c.name as category_name, u.name as user_name
              FROM reports r
              LEFT JOIN zones z ON r.zone_id = z.id
              LEFT JOIN categories c ON r.category_id = c.id
              LEFT JOIN users u ON r.user_id = u.id
              ORDER BY r.created_at DESC";
    $reports = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reports = [];
    $error_msg = "Erreur de chargement : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Signalements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --bg-sidebar: #06152d; --emerald: #00a651; }
        .sidebar { width: 260px; height: 100vh; background: var(--bg-sidebar); position: fixed; top: 0; left: 0; z-index: 1000; transition: 0.3s; }
        main { margin-left: 260px; padding: 24px; transition: 0.3s; }
        
        /* Mobile Responsive */
        @media (max-width: 991px) {
            .sidebar { left: -260px; }
            .sidebar.active { left: 0; }
            main { margin-left: 0; width: 100% !important; padding-top: 70px; }
            .menu-toggle { display: block !important; }
        }
        .menu-toggle { display: none; position: fixed; top: 15px; right: 15px; z-index: 1100; }
    </style>
</head>
<body>

<button class="btn btn-success menu-toggle" id="toggleBtn"><i class="bi bi-list"></i></button>

<div class="d-flex">
    <aside class="sidebar p-3" id="sidebar">
        <h6 class="text-white mb-4">SENEGALSET</h6>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="Dashboard.php" class="nav-link text-white"><i class="bi bi-grid"></i> Dashboard</a></li>
            <li class="nav-item"><a href="signalements.php" class="nav-link text-white bg-success"><i class="bi bi-exclamation-triangle"></i> Signalements</a></li>
            <li class="nav-item"><a href="equipes_terrain.php" class="nav-link text-white"><i class="bi bi-shield-shaded"></i> Équipes</a></li>
            <li class="nav-item"><a href="interventions.php" class="nav-link text-white"><i class="bi bi-activity"></i> Interventions</a></li>
        </ul>
    </aside>

    <main class="flex-grow-1">
        <h4>Gestion des Signalements</h4>
        <?php if($success_msg): ?><div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div><?php endif; ?>
        
        <div class="card p-3 shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>ID</th><th>Citoyen</th><th>Détails</th><th>Statut</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $rep): ?>
                        <tr>
                            <td>#REP-<?= $rep['report_id'] ?></td>
                            <td><?= htmlspecialchars($rep['user_name'] ?? 'Anonyme') ?></td>
                            <td><?= htmlspecialchars($rep['description'] ?? '') ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($rep['report_status'] ?? 'En attente') ?></span></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_report_status">
                                    <input type="hidden" name="report_id" value="<?= $rep['report_id'] ?>">
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="En attente" <?= ($rep['report_status'] ?? '') === 'En attente' ? 'selected' : '' ?>>En attente</option>
                                        <option value="En cours" <?= ($rep['report_status'] ?? '') === 'En cours' ? 'selected' : '' ?>>En cours</option>
                                        <option value="Résolu" <?= ($rep['report_status'] ?? '') === 'Résolu' ? 'selected' : '' ?>>Résolu</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
