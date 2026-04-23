<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['utilisateur']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Simulate purchase (still simple)
if (isset($_GET['buy']) && is_numeric($_GET['buy'])) {
    $programme_id = intval($_GET['buy']);
    $check = $db->prepare("SELECT id_assignation FROM assignations WHERE id_utilisateur = :user AND id_programme = :prog");
    $check->bindParam(':user', $user_id);
    $check->bindParam(':prog', $programme_id);
    $check->execute();
    if ($check->rowCount() == 0) {
        $stmt = $db->prepare("INSERT INTO assignations (id_programme, id_utilisateur, date_assignation, statut) VALUES (:prog, :user, NOW(), 'en_cours')");
        $stmt->bindParam(':prog', $programme_id);
        $stmt->bindParam(':user', $user_id);
        $stmt->execute();
        $_SESSION['message'] = "Achat réussi ! Accédez aux vidéos et contactez votre coach.";
    } else {
        $_SESSION['message'] = "Vous avez déjà acheté ce programme.";
    }
    header("Location: programmes_disponibles.php");
    exit();
}

// Fetch all programmes
$query = "SELECT p.*, u.nom as coach_nom, u.prenom as coach_prenom 
          FROM programmes p
          JOIN coachs c ON p.id_coach = c.id_coach
          JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
          ORDER BY p.date_creation DESC";
$programmes = $db->query($query)->fetchAll();

// Already purchased
$stmt = $db->prepare("SELECT id_programme FROM assignations WHERE id_utilisateur = :user");
$stmt->bindParam(':user', $user_id);
$stmt->execute();
$achetes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$page_title = 'Formations disponibles';
include '../include/header.php';
?>

<div class="mb-10 text-center">
    <h1 class="text-4xl font-extrabold text-gray-800">Nos formations</h1>
    <p class="text-gray-600 mt-2">Choisissez un programme, achetez-le et accédez aux vidéos + coaching</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach ($programmes as $prog): 
        $isPurchased = in_array($prog['id_programme'], $achetes);
        $priceClass = $isPurchased ? 'text-gray-500' : 'text-purple-600';
    ?>
        <div class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden flex flex-col">
            <!-- Card header gradient (3D effect) -->
            <div class="h-32 bg-gradient-to-r from-purple-500 to-indigo-600 relative">
                <div class="absolute inset-0 bg-black opacity-0 group-hover:opacity-10 transition duration-300"></div>
                <div class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-white to-transparent"></div>
                <div class="absolute bottom-2 left-4">
                    <span class="text-white text-sm font-semibold bg-black/30 px-3 py-1 rounded-full">
                        <?php echo ucfirst($prog['niveau_cible']); ?>
                    </span>
                </div>
            </div>

            <!-- Card body -->
            <div class="p-5 flex-1 flex flex-col">
                <h3 class="text-xl font-bold text-gray-800 line-clamp-1"><?php echo htmlspecialchars($prog['titre']); ?></h3>
                <p class="text-gray-500 text-sm mt-1 flex items-center gap-1">
                    <i class="fas fa-chalkboard-user text-gray-400"></i> 
                    <?php echo htmlspecialchars($prog['coach_prenom'] . ' ' . $prog['coach_nom']); ?>
                </p>
                <p class="text-gray-600 mt-3 text-sm line-clamp-3"><?php echo nl2br(htmlspecialchars($prog['description'])); ?></p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-gray-100 rounded-full text-xs font-medium text-gray-700">
                        <i class="far fa-clock mr-1"></i> <?php echo $prog['duree_semaines']; ?> semaines
                    </span>
                    <span class="px-2 py-1 bg-gray-100 rounded-full text-xs font-medium text-gray-700">
                        <i class="fas fa-tag mr-1"></i> <?php echo ucfirst($prog['niveau_cible']); ?>
                    </span>
                </div>

                <div class="mt-6 flex items-center justify-between border-t pt-4 border-gray-100">
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-400">Prix</span>
                        <span class="text-2xl font-bold <?php echo $priceClass; ?>">
                            <?php echo number_format($prog['price'], 2); ?> €
                        </span>
                    </div>
                    <?php if ($isPurchased): ?>
                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-3 py-2 rounded-full text-sm font-semibold">
                            <i class="fas fa-check-circle"></i> Acheté
                        </span>
                    <?php else: ?>
                        <a href="?buy=<?php echo $prog['id_programme']; ?>" 
                           class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold px-5 py-2 rounded-full shadow-md transition-all hover:shadow-lg hover:scale-105 active:scale-95"
                           onclick="return confirm('Acheter cette formation ?')">
                            <i class="fas fa-shopping-cart"></i> Acheter
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (count($programmes) == 0): ?>
    <div class="bg-gray-50 rounded-2xl p-12 text-center text-gray-500 border-2 border-dashed border-gray-300 mt-8">
        <i class="fas fa-box-open text-5xl mb-3 text-gray-400"></i>
        <p class="text-lg">Aucune formation disponible pour le moment.</p>
        <p class="text-sm">Revenez plus tard, de nouveaux programmes arrivent bientôt.</p>
    </div>
<?php endif; ?>

<style>
    /* Ensures consistent card height and line clamping */
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .group:hover .bg-gradient-to-r {
        filter: brightness(1.05);
    }
</style>

<?php include '../include/footer.php'; ?>