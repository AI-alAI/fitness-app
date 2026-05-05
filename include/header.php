<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_role = $_SESSION['role'] ?? 'guest';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Fitness - <?php echo $page_title ?? 'Application'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <style>
        /* Flexible layout – no fixed height, grows with content */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .sidebar-item { transition: all 0.3s ease; }
        .sidebar-item:hover { transform: translateX(5px); background-color: rgba(255,255,255,0.1); }
        .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .active-sidebar { background-color: #7c3aed; border-left: 4px solid #fbbf24; }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex flex-1">
    <!-- Sidebar – full height, sticky -->
    <div class="w-64 bg-gradient-to-b from-purple-800 to-indigo-900 text-white shadow-xl flex flex-col min-h-screen">
        <div class="p-6 flex-1">
            <div class="flex items-center space-x-3 mb-8">
                <i class="fas fa-dumbbell text-2xl"></i>
                <h1 class="text-xl font-bold">Smart Fitness</h1>
            </div>
            <nav class="space-y-2">
                <?php if ($current_role == 'admin'): ?>
                    <!-- Admin menu -->
                    <a href="../admin/dashboard.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'dashboard.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-tachometer-alt w-5"></i><span>Dashboard Admin</span>
                    </a>
                    <a href="../admin/utilisateurs.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition">
                        <i class="fas fa-users w-5"></i><span>Utilisateurs</span>
                    </a>
                    <a href="../admin/coachs.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition">
                        <i class="fas fa-chalkboard-user w-5"></i><span>Coachs</span>
                    </a>
                    <a href="../admin/exercices.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition">
                        <i class="fas fa-dumbbell w-5"></i><span>Exercices</span>
                    </a>
                    <a href="../admin/programmes.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition">
                        <i class="fas fa-clipboard-list w-5"></i><span>Programmes</span>
                    </a>
                    <a href="../admin/messages.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition">
                        <i class="fas fa-envelope w-5"></i><span>Messages</span>
                    </a>
                    <a href="../admin/reclamations.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition">
                        <i class="fas fa-ticket-alt w-5"></i><span>Réclamations</span>
                    </a>
                <?php elseif ($current_role == 'coach'): ?>
                    <!-- Coach menu -->
                    <a href="../coach/dashboard_coach.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'dashboard_coach.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-tachometer-alt w-5"></i><span>Tableau de bord</span>
                    </a>
                    <a href="../coach/programmes.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'programmes.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-clipboard-list w-5"></i><span>Mes programmes</span>
                    </a>
                    <a href="../coach/assignations.php?programme_id=0" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'assignations.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-user-plus w-5"></i><span>Assigner</span>
                    </a>
                    <a href="../coach/suivi_users.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'suivi_users.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-chart-line w-5"></i><span>Suivi utilisateurs</span>
                    </a>
                    <a href="../coach/conseils.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'conseils.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-lightbulb w-5"></i><span>Conseils IA</span>
                    </a>
                    <a href="../coach/messages.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'messages.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-envelope w-5"></i><span>Messages</span>
                    </a>
                    <a href="../coach/creer_compte.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'creer_compte.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-user-plus w-5"></i><span>Créer un compte</span>
                    </a>
                    <a href="../coach/gestion_exercices.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'gestion_exercices.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-dumbbell w-5"></i><span>Gérer exercices</span>
                    </a>
                    <a href="../coach/profil.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'profil.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-user-circle w-5"></i><span>Mon profil</span>
                    </a>
                    <a href="../coach/reclamations.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'reclamations.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-exclamation-circle w-5"></i><span>Réclamations</span>
                    </a>
                <?php elseif ($current_role == 'utilisateur'): ?>
                    <!-- User menu -->
                    <a href="../utilisateur/dashboard.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'dashboard.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-tachometer-alt w-5"></i><span>Tableau de bord</span>
                    </a>
                    <a href="../utilisateur/programmes_disponibles.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'programmes_disponibles.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-store w-5"></i><span>Formations</span>
                    </a>
                    <a href="../utilisateur/exercices.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'exercices.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-dumbbell w-5"></i><span>Exercices</span>
                    </a>
                    <a href="../utilisateur/seances.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'seances.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-calendar-check w-5"></i><span>Séances</span>
                    </a>
                    <a href="../utilisateur/calendrier.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'calendrier.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-calendar-alt w-5"></i><span>Calendrier</span>
                    </a>
                    <a href="../utilisateur/objectifs.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'objectifs.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-bullseye w-5"></i><span>Objectifs</span>
                    </a>
                    <a href="../utilisateur/messages.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'messages.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-envelope w-5"></i><span>Messages</span>
                    </a>
                    <a href="../utilisateur/profil.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'profil.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-user-circle w-5"></i><span>Mon profil</span>
                    </a>
                    <a href="../utilisateur/reclamations.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'reclamations.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-exclamation-circle w-5"></i><span>Réclamations</span>
                    </a>
                    <a href="../utilisateur/ai_chat.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'ai_chat.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-robot w-5"></i><span>Coach IA</span>
                    </a>
                    <!-- NEW WORKOUT LINKS -->
                    <a href="../utilisateur/workout_log.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'workout_log.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-plus-circle w-5"></i><span>Ajouter workout</span>
                    </a>
                    <a href="../utilisateur/workout_history.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo ($current_page == 'workout_history.php') ? 'active-sidebar' : ''; ?>">
                        <i class="fas fa-history w-5"></i><span>Historique</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <!-- Logout section at bottom -->
        <div class="p-6 border-t border-purple-700">
            <div class="flex items-center space-x-3 mb-3">
                <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center"><i class="fas fa-user"></i></div>
                <div><p class="text-sm font-semibold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Invité'); ?></p><p class="text-xs text-purple-300"><?php echo ucfirst($current_role); ?></p></div>
            </div>
            <a href="../auth/logout.php" class="flex items-center space-x-3 text-purple-300 hover:text-white transition"><i class="fas fa-sign-out-alt"></i><span>Déconnexion</span></a>
        </div>
    </div>
    <!-- Main content – flexible, grows with content -->
    <div class="flex-1">
        <div class="p-8">