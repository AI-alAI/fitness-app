<?php
// utilisateur/dashboard.php
require_once '../config/database.php';
require_once '../include/functions.php';

redirectIfNotRole(['utilisateur', 'coach', 'admin']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$user = getUserInfo($db, $user_id);
$stats = getStats($db, $user_id);

// Dernières séances
$query = "SELECT * FROM seances WHERE id_utilisateur = :id ORDER BY date_seance DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$recent_seances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Objectifs en cours
$query = "SELECT * FROM objectifs WHERE id_utilisateur = :id AND statut = 'en_cours' ORDER BY date_echeance ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$active_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Messages non lus (aperçu)
$query = "SELECT m.*, u.nom, u.prenom 
          FROM messages m
          JOIN utilisateurs u ON m.id_expediteur = u.id_utilisateur
          WHERE m.id_destinataire = :id AND m.lu = 0
          ORDER BY m.date_envoi DESC LIMIT 3";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$unread_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Données pour les graphiques (7 derniers jours)
$labels = [];
$calories_data = [];
$sessions_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($date));
    
    // Calories totales du jour
    $stmt = $db->prepare("SELECT SUM(calories) as total_calories, COUNT(*) as total_sessions 
                          FROM seances 
                          WHERE id_utilisateur = :id AND date_seance = :date");
    $stmt->bindParam(':id', $user_id);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $day_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $calories_data[] = $day_data['total_calories'] ?? 0;
    $sessions_data[] = $day_data['total_sessions'] ?? 0;
}

// Calcul de l'IMC (si poids et taille disponibles)
$bmi = null;
if ($user['poids'] && $user['taille'] && $user['taille'] > 0) {
    $taille_m = $user['taille'] / 100;
    $bmi = round($user['poids'] / ($taille_m * $taille_m), 1);
}
$bmi_status = '';
if ($bmi) {
    if ($bmi < 18.5) $bmi_status = 'Insuffisance pondérale';
    elseif ($bmi < 25) $bmi_status = 'Poids normal';
    elseif ($bmi < 30) $bmi_status = 'Surpoids';
    else $bmi_status = 'Obésité';
}

$page_title = 'Tableau de bord';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Bonjour, <?php echo htmlspecialchars($user['prenom']); ?> !</h1>
    <p class="text-gray-600 mt-2">Voici votre résumé fitness aujourd'hui</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Calories brûlées (7 derniers jours)</h2>
        <canvas id="caloriesChart" height="200"></canvas>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Séances par jour</h2>
        <canvas id="sessionsChart" height="200"></canvas>
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

<!-- BMI & Messages Preview Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <!-- BMI Card -->
    <?php if ($bmi): ?>
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Votre IMC</h2>
                <p class="text-3xl font-bold text-purple-600 mt-2"><?php echo $bmi; ?></p>
                <p class="text-gray-600"><?php echo $bmi_status; ?></p>
            </div>
            <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-weight-scale text-purple-600 text-3xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-500">
            Poids: <?php echo $user['poids']; ?> kg | Taille: <?php echo $user['taille']; ?> cm
        </div>
    </div>
    <?php endif; ?>

    <!-- Messages non lus aperçu -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">Messages récents</h2>
            <a href="messages.php" class="text-purple-600 hover:text-purple-800">Voir tous →</a>
        </div>
        <?php if (count($unread_messages) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($unread_messages as $msg): ?>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="font-semibold"><?php echo htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars(substr($msg['contenu'], 0, 80)); ?>...</p>
                        <p class="text-xs text-gray-400"><?php echo date('d/m/Y H:i', strtotime($msg['date_envoi'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Aucun nouveau message</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Calories chart
    const ctxCalories = document.getElementById('caloriesChart').getContext('2d');
    new Chart(ctxCalories, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Calories',
                data: <?php echo json_encode($calories_data); ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' }
            }
        }
    });

    // Sessions chart
    const ctxSessions = document.getElementById('sessionsChart').getContext('2d');
    new Chart(ctxSessions, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Nombre de séances',
                data: <?php echo json_encode($sessions_data); ?>,
                backgroundColor: '#8b5cf6',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
</script>

<?php include '../include/footer.php'; ?>