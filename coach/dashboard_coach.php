<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Récupérer le nombre de programmes créés par ce coach
$query = "SELECT COUNT(*) as total_programmes FROM programmes WHERE id_coach = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$total_programmes = $stmt->fetch(PDO::FETCH_ASSOC)['total_programmes'];

// Nombre d'utilisateurs assignés aux programmes de ce coach
$query = "SELECT COUNT(DISTINCT a.id_utilisateur) as total_users
          FROM assignations a
          JOIN programmes p ON a.id_programme = p.id_programme
          WHERE p.id_coach = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

// Derniers utilisateurs actifs
$query = "SELECT DISTINCT u.id_utilisateur, u.nom, u.prenom, u.email, MAX(s.date_seance) as derniere_seance
          FROM utilisateurs u
          LEFT JOIN seances s ON u.id_utilisateur = s.id_utilisateur
          JOIN assignations a ON u.id_utilisateur = a.id_utilisateur
          JOIN programmes p ON a.id_programme = p.id_programme
          WHERE p.id_coach = :id
          GROUP BY u.id_utilisateur
          ORDER BY derniere_seance DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Dashboard Coach';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Espace Coach</h1>
    <p class="text-gray-600 mt-2">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
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
</div>

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
                        <p class="text-xs text-gray-500">Dernière séance: <?php echo $user['derniere_seance'] ? date('d/m/Y', strtotime($user['derniere_seance'])) : 'Aucune'; ?></p>
                        <a href="suivi_users.php?user_id=<?php echo $user['id_utilisateur']; ?>" class="text-sm text-purple-600 hover:underline">Voir progression</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-500 text-center py-4">Aucun utilisateur assigné pour le moment</p>
    <?php endif; ?>
</div>

<?php include '../include/footer.php'; ?>