<?php
// utilisateur/dashboard.php
require_once '../config/database.php';
require_once '../include/functions.php';

redirectIfNotRole(['utilisateur', 'coach', 'admin']);

$database = new Database();
$db = $database->getConnection();
$user = getUserInfo($db, $_SESSION['user_id']);
$stats = getStats($db, $_SESSION['user_id']);

// Dernières séances
$query = "SELECT * FROM seances WHERE id_utilisateur = :id ORDER BY date_seance DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$recent_seances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Objectifs en cours
$query = "SELECT * FROM objectifs WHERE id_utilisateur = :id AND statut = 'en_cours' ORDER BY date_echeance ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$active_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Tableau de bord';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Bonjour, <?php echo htmlspecialchars($user['prenom']); ?> !</h1>
    <p class="text-gray-600 mt-2">Voici votre résumé fitness aujourd'hui</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Séances totales</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_seances']; ?></p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <i class="fas fa-calendar-check text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Calories brûlées</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_calories']); ?></p>
            </div>
            <div class="bg-orange-100 rounded-full p-3">
                <i class="fas fa-fire text-orange-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Objectifs atteints</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['goals_achieved']; ?></p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-trophy text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Messages non lus</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['unread_messages']; ?></p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-envelope text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Dernières séances -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">Dernières séances</h2>
            <a href="seances.php" class="text-purple-600 hover:text-purple-800">Voir tout →</a>
        </div>
        <?php if (count($recent_seances) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recent_seances as $seance): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-semibold"><?php echo date('d/m/Y', strtotime($seance['date_seance'])); ?></p>
                            <p class="text-sm text-gray-600">Durée: <?php echo $seance['duree']; ?> min | Calories: <?php echo $seance['calories']; ?></p>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Aucune séance enregistrée</p>
        <?php endif; ?>
    </div>
    
    <!-- Objectifs actifs -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">Objectifs en cours</h2>
            <a href="objectifs.php" class="text-purple-600 hover:text-purple-800">Voir tout →</a>
        </div>
        <?php if (count($active_goals) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($active_goals as $goal): ?>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="font-semibold"><?php echo htmlspecialchars($goal['type_objectif']); ?></p>
                        <p class="text-sm text-gray-600">Cible: <?php echo $goal['valeur_cible']; ?></p>
                        <p class="text-xs text-gray-500">Échéance: <?php echo date('d/m/Y', strtotime($goal['date_echeance'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Aucun objectif défini</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../include/footer.php'; ?>