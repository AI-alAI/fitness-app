<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Récupérer l'id_coach correspondant à l'utilisateur connecté
$stmt = $db->prepare("SELECT id_coach FROM coachs WHERE id_utilisateur = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$coach = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$coach) {
    die("Erreur : Vous n'êtes pas enregistré comme coach.");
}
$coach_id = $coach['id_coach'];

// Statistiques générales
// Programmes créés par ce coach
$stmt = $db->prepare("SELECT COUNT(*) as total FROM programmes WHERE id_coach = :coach");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$total_programmes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Utilisateurs assignés à ces programmes
$stmt = $db->prepare("SELECT COUNT(DISTINCT a.id_utilisateur) as total
                      FROM assignations a
                      JOIN programmes p ON a.id_programme = p.id_programme
                      WHERE p.id_coach = :coach");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre total d'exercices associés aux programmes du coach
$stmt = $db->prepare("SELECT COUNT(DISTINCT pe.id_exercice) as total
                      FROM programme_exercices pe
                      JOIN programmes p ON pe.id_programme = p.id_programme
                      WHERE p.id_coach = :coach");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$total_exercices = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre total de séances réalisées par tous les utilisateurs du coach
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

// Derniers utilisateurs actifs (avec dernière séance)
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

// Données pour le graphique (nombre de séances par jour sur les 7 derniers jours, tous utilisateurs confondus)
$labels = [];
$seances_per_day = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($date));
    
    $stmt = $db->prepare("SELECT COUNT(s.id_seance) as count
                          FROM seances s
                          JOIN utilisateurs u ON s.id_utilisateur = u.id_utilisateur
                          JOIN assignations a ON u.id_utilisateur = a.id_utilisateur
                          JOIN programmes p ON a.id_programme = p.id_programme
                          WHERE p.id_coach = :coach AND s.date_seance = :date");
    $stmt->bindParam(':coach', $coach_id);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $seances_per_day[] = $result['count'] ?? 0;
}

// Messages récents non lus (utilisateurs -> coach)
$stmt = $db->prepare("SELECT m.*, u.nom, u.prenom 
                      FROM messages m
                      JOIN utilisateurs u ON m.id_expediteur = u.id_utilisateur
                      WHERE m.id_destinataire = :user_id AND m.lu = 0
                      ORDER BY m.date_envoi DESC LIMIT 5");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$recent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Programmes récents du coach
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
            <div>
                <p class="text-gray-500 text-sm">Programmes créés</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_programmes; ?></p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <i class="fas fa-clipboard-list text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Utilisateurs suivis</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_users; ?></p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-users text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Exercices total</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_exercices; ?></p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-dumbbell text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Séances totales</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_seances; ?></p>
            </div>
            <div class="bg-orange-100 rounded-full p-3">
                <i class="fas fa-calendar-check text-orange-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Graphique d'activité -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Activité des clients (7 derniers jours)</h2>
    <canvas id="activityChart" height="100"></canvas>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Derniers utilisateurs actifs -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">Derniers utilisateurs actifs</h2>
            <a href="suivi_users.php" class="text-purple-600 hover:text-purple-800">Voir tout →</a>
        </div>
        <?php if (count($recent_users) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recent_users as $user): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-semibold"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500"><?php echo $user['total_seances_user']; ?> séance(s)</p>
                            <p class="text-xs text-gray-500">Dernière: <?php echo $user['derniere_seance'] ? date('d/m/Y', strtotime($user['derniere_seance'])) : 'Aucune'; ?></p>
                            <a href="suivi_users.php?user_id=<?php echo $user['id_utilisateur']; ?>" class="text-sm text-purple-600 hover:underline">Voir progression</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Aucun utilisateur assigné pour le moment</p>
        <?php endif; ?>
    </div>

    <!-- Messages récents non lus -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">Messages non lus</h2>
            <a href="messages.php" class="text-purple-600 hover:text-purple-800">Voir tous →</a>
        </div>
        <?php if (count($recent_messages) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recent_messages as $msg): ?>
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

<!-- Programmes récents -->
<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-800">Programmes récents</h2>
        <a href="programmes.php" class="text-purple-600 hover:text-purple-800">Gérer →</a>
    </div>
    <?php if (count($recent_programmes) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($recent_programmes as $prog): ?>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-semibold"><?php echo htmlspecialchars($prog['titre']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($prog['date_creation'])); ?> | <?php echo number_format($prog['price'], 2); ?> €</p>
                    </div>
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
    const ctx = document.getElementById('activityChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Nombre de séances',
                data: <?php echo json_encode($seances_per_day); ?>,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
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
</script>

<?php include '../include/footer.php'; ?>