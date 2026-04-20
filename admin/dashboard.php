<?php
// admin/dashboard.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Statistiques globales
$total_users = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'utilisateur'")->fetchColumn();
$total_coaches = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'coach'")->fetchColumn();
$total_admins = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'")->fetchColumn();
$total_programmes = $db->query("SELECT COUNT(*) FROM programmes")->fetchColumn();
$total_exercices = $db->query("SELECT COUNT(*) FROM exercices")->fetchColumn();
$total_seances = $db->query("SELECT COUNT(*) FROM seances")->fetchColumn();
$total_assignations = $db->query("SELECT COUNT(*) FROM assignations")->fetchColumn();

// Revenu total (somme des prix des programmes assignés)
$revenue = $db->query("SELECT SUM(p.price) FROM assignations a JOIN programmes p ON a.id_programme = p.id_programme")->fetchColumn();
$revenue = $revenue ?: 0;

// --- Graphique 1 : Évolution des inscriptions (30 derniers jours) ---
$labels_inscriptions = [];
$data_inscriptions = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels_inscriptions[] = date('d/m', strtotime($date));
    $stmt = $db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE DATE(date_inscription) = :date");
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $data_inscriptions[] = $stmt->fetchColumn();
}

// --- Graphique 2 : Programmes les plus populaires (top 5 par nombre d'assignations) ---
$popular_programmes = $db->query("SELECT p.titre, COUNT(a.id_assignation) as nb 
                                  FROM programmes p 
                                  LEFT JOIN assignations a ON p.id_programme = a.id_programme 
                                  GROUP BY p.id_programme 
                                  ORDER BY nb DESC LIMIT 5")->fetchAll();
$popular_labels = array_column($popular_programmes, 'titre');
$popular_data = array_column($popular_programmes, 'nb');

// --- Graphique 3 : Revenu par mois (6 derniers mois) ---
$monthly_labels = [];
$monthly_revenue = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_label = date('M Y', strtotime($month . '-01'));
    $monthly_labels[] = $month_label;
    $stmt = $db->prepare("SELECT SUM(p.price) 
                          FROM assignations a 
                          JOIN programmes p ON a.id_programme = p.id_programme 
                          WHERE DATE_FORMAT(a.date_assignation, '%Y-%m') = :month");
    $stmt->bindParam(':month', $month);
    $stmt->execute();
    $monthly_revenue[] = $stmt->fetchColumn() ?: 0;
}

$page_title = 'Dashboard Admin';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Administration</h1>
    <p class="text-gray-600">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
</div>

<!-- Cartes statistiques -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start">
            <div><p class="text-gray-500 text-sm">Utilisateurs</p><p class="text-3xl font-bold"><?php echo $total_users; ?></p></div>
            <i class="fas fa-users text-3xl text-blue-500"></i>
        </div>
        <div class="text-sm text-gray-500 mt-2">Coachs: <?php echo $total_coaches; ?> | Admins: <?php echo $total_admins; ?></div>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start">
            <div><p class="text-gray-500 text-sm">Programmes</p><p class="text-3xl font-bold"><?php echo $total_programmes; ?></p></div>
            <i class="fas fa-clipboard-list text-3xl text-purple-500"></i>
        </div>
        <div class="text-sm text-gray-500 mt-2">Exercices: <?php echo $total_exercices; ?></div>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start">
            <div><p class="text-gray-500 text-sm">Séances</p><p class="text-3xl font-bold"><?php echo $total_seances; ?></p></div>
            <i class="fas fa-calendar-check text-3xl text-green-500"></i>
        </div>
        <div class="text-sm text-gray-500 mt-2">Assignations: <?php echo $total_assignations; ?></div>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start">
            <div><p class="text-gray-500 text-sm">Revenu estimé</p><p class="text-3xl font-bold"><?php echo number_format($revenue, 2); ?> €</p></div>
            <i class="fas fa-euro-sign text-3xl text-yellow-500"></i>
        </div>
    </div>
</div>

<!-- Graphiques en ligne -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Évolution des inscriptions -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Inscriptions (30 derniers jours)</h2>
        <canvas id="inscriptionsChart" height="200"></canvas>
    </div>
    <!-- Programmes populaires -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Programmes les plus populaires</h2>
        <canvas id="popularChart" height="200"></canvas>
    </div>
</div>

<!-- Revenu mensuel -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h2 class="text-xl font-bold mb-4">Revenu par mois (6 derniers mois)</h2>
    <canvas id="revenueChart" height="100"></canvas>
</div>

<!-- Actions rapides -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <a href="utilisateurs.php" class="bg-purple-100 rounded-xl p-6 text-center hover:bg-purple-200 transition">
        <i class="fas fa-users text-3xl text-purple-600 mb-2"></i>
        <p class="font-bold">Gérer les utilisateurs</p>
    </a>
    <a href="coachs.php" class="bg-green-100 rounded-xl p-6 text-center hover:bg-green-200 transition">
        <i class="fas fa-chalkboard-user text-3xl text-green-600 mb-2"></i>
        <p class="font-bold">Gérer les coachs</p>
    </a>
    <a href="exercices.php" class="bg-blue-100 rounded-xl p-6 text-center hover:bg-blue-200 transition">
        <i class="fas fa-dumbbell text-3xl text-blue-600 mb-2"></i>
        <p class="font-bold">Gérer les exercices</p>
    </a>
    <a href="programmes.php" class="bg-yellow-100 rounded-xl p-6 text-center hover:bg-yellow-200 transition">
        <i class="fas fa-clipboard-list text-3xl text-yellow-600 mb-2"></i>
        <p class="font-bold">Gérer les programmes</p>
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Graphique des inscriptions
    new Chart(document.getElementById('inscriptionsChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels_inscriptions); ?>,
            datasets: [{
                label: 'Nouveaux utilisateurs',
                data: <?php echo json_encode($data_inscriptions); ?>,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    // Graphique des programmes populaires (barres)
    new Chart(document.getElementById('popularChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($popular_labels); ?>,
            datasets: [{
                label: 'Nombre d\'assignations',
                data: <?php echo json_encode($popular_data); ?>,
                backgroundColor: '#f59e0b',
                borderRadius: 8
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    // Graphique du revenu mensuel (ligne)
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($monthly_labels); ?>,
            datasets: [{
                label: 'Revenu (€)',
                data: <?php echo json_encode($monthly_revenue); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.raw} €` } } } }
    });
</script>

<?php include '../include/footer.php'; ?>