<?php
// coach/dashboard_coach.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Récupérer l'id_coach
$stmt = $db->prepare("SELECT id_coach FROM coachs WHERE id_utilisateur = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$coach = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$coach) {
    die("Erreur : Vous n'êtes pas enregistré comme coach.");
}
$coach_id = $coach['id_coach'];

// --- Statistiques générales ---
$stmt = $db->prepare("SELECT COUNT(*) as total FROM programmes WHERE id_coach = :coach");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$total_programmes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare("SELECT COUNT(DISTINCT a.id_utilisateur) as total
                      FROM assignations a
                      JOIN programmes p ON a.id_programme = p.id_programme
                      WHERE p.id_coach = :coach");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare("SELECT COUNT(DISTINCT pe.id_exercice) as total
                      FROM programme_exercices pe
                      JOIN programmes p ON pe.id_programme = p.id_programme
                      WHERE p.id_coach = :coach");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$total_exercices = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare("SELECT COUNT(s.id_seance) as total_seances, SUM(s.calories) as total_calories
                      FROM seances s
                      JOIN utilisateurs u ON s.id_utilisateur = u.id_utilisateur
                      JOIN assignations a ON u.id_utilisateur = a.id_utilisateur
                      JOIN programmes p ON a.id_programme = p.id_programme
                      WHERE p.id_coach = :coach");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$seances_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$total_seances = $seances_stats['total_seances'] ?? 0;
$total_calories = $seances_stats['total_calories'] ?? 0;

// --- Données pour le camembert : calories par programme ---
$stmt = $db->prepare("SELECT p.titre, SUM(s.calories) as calories_programme
                      FROM seances s
                      JOIN utilisateurs u ON s.id_utilisateur = u.id_utilisateur
                      JOIN assignations a ON u.id_utilisateur = a.id_utilisateur
                      JOIN programmes p ON a.id_programme = p.id_programme
                      WHERE p.id_coach = :coach
                      GROUP BY p.id_programme
                      ORDER BY calories_programme DESC");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$programme_calories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pie_labels = [];
$pie_data = [];
foreach ($programme_calories as $pc) {
    $pie_labels[] = $pc['titre'];
    $pie_data[] = $pc['calories_programme'];
}

if (empty($pie_labels)) {
    $pie_labels = ['Aucune donnée'];
    $pie_data = [1];
}

// --- Derniers utilisateurs actifs ---
$stmt = $db->prepare("SELECT DISTINCT u.id_utilisateur, u.nom, u.prenom, u.email, 
                             MAX(s.date_seance) as derniere_seance,
                             (SELECT COUNT(*) FROM seances WHERE id_utilisateur = u.id_utilisateur) as total_seances_user
                      FROM utilisateurs u
                      LEFT JOIN seances s ON u.id_utilisateur = s.id_utilisateur
                      JOIN assignations a ON u.id_utilisateur = a.id_utilisateur
                      JOIN programmes p ON a.id_programme = p.id_programme
                      WHERE p.id_coach = :coach
                      GROUP BY u.id_utilisateur
                      ORDER BY derniere_seance DESC LIMIT 5");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Messages récents non lus ---
$stmt = $db->prepare("SELECT m.*, u.nom, u.prenom 
                      FROM messages m
                      JOIN utilisateurs u ON m.id_expediteur = u.id_utilisateur
                      WHERE m.id_destinataire = :user_id AND m.lu = 0
                      ORDER BY m.date_envoi DESC LIMIT 5");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$recent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Programmes récents du coach ---
$stmt = $db->prepare("SELECT id_programme, titre, date_creation, price 
                      FROM programmes 
                      WHERE id_coach = :coach 
                      ORDER BY date_creation DESC LIMIT 5");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$recent_programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Dashboard Coach';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Espace Coach</h1>
    <p class="text-gray-600 mt-2">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div><p class="text-gray-500 text-sm">Programmes créés</p><p class="text-3xl font-bold"><?php echo $total_programmes; ?></p></div>
            <div class="bg-purple-100 rounded-full p-3"><i class="fas fa-clipboard-list text-purple-600 text-2xl"></i></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div><p class="text-gray-500 text-sm">Utilisateurs suivis</p><p class="text-3xl font-bold"><?php echo $total_users; ?></p></div>
            <div class="bg-green-100 rounded-full p-3"><i class="fas fa-users text-green-600 text-2xl"></i></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div><p class="text-gray-500 text-sm">Exercices total</p><p class="text-3xl font-bold"><?php echo $total_exercices; ?></p></div>
            <div class="bg-blue-100 rounded-full p-3"><i class="fas fa-dumbbell text-blue-600 text-2xl"></i></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div><p class="text-gray-500 text-sm">Séances totales</p><p class="text-3xl font-bold"><?php echo $total_seances; ?></p></div>
            <div class="bg-orange-100 rounded-full p-3"><i class="fas fa-calendar-check text-orange-600 text-2xl"></i></div>
        </div>
    </div>
</div>

<!-- Camembert (répartition des calories par programme) - plus petit et centré -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Calories brûlées par programme</h2>
    <?php if (count($programme_calories) > 0): ?>
        <div class="flex justify-center">
            <div class="max-w-sm w-full">
                <canvas id="caloriesPieChart" height="200"></canvas>
            </div>
        </div>
    <?php else: ?>
        <p class="text-gray-500 text-center py-8">Aucune donnée de séance disponible pour vos programmes.</p>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Derniers utilisateurs actifs -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Derniers utilisateurs actifs</h2>
            <a href="suivi_users.php" class="text-purple-600">Voir tout →</a>
        </div>
        <?php if (count($recent_users) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recent_users as $user): ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <div><p class="font-semibold"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></p><p class="text-sm text-gray-600"><?php echo $user['email']; ?></p></div>
                        <div class="text-right"><p class="text-xs text-gray-500"><?php echo $user['total_seances_user']; ?> séance(s)</p><a href="suivi_users.php?user_id=<?php echo $user['id_utilisateur']; ?>" class="text-sm text-purple-600">Progression →</a></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Aucun utilisateur assigné</p>
        <?php endif; ?>
    </div>

    <!-- Messages non lus -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Messages non lus</h2>
            <a href="messages.php" class="text-purple-600">Voir tous →</a>
        </div>
        <?php if (count($recent_messages) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recent_messages as $msg): ?>
                    <div class="p-3 bg-gray-50 rounded"><p class="font-semibold"><?php echo htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']); ?></p><p class="text-sm"><?php echo htmlspecialchars(substr($msg['contenu'], 0, 80)); ?>...</p><p class="text-xs text-gray-400"><?php echo date('d/m/Y H:i', strtotime($msg['date_envoi'])); ?></p></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Aucun nouveau message</p>
        <?php endif; ?>
    </div>
</div>

<!-- Programmes récents -->
<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Programmes récents</h2>
        <a href="programmes.php" class="text-purple-600">Gérer →</a>
    </div>
    <?php if (count($recent_programmes) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($recent_programmes as $prog): ?>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div><p class="font-semibold"><?php echo htmlspecialchars($prog['titre']); ?></p><p class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($prog['date_creation'])); ?> | <?php echo number_format($prog['price'], 2); ?> €</p></div>
                    <a href="programmes.php?edit=<?php echo $prog['id_programme']; ?>" class="text-purple-600"><i class="fas fa-edit"></i></a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-500 text-center py-4">Aucun programme créé</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php if (count($programme_calories) > 0): ?>
    const ctx = document.getElementById('caloriesPieChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($pie_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($pie_data); ?>,
                backgroundColor: [
                    '#8b5cf6', '#f59e0b', '#10b981', '#3b82f6', '#ef4444',
                    '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw} calories` } }
            }
        }
    });
    <?php endif; ?>
</script>

<?php include '../include/footer.php'; ?>