<?php
// agent/interventions.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
    // header('Location: ../auth/login.php');
    // exit();
}

require_once '../config/config.php';

// Pour les tests, si la session n'est pas définie, on utilise l'ID 8 (Ousmane Niang - Agent)
$agent_id = $_SESSION['user_id'] ?? 8; 
$error_msg = "";
$success_msg = "";

$filter_status = isset($_GET['status']) ? $_GET['status'] : 'toutes';

try {
    // 1. Requête principale adaptée à la BDD seneset
    $query = "SELECT i.id, i.title as intervention_title, i.start_time, i.end_time, i.status, 
                     c.name as category_name, z.name as zone_name
              FROM interventions i
              LEFT JOIN reports r ON i.report_id = r.id
              LEFT JOIN categories c ON r.category_id = c.id
              LEFT JOIN zones z ON r.zone_id = z.id
              WHERE i.agent_id = :agent_id";
    
    if ($filter_status !== 'toutes') {
        $query .= " AND i.status = :status";
    }
    $query .= " ORDER BY i.start_time ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':agent_id', $agent_id, PDO::PARAM_INT);
    if ($filter_status !== 'toutes') {
        $stmt->bindValue(':status', $filter_status, PDO::PARAM_STR);
    }
    $stmt->execute();
    $interventions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Statistiques dynamiques basées sur l'agent connecté
    $count_all = $pdo->query("SELECT COUNT(*) FROM interventions WHERE agent_id = $agent_id")->fetchColumn() ?: 0;
    $count_attente = $pdo->query("SELECT COUNT(*) FROM interventions WHERE agent_id = $agent_id AND status='À venir'")->fetchColumn() ?: 0;
    $count_cours = $pdo->query("SELECT COUNT(*) FROM interventions WHERE agent_id = $agent_id AND status='En cours'")->fetchColumn() ?: 0;
    $count_termine = $pdo->query("SELECT COUNT(*) FROM interventions WHERE agent_id = $agent_id AND status='Terminé'")->fetchColumn() ?: 0;
    $count_retard = $pdo->query("SELECT COUNT(*) FROM interventions WHERE agent_id = $agent_id AND status='En retard'")->fetchColumn() ?: 0;

    // 3. Récupération du nombre de notifications non lues
    $notif_count = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = $agent_id AND status='unread'")->fetchColumn() ?: 0;

    // 4. Récupération des signalements en cours/attente pour alimenter la liste déroulante du formulaire
    $stmt_reports = $pdo->query("SELECT r.id, c.name as category_name, z.name as zone_name 
                                 FROM reports r 
                                 LEFT JOIN categories c ON r.category_id = c.id 
                                 LEFT JOIN zones z ON r.zone_id = z.id 
                                 WHERE r.status IN ('En attente', 'En cours') ORDER BY r.created_at DESC");
    $available_reports = $stmt_reports->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Erreur : " . $e->getMessage();
    $interventions = [];
    $available_reports = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Gestion des Interventions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --emerald: #00a651;
            --bg-body: #f8fafc;
            --sidebar-width: 280px;
            --banner-gradient: linear-gradient(135deg, #0d6efd 0%, #00a651 100%);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
        }

        /* SIDEBAR (Ordinateur) */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            z-index: 100;
            padding: 24px;
        }
        .nav-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #64748b;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            margin-bottom: 8px;
            transition: all 0.2s;
        }
        .nav-menu-item:hover, .nav-menu-item.active {
            background-color: #f0fdf4;
            color: var(--emerald);
        }

        /* CONTENU CENTRAL */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            min-height: 100vh;
        }

        /* Top bar mobile */
        .top-bar-mobile {
            display: none;
            background: #fff;
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .menu-toggle-btn {
            background: none;
            border: none;
            padding: 0;
            color: #1e293b;
        }

        /* Bannière Principale */
        .main-banner {
            background: var(--banner-gradient);
            border-radius: 20px;
            color: white;
            padding: 35px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        /* Mini Cartes de Statut */
        .stat-card-mini {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
            height: 100%;
        }
        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        /* Cartes d'Interventions */
        .intervention-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        .intervention-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.05);
        }
        .img-placeholder-card {
            width: 100%;
            height: 100%;
            min-height: 120px;
            object-fit: cover;
            border-radius: 12px;
        }

        /* Timeline */
        .timeline-container {
            position: relative;
            padding-left: 25px;
            border-left: 2px solid #e2e8f0;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 25px;
        }
        .timeline-dot {
            position: absolute;
            left: -32px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--emerald);
            border: 2px solid #fff;
        }

        /* Bottom Nav Mobile */
        .bottom-nav {
            display: none;
            background-color: #ffffff;
            border-top: 1px solid #f1f5f9;
            z-index: 1050;
        }
        .nav-link-custom {
            color: #94a3b8;
            font-size: 0.75rem;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .nav-link-custom.active {
            color: var(--emerald);
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 991.98px) {
            .sidebar { display: none; }
            .main-content {
                margin-left: 0;
                padding: 20px;
                padding-bottom: 90px;
            }
            .top-bar-mobile {
                display: flex;
            }
            .bottom-nav {
                display: flex;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="d-flex align-items-center gap-2 mb-4 pb-3 border-bottom">
        <div class="rounded-circle bg-success px-2 py-1 text-white fw-bold">SS</div>
        <div>
            <h6 class="m-0 fw-bold text-dark">SENEGALSET</h6>
            <small class="text-muted" style="font-size: 10px;">Espace Opérations</small>
        </div>
    </div>

    <div class="mt-4">
        <a href="index.php" class="nav-menu-item"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="interventions.php" class="nav-menu-item active"><i class="bi bi-tools"></i> Interventions</a>
        <a href="carte.php" class="nav-menu-item"><i class="bi bi-map"></i> Carte Terrain</a>
        <a href="profil.php" class="nav-menu-item"><i class="bi bi-person"></i> Mon Profil</a>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel" style="width: var(--sidebar-width);">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-success px-2 py-1 text-white fw-bold">SS</div>
            <div>
                <h6 class="m-0 fw-bold text-dark" id="mobileMenuLabel">SENEGALSET</h6>
                <small class="text-muted" style="font-size: 10px;">Espace Opérations</small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-4">
        <a href="index.php" class="nav-menu-item"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="interventions.php" class="nav-menu-item active"><i class="bi bi-tools"></i> Interventions</a>
        <a href="carte.php" class="nav-menu-item"><i class="bi bi-map"></i> Carte Terrain</a>
        <a href="profil.php" class="nav-menu-item"><i class="bi bi-person"></i> Mon Profil</a>
    </div>
</div>

<div class="top-bar-mobile justify-content-between align-items-center">
    <button class="menu-toggle-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
        <i class="bi bi-list fs-3"></i>
    </button>
    <h6 class="m-0 fw-bold">SENEGALSET</h6>
    <div class="position-relative">
        <i class="bi bi-bell fs-4"></i>
        <?php if($notif_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger" style="font-size: 0.55rem;"><?= $notif_count ?></span>
        <?php endif; ?>
    </div>
</div>

<div class="main-content">
    <div class="container-fluid p-0">
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger rounded-3" role="alert"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold m-0 text-dark">Gestion des Interventions</h3>
                <p class="text-muted m-0 d-none d-sm-block">Suivi des opérations et répartition sur le terrain</p>
            </div>
            <div class="d-none d-md-block">
                <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalNouvelleIntervention">
                    <i class="bi bi-plus-lg me-2"></i>Nouvelle intervention
                </button>
            </div>
        </div>

        <div class="main-banner mb-4">
            <div class="row align-items-center">
                <div class="col-8 col-md-9">
                    <span class="small text-white-50 d-block uppercase fw-bold mb-1">Total Interventions assignées</span>
                    <h1 class="fw-bold m-0 display-4"><?= $count_all ?></h1>
                </div>
                <div class="col-4 col-md-3 text-end d-block d-md-none">
                    <button class="btn btn-white bg-white text-success fw-bold btn-sm rounded-pill px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalNouvelleIntervention">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-5">
            <div class="col-6 col-lg-3">
                <a href="interventions.php?status=À venir" class="text-decoration-none">
                    <div class="stat-card-mini">
                        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-event"></i></div>
                        <div class="text-muted small">À venir</div>
                        <h3 class="fw-bold text-dark mt-1 mb-0"><?= $count_attente ?></h3>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="interventions.php?status=En cours" class="text-decoration-none">
                    <div class="stat-card-mini">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-gear-fill"></i></div>
                        <div class="text-muted small">En cours</div>
                        <h3 class="fw-bold text-dark mt-1 mb-0"><?= $count_cours ?></h3>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="interventions.php?status=Terminé" class="text-decoration-none">
                    <div class="stat-card-mini">
                        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="text-muted small">Terminées</div>
                        <h3 class="fw-bold text-dark mt-1 mb-0"><?= $count_termine ?></h3>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="interventions.php?status=En retard" class="text-decoration-none">
                    <div class="stat-card-mini">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <div class="text-muted small">En retard</div>
                        <h3 class="fw-bold text-dark mt-1 mb-0"><?= $count_retard ?></h3>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-12 col-xl-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0">Interventions actives</h5>
                    <a href="interventions.php?status=toutes" class="text-decoration-none small text-success fw-bold">Voir tout</a>
                </div>

                <div class="row g-3">
                    <?php if (count($interventions) > 0): ?>
                        <?php foreach($interventions as $task): ?>
                            <?php
                                $badgeClass = 'bg-secondary';
                                if($task['status'] == 'En cours') $badgeClass = 'bg-warning text-dark';
                                elseif($task['status'] == 'Terminé') $badgeClass = 'bg-success';
                                elseif($task['status'] == 'À venir') $badgeClass = 'bg-info text-dark';
                                elseif($task['status'] == 'En retard') $badgeClass = 'bg-danger';
                            ?>
                            <div class="col-12 col-md-6">
                                <div class="intervention-card p-3">
                                    <div class="row g-3 h-100">
                                        <div class="col-4">
                                            <img src="https://images.unsplash.com/photo-1611284446314-60a58ac0deb9?w=200" class="img-placeholder-card" alt="Mission">
                                        </div>
                                        <div class="col-8 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="text-success fw-bold small">#INT-<?= $task['id'] ?></span>
                                                    <span class="badge <?= $badgeClass ?> rounded-pill px-2" style="font-size:0.7rem;"><?= htmlspecialchars($task['status']) ?></span>
                                                </div>
                                                <h6 class="fw-bold text-dark text-truncate mb-1" title="<?= htmlspecialchars($task['intervention_title']) ?>"><?= htmlspecialchars($task['intervention_title']) ?></h6>
                                                
                                                <p class="text-muted small mb-0 text-truncate">
                                                    <i class="bi bi-tag-fill text-primary me-1"></i><?= htmlspecialchars($task['category_name'] ?? 'Non catégorisé') ?>
                                                </p>
                                                <p class="text-muted small mb-0 text-truncate">
                                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($task['zone_name'] ?? 'Zone non définie') ?>
                                                </p>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
                                                <span class="text-muted text-nowrap" style="font-size: 0.75rem;"><i class="bi bi-calendar3 me-1"></i><?= date('d M - H:i', strtotime($task['start_time'])) ?></span>
                                                <a href="action_intervention.php?id=<?= $task['id'] ?>" class="btn btn-light btn-sm rounded-pill border px-3" style="font-size:0.75rem;">Ouvrir</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-5 bg-white rounded-4 border text-muted">
                                <i class="bi bi-emoji-smile fs-1 d-block mb-2 text-success"></i>
                                Aucune intervention pour le statut : <strong><?= htmlspecialchars($filter_status) ?></strong>.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <h5 class="fw-bold mb-3">Dernières Activités</h5>
                <div class="bg-white p-4 rounded-4 border">
                    <div class="timeline-container">
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark small">Débouchage canalisation</span>
                                <small class="text-muted">11h30</small>
                            </div>
                            <span class="badge bg-info text-dark mt-1" style="font-size:0.65rem;">À venir</span>
                        </div>
                        <div class="timeline-item mb-0 pb-0">
                            <div class="timeline-dot bg-warning"></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark small">Nettoyage dépôts</span>
                                <small class="text-muted">08h00</small>
                            </div>
                            <span class="badge bg-warning text-dark mt-1" style="font-size:0.65rem;">En cours</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<nav class="navbar bottom-nav fixed-bottom py-2 shadow-lg">
    <div class="container-fluid d-flex justify-content-around">
        <a href="index.php" class="nav-link-custom"><i class="bi bi-grid mb-1 fs-5"></i>Dashboard</a>
        <a href="interventions.php" class="nav-link-custom active"><i class="bi bi-tools mb-1 fs-5"></i>Missions</a>
        <a href="carte.php" class="nav-link-custom"><i class="bi bi-map mb-1 fs-5"></i>Carte</a>
        <a href="profil.php" class="nav-link-custom"><i class="bi bi-person mb-1 fs-5"></i>Profil</a>
    </div>
</nav>

<div class="modal fade" id="modalNouvelleIntervention" tabindex="-1" aria-labelledby="modalInterventionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="modalInterventionLabel">Créer une intervention</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="ajouter_intervention.php" method="POST">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Titre de l'intervention</label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="Ex: Nettoyage Dépôt Sacré-Cœur" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Associer à un signalement de la BDD</label>
                        <select name="report_id" class="form-select rounded-3">
                            <option value="">-- Aucun (Intervention libre) --</option>
                            <?php foreach($available_reports as $report): ?>
                                <option value="<?= $report['id'] ?>">
                                    #REP-<?= $report['id'] ?> : <?= htmlspecialchars($report['category_name'] ?? 'Inconnu') ?> (<?= htmlspecialchars($report['zone_name'] ?? 'Sans Zone') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Début prévu</label>
                            <input type="datetime-local" name="start_time" class="form-control rounded-3" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Fin estimée</label>
                            <input type="datetime-local" name="end_time" class="form-control rounded-3" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Statut de départ</label>
                        <select name="status" class="form-select rounded-3">
                            <option value="À venir" selected>À venir</option>
                            <option value="En cours">En cours</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">Planifier la mission</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>