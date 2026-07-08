<?php
// agent/dashboard.php
session_start();
require_once '../config/config.php';

// Protection : vérifier si l'utilisateur est bien un agent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
    // Rediriger vers login si nécessaire
}

$agent_id = $_SESSION['user_id'] ?? 8; 

try {
    // 1. STATISTIQUES CORRIGÉES (Basées sur votre structure réelle)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM interventions WHERE agent_id = ?");
    $stmt->execute([$agent_id]);
    $total_interventions = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM interventions WHERE agent_id = ? AND status = 'En cours'");
    $stmt->execute([$agent_id]);
    $assigned_interventions = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM interventions WHERE agent_id = ? AND status = 'Terminé'");
    $stmt->execute([$agent_id]);
    $completed_interventions = $stmt->fetchColumn();

    // 2. RÉCUPÉRATION DES INTERVENTIONS AVEC JOINTURE SQL
    $query = "SELECT i.id, i.title, i.start_time, i.status, r.description, r.latitude, r.longitude 
              FROM interventions i 
              LEFT JOIN reports r ON i.report_id = r.id 
              WHERE i.agent_id = ? 
              ORDER BY i.start_time DESC LIMIT 10";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$agent_id]);
    $interventions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>SENEGALSET - Espace Agent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #1e3a3a; --bg-main: #f8fafc; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-main); display: flex; }
        .sidebar { width: 260px; height: 100vh; background: var(--sidebar-bg); color: white; position: fixed; padding: 20px; }
        .nav-link { color: #cbd5e1; padding: 12px; border-radius: 8px; margin-bottom: 5px; }
        .nav-link.active { background: #2d5a5a; color: white; }
        .main-content { margin-left: 260px; width: 100%; padding: 30px; }
        .card { border-radius: 12px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-box { font-size: 1.5rem; font-weight: bold; }
    </style>
</head>
<body>

<nav class="sidebar">
    <h4 class="text-white mb-4">SENEGALSET</h4>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="bi bi-house me-2"></i> Tableau de bord</a>
        <a href="interventions.php" class="nav-link"><i class="bi bi-tools me-2"></i> Mes Interventions</a>
        <a href="carte.php" class="nav-link"><i class="bi bi-map me-2"></i> Carte Terrain</a>
        <a href="notifications.php" class="nav-link"><i class="bi bi-bell me-2"></i> Notifications</a>
        <a href="parametres.php" class="nav-link"><i class="bi bi-gear me-2"></i> Paramètres</a>
        <a href="support.php" class="nav-link"><i class="bi bi-question-circle me-2"></i> Support</a>
        <a href="historique.php" class="nav-link"><i class="bi bi-clock-history me-2"></i> Historique</a>
        <a href="profil.php" class="nav-link"><i class="bi bi-person me-2"></i> Mon Profil</a>
    </div>
    <div class="mt-auto position-absolute bottom-0 p-3">
        <div class="text-white small">Session Active<br><span class="text-success">● En ligne</span></div>
        <a href="../auth/logout.php" class="btn btn-outline-light btn-sm mt-2">Déconnexion</a>
    </div>
</nav>

<main class="main-content">
    <h3 class="fw-bold">Tableau de bord</h3>
    <p class="text-muted">Suivi opérationnel des missions sur le terrain</p>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-4">
                <small class="text-muted">Total Interventions</small>
                <div class="stat-box"><?= $total_interventions ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4">
                <small class="text-muted">En cours</small>
                <div class="stat-box text-warning"><?= $assigned_interventions ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4">
                <small class="text-muted">Terminées</small>
                <div class="stat-box text-success"><?= $completed_interventions ?></div>
            </div>
        </div>
    </div>

    <div class="card p-4">
        <h5 class="mb-3">Interventions récentes</h5>
        <table class="table table-hover align-middle">
            <thead class="text-muted small text-uppercase">
                <tr><th>ID</th><th>Mission</th><th>Localisation</th><th>Statut</th><th class="text-end">Action</th></tr>
            </thead>
            <tbody>
                <?php foreach($interventions as $task): ?>
                <tr>
                    <td class="fw-bold">#<?= $task['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($task['title']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($task['description'] ?? 'Pas de description') ?></small>
                    </td>
                    <td><?= $task['latitude'] ? round($task['latitude'], 4) . ' / ' . round($task['longitude'], 4) : 'N/A' ?></td>
                    <td>
                        <span class="badge <?= $task['status'] === 'Terminé' ? 'bg-success' : 'bg-warning' ?> bg-opacity-10 text-dark">
                            <?= htmlspecialchars($task['status']) ?>
                        </span>
                    </td>
                    <td class="text-end"><a href="action_intervention.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-outline-primary">Gérer</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
