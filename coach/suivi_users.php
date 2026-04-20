<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Get coach ID from logged in user
$stmt = $db->prepare("SELECT id_coach FROM coachs WHERE id_utilisateur = :user");
$stmt->bindParam(':user', $_SESSION['user_id']);
$stmt->execute();
$coach = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$coach) {
    die("Erreur : Vous n'êtes pas enregistré comme coach.");
}
$coach_id = $coach['id_coach'];

// Get all users assigned to this coach (through programmes)
$stmt = $db->prepare("SELECT DISTINCT u.id_utilisateur, u.nom, u.prenom, u.email, u.age, u.poids, u.taille, u.niveau
                      FROM utilisateurs u
                      JOIN assignations a ON u.id_utilisateur = a.id_utilisateur
                      JOIN programmes p ON a.id_programme = p.id_programme
                      WHERE p.id_coach = :coach
                      ORDER BY u.nom");
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : (count($users) > 0 ? $users[0]['id_utilisateur'] : 0);

$selected_user = null;
$user_stats = [];
$sessions_data = [];
$goals_data = [];
$messages_data = [];
$calories_chart_labels = [];
$calories_chart_values = [];
$sessions_chart_values = [];

if ($selected_user_id) {
    // Get selected user details
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = :id");
    $stmt->bindParam(':id', $selected_user_id);
    $stmt->execute();
    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected_user) {
        // Statistics
        // Total sessions
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(duree) as total_duree, SUM(calories) as total_calories FROM seances WHERE id_utilisateur = :id");
        $stmt->bindParam(':id', $selected_user_id);
        $stmt->execute();
        $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Goals (active, achieved, failed)
        $stmt = $db->prepare("SELECT statut, COUNT(*) as count FROM objectifs WHERE id_utilisateur = :id GROUP BY statut");
        $stmt->bindParam(':id', $selected_user_id);
        $stmt->execute();
        $goals_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Recent sessions (last 7 days for chart)
        $stmt = $db->prepare("SELECT date_seance, calories, duree FROM seances 
                              WHERE id_utilisateur = :id AND date_seance >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                              ORDER BY date_seance ASC");
        $stmt->bindParam(':id', $selected_user_id);
        $stmt->execute();
        $sessions_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare data for charts (last 7 days)
        $last7days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $last7days[$date] = ['calories' => 0, 'duree' => 0];
        }
        foreach ($sessions_data as $s) {
            $date = $s['date_seance'];
            if (isset($last7days[$date])) {
                $last7days[$date]['calories'] += $s['calories'];
                $last7days[$date]['duree'] += $s['duree'];
            }
        }
        foreach ($last7days as $date => $values) {
            $calories_chart_labels[] = date('d/m', strtotime($date));
            $calories_chart_values[] = $values['calories'];
            $sessions_chart_values[] = $values['duree'];
        }

        // Active goals (in progress)
        $stmt = $db->prepare("SELECT * FROM objectifs WHERE id_utilisateur = :id AND statut = 'en_cours' ORDER BY date_echeance ASC LIMIT 5");
        $stmt->bindParam(':id', $selected_user_id);
        $stmt->execute();
        $active_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent messages (last 5 between user and coach)
        $stmt = $db->prepare("SELECT m.*, u1.nom as exp_nom, u1.prenom as exp_prenom 
                              FROM messages m
                              JOIN utilisateurs u1 ON m.id_expediteur = u1.id_utilisateur
                              WHERE (m.id_expediteur = :user AND m.id_destinataire = :coach_user)
                                 OR (m.id_expediteur = :coach_user AND m.id_destinataire = :user)
                              ORDER BY m.date_envoi DESC LIMIT 5");
        $coach_user_id = $_SESSION['user_id'];
        $stmt->bindParam(':user', $selected_user_id);
        $stmt->bindParam(':coach_user', $coach_user_id);
        $stmt->execute();
        $recent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $recent_messages = array_reverse($recent_messages); // chronological order for display
    }
}

$page_title = 'Suivi des utilisateurs';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Suivi personnalisé</h1>
    <p class="text-gray-600">Consultez les progrès de vos utilisateurs et gérez leurs objectifs.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- User list sidebar -->
    <div class="lg:col-span-1 bg-white rounded-xl shadow-md p-4">
        <h2 class="font-bold text-lg mb-3">Mes utilisateurs</h2>
        <div class="mb-3">
            <input type="text" id="searchUser" placeholder="Rechercher..." class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:border-purple-500">
        </div>
        <div id="userList" class="space-y-2 max-h-[500px] overflow-y-auto">
            <?php foreach ($users as $u): ?>
                <a href="?user_id=<?php echo $u['id_utilisateur']; ?>" 
                   class="user-item block p-3 rounded-lg hover:bg-purple-50 transition <?php echo ($selected_user_id == $u['id_utilisateur']) ? 'bg-purple-100 border-l-4 border-purple-600' : ''; ?>"
                   data-name="<?php echo strtolower($u['prenom'] . ' ' . $u['nom']); ?>">
                    <p class="font-semibold"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></p>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($u['email']); ?></p>
                    <p class="text-xs text-gray-400">Niveau: <?php echo ucfirst($u['niveau']); ?></p>
                </a>
            <?php endforeach; ?>
            <?php if (count($users) == 0): ?>
                <p class="text-gray-500 text-center py-4">Aucun utilisateur assigné.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- User details panel -->
    <div class="lg:col-span-3 space-y-6">
        <?php if ($selected_user && $selected_user_id): ?>
            <!-- User header -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex flex-wrap justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($selected_user['prenom'] . ' ' . $selected_user['nom']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($selected_user['email']); ?></p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span class="px-2 py-1 bg-gray-100 rounded-full text-xs">Âge: <?php echo $selected_user['age'] ?? '—'; ?></span>
                            <span class="px-2 py-1 bg-gray-100 rounded-full text-xs">Poids: <?php echo $selected_user['poids'] ?? '—'; ?> kg</span>
                            <span class="px-2 py-1 bg-gray-100 rounded-full text-xs">Taille: <?php echo $selected_user['taille'] ?? '—'; ?> cm</span>
                            <span class="px-2 py-1 bg-gray-100 rounded-full text-xs">Niveau: <?php echo ucfirst($selected_user['niveau']); ?></span>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="messages.php?user_id=<?php echo $selected_user_id; ?>" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                            <i class="fas fa-comment mr-1"></i> Message
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-md p-4 text-center">
                    <i class="fas fa-calendar-check text-2xl text-purple-600 mb-1"></i>
                    <p class="text-2xl font-bold"><?php echo $user_stats['total'] ?? 0; ?></p>
                    <p class="text-gray-500 text-sm">Séances totales</p>
                </div>
                <div class="bg-white rounded-xl shadow-md p-4 text-center">
                    <i class="fas fa-clock text-2xl text-blue-600 mb-1"></i>
                    <p class="text-2xl font-bold"><?php echo ($user_stats['total_duree'] ?? 0); ?> min</p>
                    <p class="text-gray-500 text-sm">Durée totale</p>
                </div>
                <div class="bg-white rounded-xl shadow-md p-4 text-center">
                    <i class="fas fa-fire text-2xl text-orange-600 mb-1"></i>
                    <p class="text-2xl font-bold"><?php echo number_format($user_stats['total_calories'] ?? 0); ?></p>
                    <p class="text-gray-500 text-sm">Calories brûlées</p>
                </div>
            </div>

            <!-- Charts (last 7 days) -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold mb-4">Activité récente (7 derniers jours)</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <canvas id="caloriesChart" height="200"></canvas>
                        <p class="text-center text-sm text-gray-500 mt-2">Calories par jour</p>
                    </div>
                    <div>
                        <canvas id="durationChart" height="200"></canvas>
                        <p class="text-center text-sm text-gray-500 mt-2">Durée (minutes) par jour</p>
                    </div>
                </div>
            </div>

            <!-- Active goals -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Objectifs en cours</h3>
                    <a href="objectifs.php?user_id=<?php echo $selected_user_id; ?>" class="text-purple-600 text-sm hover:underline">Voir tous</a>
                </div>
                <?php if (count($active_goals) > 0): ?>
                    <div class="space-y-3">
                        <?php foreach ($active_goals as $goal): ?>
                            <div class="p-3 bg-gray-50 rounded-lg flex justify-between items-center">
                                <div>
                                    <p class="font-semibold"><?php echo htmlspecialchars($goal['type_objectif']); ?></p>
                                    <p class="text-sm text-gray-600">Cible: <?php echo $goal['valeur_cible']; ?></p>
                                    <p class="text-xs text-gray-500">Échéance: <?php echo date('d/m/Y', strtotime($goal['date_echeance'])); ?></p>
                                </div>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">En cours</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Aucun objectif actif</p>
                <?php endif; ?>
            </div>

            <!-- Recent messages -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold mb-4">Derniers messages</h3>
                <?php if (count($recent_messages) > 0): ?>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <?php foreach ($recent_messages as $msg): ?>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex justify-between text-sm">
                                    <span class="font-semibold">
                                        <?php if ($msg['id_expediteur'] == $selected_user_id): ?>
                                            👤 Client:
                                        <?php else: ?>
                                            👨‍🏫 Vous:
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-gray-400 text-xs"><?php echo date('d/m/Y H:i', strtotime($msg['date_envoi'])); ?></span>
                                </div>
                                <p class="text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars(substr($msg['contenu'], 0, 150))); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 text-right">
                        <a href="messages.php?user_id=<?php echo $selected_user_id; ?>" class="text-purple-600 text-sm hover:underline">Voir la conversation →</a>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Aucun message échangé</p>
                <?php endif; ?>
            </div>

        <?php elseif ($selected_user_id && !$selected_user): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg">Utilisateur non trouvé.</div>
        <?php else: ?>
            <div class="bg-gray-100 rounded-xl shadow-md p-8 text-center text-gray-500">
                <i class="fas fa-users text-4xl mb-2 block"></i>
                <p>Sélectionnez un utilisateur dans la liste pour voir ses statistiques.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// User search filter
document.getElementById('searchUser')?.addEventListener('keyup', function() {
    let search = this.value.toLowerCase();
    document.querySelectorAll('.user-item').forEach(item => {
        let name = item.getAttribute('data-name');
        item.style.display = name.includes(search) ? '' : 'none';
    });
});

// Charts (only if data exists)
<?php if ($selected_user && count($calories_chart_labels) > 0): ?>
    new Chart(document.getElementById('caloriesChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($calories_chart_labels); ?>,
            datasets: [{
                label: 'Calories',
                data: <?php echo json_encode($calories_chart_values); ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    new Chart(document.getElementById('durationChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($calories_chart_labels); ?>,
            datasets: [{
                label: 'Minutes',
                data: <?php echo json_encode($sessions_chart_values); ?>,
                backgroundColor: '#8b5cf6',
                borderRadius: 8
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
<?php endif; ?>
</script>

<?php include '../include/footer.php'; ?>