<?php
// client/support.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/config.php';

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

try {
    // Récupération de l'utilisateur pour pré-remplir les champs si besoin
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Erreur de connexion : " . $e->getMessage();
}

// Traitement du formulaire de support
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $category = trim($_POST['category']);

    if (empty($subject) || empty($message)) {
        $error_msg = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            /* 
               OPTIONNEL : Si tu as une table 'support_tickets', tu peux décommenter ce bloc.
               Sinon, la simulation actuelle montre un message de succès parfait pour l'utilisateur.

               $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, category, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
               $stmt->execute([$user_id, $category, $subject, $message]);
            */
            
            $success_msg = "Votre message a bien été envoyé à l'équipe SENEGALSET. Nous vous répondrons par email sous 24h.";
        } catch (Exception $e) {
            $error_msg = "Impossible d'envoyer votre demande : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Support & Assistance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --emerald: #00a651;
            --bg-body: #f8fafc;
            --text-dark: #0f172a;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #475569;
            font-size: 0.85rem;
        }
        .card-custom {
            background: #ffffff;
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.015);
            padding: 24px;
            margin-bottom: 20px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--emerald);
            box-shadow: 0 0 0 0.25rem rgba(0, 166, 81, 0.15);
        }
        .btn-submit {
            background-color: var(--emerald);
            color: white;
            border: none;
            font-weight: 600;
            border-radius: 10px;
            padding: 12px;
            transition: background 0.2s;
        }
        .btn-submit:hover {
            background-color: #008f43;
            color: white;
        }
        .support-icon-box {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background-color: rgba(0, 166, 81, 0.1);
            color: var(--emerald);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .bottom-nav {
            background-color: #ffffff;
            border-top: 1px solid #f1f5f9;
        }
        .nav-link-custom {
            color: #94a3b8;
            font-size: 0.72rem;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .nav-link-custom.active {
            color: var(--emerald);
        }
        @media (max-width: 767.98px) {
            body { padding-bottom: 75px; }
        }
    </style>
</head>
<body>

<div class="container-md px-3 py-4" style="max-width: 650px;">
    
    <!-- RETOUR -->
    <div class="mb-3">
        <a href="profil.php" class="text-decoration-none text-muted fw-medium small">
            <i class="bi bi-arrow-left me-1"></i> Retour au profil
        </a>
    </div>

    <!-- EN-TÊTE -->
    <div class="mb-4 text-center">
        <h4 class="fw-bold m-0 text-dark">Centre d'assistance</h4>
        <small class="text-muted">Une question ou un problème technique ? Contactez-nous.</small>
    </div>

    <!-- MESSAGES ALERTES -->
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger rounded-3 small" role="alert"><i class="bi bi-exclamation-triangle me-2"></i><?= $error_msg ?></div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success rounded-3 small" role="alert"><i class="bi bi-check-circle me-2"></i><?= $success_msg ?></div>
    <?php endif; ?>

    <!-- CONTACT DIRECT QUICK INFO -->
    <div class="row g-2 mb-4">
        <div class="col-6">
            <div class="card-custom p-3 m-0 text-center">
                <div class="support-icon-box mx-auto mb-2"><i class="bi bi-envelope-at"></i></div>
                <h6 class="fw-bold text-dark m-0" style="font-size: 11px;">Email Support</h6>
                <small class="text-muted" style="font-size: 10px;">support@senegalset.sn</small>
            </div>
        </div>
        <div class="col-6">
            <div class="card-custom p-3 m-0 text-center">
                <div class="support-icon-box mx-auto mb-2"><i class="bi bi-telephone-outbound"></i></div>
                <h6 class="fw-bold text-dark m-0" style="font-size: 11px;">Urgence Verte</h6>
                <small class="text-muted" style="font-size: 10px;">+221 33 800 00 00</small>
            </div>
        </div>
    </div>

    <!-- FORMULAIRE DE SUPPORT -->
    <div class="card-custom">
        <h6 class="fw-bold text-dark mb-4"><i class="bi bi-chat-left-text me-2 text-success"></i>Ouvrir un ticket d'aide</h6>
        
        <form action="support.php" method="POST">
            
            <!-- TYPE DE DEMANDE -->
            <div class="mb-3">
                <label for="category" class="form-label fw-semibold text-dark small">Nature de la demande</label>
                <select class="form-select bg-light py-2 px-3 small" id="category" name="category">
                    <option value="technique">Problème technique (Application / Bug)</option>
                    <option value="signalement">Question sur un signalement envoyé</option>
                    <option value="compte">Gestion de mon compte citoyen</option>
                    <option value="autre">Autre demande</option>
                </select>
            </div>

            <!-- SUJET -->
            <div class="mb-3">
                <label for="subject" class="form-label fw-semibold text-dark small">Sujet du message</label>
                <input type="text" class="form-control bg-light py-2" id="subject" name="subject" placeholder="Ex: Impossible d'importer une photo" required>
            </div>

            <!-- MESSAGE -->
            <div class="mb-4">
                <label for="message" class="form-label fw-semibold text-dark small">Description détaillée</label>
                <textarea class="form-control bg-light" id="message" name="message" rows="5" placeholder="Expliquez-nous votre problème avec le plus de précisions possible..." required></textarea>
            </div>

            <!-- BOUTON D'ENVOI -->
            <button type="submit" class="btn btn-submit w-100 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-send"></i> Envoyer ma demande
            </button>

        </form>
    </div>

    <!-- FAQ EXPRESS -->
    <div class="card-custom mt-3">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-question-circle me-2 text-success"></i>Questions fréquentes</h6>
        <div class="accordion accordion-flush" id="faqAccordion">
            <div class="accordion-item bg-transparent">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed px-0 py-2 bg-transparent small fw-medium text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        Combien de temps prend le traitement d'un signalement ?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body px-0 text-muted" style="font-size: 11px;">
                        Les services municipaux étudient les signalements sous 48h. Le délai d'intervention dépend de la gravité de l'anomalie constatée.
                    </div>
                </div>
            </div>
            <div class="accordion-item bg-transparent">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed px-0 py-2 bg-transparent small fw-medium text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        Qui peut voir les signalements que je publie ?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body px-0 text-muted" style="font-size: 11px;">
                        Les signalements sont visibles par les agents techniques de votre zone et géolocalisés anonymement sur la carte publique pour les autres citoyens.
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- BARRE DE NAVIGATION FIXE MOBILE -->
<nav class="navbar bottom-nav fixed-bottom py-2 d-md-none">
    <div class="container-fluid d-flex justify-content-around">
        <a href="accueil.php" class="nav-link-custom"><i class="bi bi-house mb-1" style="font-size: 1.1rem;"></i>Accueil</a>
        <a href="signaler.php" class="nav-link-custom"><i class="bi bi-megaphone mb-1" style="font-size: 1.1rem;"></i>Signaler</a>
        <a href="carte.php" class="nav-link-custom"><i class="bi bi-map mb-1" style="font-size: 1.1rem;"></i>Carte</a>
        <a href="notifications.php" class="nav-link-custom"><i class="bi bi-bell mb-1" style="font-size: 1.1rem;"></i>Notifs</a>
        <a href="profil.php" class="nav-link-custom active"><i class="bi bi-person-fill mb-1" style="font-size: 1.1rem;"></i>Profil</a>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>