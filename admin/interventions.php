<?php
// admin/interventions.php
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

// 2. RÉCUPÉRATION DES INTERVENTIONS
try {
    $query = "SELECT r.id as report_id, r.status as report_status, r.created_at as reported_at, r.description,
                     z.name as zone_name,
                     c.name as category_name
              FROM reports r
              LEFT JOIN zones z ON r.zone_id = z.id
              LEFT JOIN categories c ON r.category_id = c.id
              ORDER BY r.created_at DESC";
              
    $interventions = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $interventions = [];
    $error_msg = "Erreur lors du chargement du journal : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Journal des Interventions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --bg-sidebar: #06152d; --bg-body: #f3f6f9; --emerald: #00a651; }
        body { font-family: sans-serif; background-color: var(--bg-body); font-size: 0.82rem; }
        
        .sidebar { width: 260px; height: 100vh; background-color: var(--bg-sidebar); position: fixed; top: 0; left: 0; z-index: 1000; transition: 0.3s; }
        main { margin-left: 260px; padding: 24px; transition: 0.3s; }
        
        /* Responsive Mobile */
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
    <aside class="sidebar p-3 d-flex flex-column" id="sidebar">
        <h6 class="text-white mb-4">SENEGALSET</h6>
        <ul class="nav flex-column mb-auto">
            <li class="nav-item"><a href="Dashboard.php" class="nav-link text-white"><i class="bi bi-grid"></i> Dashboard</a></li>
            <li class="nav-item"><a href="interventions.php" class="nav-link text-white bg-success"><i class="bi bi-activity"></i> Interventions</a></li>
        </ul>
    </aside>

    <main class="flex-grow-1">
        <h4>Journal des Interventions</h4>
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>ID</th><th>Nature</th><th>Secteur</th><th>Date</th><th>État</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interventions as $int): ?>
                        <tr>
                            <td>#INT-<?= $int['report_id'] ?></td>
                            <td><?= htmlspecialchars($int['category_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($int['zone_name'] ?? 'N/A') ?></td>
                            <td><?= date('d/m/Y', strtotime($int['reported_at'])) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($int['report_status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    document.getElementById('toggleBtn').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
</body>
</html>
```[cite: 3]