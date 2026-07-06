<?php
// auth/login.php
session_start();
require_once '../config/config.php';

// 1. VERIFICATION SI DEJA CONNECTE
if (isset($_SESSION['user_id'])) {
    $role = trim(strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? ''));

    if ($role === 'admin') {
        header('Location: ../admin/dashboard.php');
        exit(); // Toujours ajouter exit() après une redirection
    } elseif ($role === 'agent_municipale' || $role === 'agent') {
        header('Location: ../agent/index.php');
        exit();
    } else {
        header('Location: ../client/espace_client.php');
        exit();
    }
}

$error_message = "";

// 2. TRAITEMENT DU FORMULAIRE DE CONNEXION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Stockage des variables de session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_role'] = $user['role'];

                $user_role = trim(strtolower($user['role']));
                if ($user_role === 'admin') {
                    header('Location: ../admin/dashboard.php');
                } elseif ($user_role === 'agent_municipale' || $user_role === 'agent') {
                    header('Location: ../agent/index.php');
                } else {
                    header('Location: ../client/espace_client.php');
                }
                exit();
            } else {
                $error_message = "Identifiants ou mot de passe incorrects.";
            }
        } catch (PDOException $e) {
            $error_message = "Une erreur technique est survenue.";
        }
    } else {
        $error_message = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SenegalSet — Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .login-screen {
            width: 100%;
            height: 100%;
            min-height: 100vh;
            background: linear-gradient(rgba(22, 94, 184, 0.88), rgba(14, 61, 117, 0.92)), 
                        url('https://images.unsplash.com/photo-1542435503-956c469947f6?q=80&w=1400') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding-left: 10%;
            position: relative;
            overflow: hidden;
        }

        .logo-corner {
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 230px;
            background: #ffffff;
            border-bottom-left-radius: 230px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-left: 40px;
            padding-bottom: 40px;
            box-shadow: -2px 2px 10px rgba(0,0,0,0.1);
        }

        .logo-corner img {
            width: 170px;
            height: auto;
        }

        .form-container-box {
            background: #ffffff;
            width: 100%;
            max-width: 440px;
            padding: 45px 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            z-index: 5;
        }

        .label-text {
            color: #124d9c;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 8px;
            display: block;
        }

        .input-grey-field {
            background-color: #dcdcdc !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 14px 16px !important;
            font-size: 15px;
            color: #333333;
            width: 100%;
            margin-bottom: 24px;
            outline: none;
        }

        .btn-green-submit {
            background-color: #3ca336 !important;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 16px;
            padding: 12px;
            border: none;
            border-radius: 8px;
            width: 60%;
            margin: 10px auto 16px auto;
            display: block;
            text-align: center;
            text-decoration: none;
        }

        .btn-green-submit:hover {
            background-color: #2e822a !important;
        }

        .link-forgot {
            color: #124d9c;
            font-weight: 600;
            text-decoration: none;
            font-size: 15px;
            display: block;
            text-align: center;
        }

        .right-text-block {
            position: absolute;
            left: 55%;
            top: 35%;
            max-width: 480px;
            color: #ffffff;
        }

        .right-title {
            font-size: 44px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 12px;
        }

        .green-divider {
            width: 180px;
            height: 6px;
            background-color: #3ca336;
            margin-bottom: 40px;
        }

        .register-prompt-text {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 14px;
        }

        .btn-outline-register, .btn-outline-index {
            border: 2px solid #ffffff;
            color: #ffffff;
            background: transparent;
            font-weight: 600;
            padding: 10px 32px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-size: 15px;
            margin-right: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .btn-outline-register:hover, .btn-outline-index:hover {
            background: #ffffff;
            color: #124d9c;
        }

        .error-alert-box {
            background-color: #fce8e6;
            color: #cc0000;
            font-size: 13px;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: left;
            border-left: 3px solid #cc0000;
        }

        @media (max-width: 991px) {
            .login-screen {
                padding-left: 0;
                justify-content: center;
                flex-direction: column;
            }
            .right-text-block, .logo-corner {
                display: none;
            }
            .form-container-box {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>

    <div class="login-screen">
        
        <div class="logo-corner">
            <img src="../assets/img/logo.png" alt="SenegalSet Logo">
        </div>

        <div class="form-container-box">
            <?php if (!empty($error_message)): ?>
                <div class="error-alert-box">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <span class="label-text">Email:</span>
                <input type="email" name="email" class="input-grey-field" required autocomplete="email">

                <span class="label-text">Mot de passe</span>
                <input type="password" name="password" class="input-grey-field" required>

                <button type="submit" class="btn-green-submit">Se connecter</button>

                <a href="forgot_password.php" class="link-forgot">Mot de passe oublié</a>
            </form>
        </div>

        <div class="right-text-block">
            <h1 class="right-title">Connexion à votre compte</h1>
            <div class="green-divider"></div>
            
            <p class="register-prompt-text">Vous n'avez pas de compte ?</p>
            <a href="register.php" class="btn-outline-register">Créer un compte</a>
            <a href="../index.php" class="btn-outline-index">Retour à l'accueil</a>
        </div>

    </div>

</body>
</html>