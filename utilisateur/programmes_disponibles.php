<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['utilisateur']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Handle purchase
if (isset($_GET['buy']) && is_numeric($_GET['buy'])) {
    $programme_id = intval($_GET['buy']);
    // Check if already bought
    $check = $db->prepare("SELECT id_assignation FROM assignations WHERE id_utilisateur = :user AND id_programme = :prog");
    $check->bindParam(':user', $user_id);
    $check->bindParam(':prog', $programme_id);
    $check->execute();
    if ($check->rowCount() == 0) {
        $stmt = $db->prepare("INSERT INTO assignations (id_programme, id_utilisateur, date_assignation, statut) VALUES (:prog, :user, NOW(), 'en_cours')");
        $stmt->bindParam(':prog', $programme_id);
        $stmt->bindParam(':user', $user_id);
        $stmt->execute();
        $_SESSION['message'] = "Achat réussi ! Vous pouvez maintenant accéder aux vidéos et contacter votre coach.";
    } else {
        $_SESSION['message'] = "Vous avez déjà acheté ce programme.";
    }
    header("Location: programmes_disponibles.php");
    exit();
}

// Get all programmes (available for purchase)
$query = "SELECT p.*, u.nom as coach_nom, u.prenom as coach_prenom 
          FROM programmes p
          JOIN coachs c ON p.id_coach = c.id_coach
          JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
          ORDER BY p.date_creation DESC";
$programmes = $db->query($query)->fetchAll();

// Get already purchased programme IDs
$stmt = $db->prepare("SELECT id_programme FROM assignations WHERE id_utilisateur = :user");
$stmt->bindParam(':user', $user_id);
$stmt->execute();
$achetes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$page_title = 'Formations disponibles';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Formations disponibles</h1>
    <p class="text-gray-600">Choisissez une formation, achetez-la et accédez aux vidéos + à votre coach.</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($programmes as $prog): ?>
        <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
            <div class="p-6">
                <h3 class="text-xl font-bold"><?php echo htmlspecialchars($prog['titre']); ?></h3>
                <p class="text-gray-500 text-sm mt-1">Coach : <?php echo htmlspecialchars($prog['coach_prenom'] . ' ' . $prog['coach_nom']); ?></p>
                <p class="text-gray-600 mt-2"><?php echo nl2br(htmlspecialchars($prog['description'])); ?></p>
                <div class="mt-4 flex flex-wrap gap-2 text-sm">
                    <span class="px-2 py-1 bg-gray-100 rounded">Niveau: <?php echo ucfirst($prog['niveau_cible']); ?></span>
                    <span class="px-2 py-1 bg-gray-100 rounded">Durée: <?php echo $prog['duree_semaines']; ?> semaines</span>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <span class="text-2xl font-bold text-purple-600"><?php echo number_format($prog['price'], 2); ?> €</span>
                    <?php if (in_array($prog['id_programme'], $achetes)): ?>
                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">Déjà acheté</span>
                    <?php else: ?>
                        <a href="?buy=<?php echo $prog['id_programme']; ?>" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700" onclick="return confirm('Acheter cette formation ?')">Acheter</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include '../include/footer.php'; ?>