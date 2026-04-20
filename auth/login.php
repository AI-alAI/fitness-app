<?php
// auth/login.php - Page d'accueil des connexions
session_start();
// Si déjà connecté, rediriger vers le bon dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin': header("Location: ../admin/dashboard.php"); break;
        case 'coach': header("Location: ../coach/dashboard_coach.php"); break;
        default: header("Location: ../utilisateur/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Fitness - Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card-choice {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-choice:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="max-w-4xl w-full">
        <div class="text-center text-white mb-8">
            <i class="fas fa-dumbbell text-5xl mb-2"></i>
            <h1 class="text-4xl font-bold">Smart Fitness</h1>
            <p class="text-xl opacity-90">Choisissez votre espace de connexion</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Utilisateur -->
            <a href="login_user.php" class="bg-white rounded-2xl shadow-xl p-6 text-center card-choice">
                <i class="fas fa-user text-4xl text-purple-600 mb-3"></i>
                <h2 class="text-2xl font-bold text-gray-800">Utilisateur</h2>
                <p class="text-gray-500 mt-2">Accédez à vos programmes, exercices et suivez vos progrès.</p>
                <span class="inline-block mt-4 text-purple-600 font-semibold">Se connecter →</span>
            </a>
            <!-- Coach -->
            <a href="login_coach.php" class="bg-white rounded-2xl shadow-xl p-6 text-center card-choice">
                <i class="fas fa-chalkboard-user text-4xl text-green-600 mb-3"></i>
                <h2 class="text-2xl font-bold text-gray-800">Coach</h2>
                <p class="text-gray-500 mt-2">Gérez vos programmes, vos clients et leurs progrès.</p>
                <span class="inline-block mt-4 text-green-600 font-semibold">Se connecter →</span>
            </a>
            <!-- Admin -->
            <a href="login_admin.php" class="bg-white rounded-2xl shadow-xl p-6 text-center card-choice">
                <i class="fas fa-user-shield text-4xl text-gray-700 mb-3"></i>
                <h2 class="text-2xl font-bold text-gray-800">Administrateur</h2>
                <p class="text-gray-500 mt-2">Gestion globale des utilisateurs, coachs et contenus.</p>
                <span class="inline-block mt-4 text-gray-700 font-semibold">Se connecter →</span>
            </a>
        </div>
        <div class="text-center text-white mt-8 text-sm opacity-75">
            <p>Pas encore inscrit ? <a href="register.php" class="underline">Créer un compte utilisateur</a> | <a href="register_coach.php" class="underline">Devenir coach</a></p>
        </div>
    </div>
</body>
</html>