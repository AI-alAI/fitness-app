<?php
// admin/coachs.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Supprimer un coach (supprime également son compte utilisateur grâce à la clé étrangère)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $coach_id = intval($_GET['delete']);
    // On récupère d'abord l'id_utilisateur associé
    $stmt = $db->prepare("SELECT id_utilisateur FROM coachs WHERE id_coach = :id");
    $stmt->bindParam(':id', $coach_id);
    $stmt->execute();
    $coach = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($coach) {
        $user_id = $coach['id_utilisateur'];
        // Supprimer l'utilisateur (les suppressions en cascade feront le reste)
        $stmt2 = $db->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = :id");
        $stmt2->bindParam(':id', $user_id);
        $stmt2->execute();
        $_SESSION['message'] = "Coach supprimé avec succès.";
    } else {
        $_SESSION['message'] = "Coach introuvable.";
    }
    header("Location: coachs.php");
    exit();
}

// Récupérer tous les coachs avec leurs informations
$query = "SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.date_inscription, u.niveau,
                 c.id_coach, c.specialite, c.bio,
                 (SELECT COUNT(*) FROM programmes WHERE id_coach = c.id_coach) as total_programmes,
                 (SELECT COUNT(DISTINCT a.id_utilisateur) FROM assignations a 
                  JOIN programmes p ON a.id_programme = p.id_programme 
                  WHERE p.id_coach = c.id_coach) as total_clients
          FROM utilisateurs u
          JOIN coachs c ON u.id_utilisateur = c.id_utilisateur
          WHERE u.role = 'coach'
          ORDER BY u.date_inscription DESC";
$coachs = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestion des coachs';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Gestion des coachs</h1>
    <p class="text-gray-600 mt-2">Liste des coachs inscrits. Vous pouvez les supprimer (cela supprimera aussi leurs programmes et assignations).</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($coachs as $coach): ?>
        <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold"><?php echo htmlspecialchars($coach['prenom'] . ' ' . $coach['nom']); ?></h3>
                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($coach['email']); ?></p>
                    </div>
                    <a href="?delete=<?php echo $coach['id_coach']; ?>" onclick="return confirm('Supprimer définitivement ce coach ? Tous ses programmes et assignations seront également supprimés.')" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
                <div class="mt-3">
                    <p class="text-sm"><strong>Spécialité :</strong> <?php echo htmlspecialchars($coach['specialite'] ?: 'Non renseignée'); ?></p>
                    <p class="text-sm mt-1"><strong>Bio :</strong> <?php echo nl2br(htmlspecialchars(substr($coach['bio'] ?? '', 0, 100))); ?></p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        <span class="px-2 py-1 bg-gray-100 rounded">Programmes: <?php echo $coach['total_programmes']; ?></span>
                        <span class="px-2 py-1 bg-gray-100 rounded">Clients: <?php echo $coach['total_clients']; ?></span>
                        <span class="px-2 py-1 bg-gray-100 rounded">Niveau: <?php echo ucfirst($coach['niveau']); ?></span>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Inscrit le <?php echo date('d/m/Y', strtotime($coach['date_inscription'])); ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (count($coachs) == 0): ?>
    <div class="bg-gray-100 rounded-xl p-8 text-center text-gray-500">
        <i class="fas fa-chalkboard-user text-4xl mb-2 block"></i>
        Aucun coach inscrit pour le moment.
    </div>
<?php endif; ?>

<?php include '../include/footer.php'; ?>