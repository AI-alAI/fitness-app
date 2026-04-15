<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Récupérer l'id_coach du coach connecté
$stmt = $db->prepare("SELECT id_coach FROM coachs WHERE id_utilisateur = :user");
$stmt->bindParam(':user', $_SESSION['user_id']);
$stmt->execute();
$coach = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$coach) {
    die("Erreur : Vous n'êtes pas enregistré comme coach.");
}
$coach_id = $coach['id_coach'];

// Récupérer le programme sélectionné (depuis l'URL)
$programme_id = isset($_GET['programme_id']) ? intval($_GET['programme_id']) : 0;
$message = '';

// Vérifier que le programme appartient bien au coach
if ($programme_id) {
    $check = $db->prepare("SELECT id_programme, titre FROM programmes WHERE id_programme = :id AND id_coach = :coach");
    $check->bindParam(':id', $programme_id);
    $check->bindParam(':coach', $coach_id);
    $check->execute();
    if ($check->rowCount() == 0) {
        $programme_id = 0;
        $message = "Programme non trouvé ou accès refusé.";
    } else {
        $progInfo = $check->fetch(PDO::FETCH_ASSOC);
        $programme_titre = $progInfo['titre'];
    }
}

// Assigner un utilisateur à un programme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign']) && $programme_id) {
    $id_utilisateur = $_POST['id_utilisateur'];
    $date_assignation = date('Y-m-d');
    // Vérifier si déjà assigné
    $check = $db->prepare("SELECT id_assignation FROM assignations WHERE id_programme = :prog AND id_utilisateur = :user");
    $check->bindParam(':prog', $programme_id);
    $check->bindParam(':user', $id_utilisateur);
    $check->execute();
    if ($check->rowCount() > 0) {
        $message = "Cet utilisateur est déjà assigné à ce programme.";
    } else {
        $query = "INSERT INTO assignations (id_programme, id_utilisateur, date_assignation, statut) VALUES (:prog, :user, :date, 'en_cours')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':prog', $programme_id);
        $stmt->bindParam(':user', $id_utilisateur);
        $stmt->bindParam(':date', $date_assignation);
        if ($stmt->execute()) {
            $message = "Utilisateur assigné avec succès.";
        } else {
            $message = "Erreur lors de l'assignation.";
        }
    }
}

// Supprimer une assignation
if (isset($_GET['delete_assign']) && $programme_id) {
    $id_assign = intval($_GET['delete_assign']);
    $query = "DELETE FROM assignations WHERE id_assignation = :id AND id_programme = :prog";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id_assign);
    $stmt->bindParam(':prog', $programme_id);
    $stmt->execute();
    $message = "Assignation supprimée.";
}

// Récupérer les utilisateurs déjà assignés à ce programme
$assigned_users = [];
if ($programme_id) {
    $query = "SELECT a.id_assignation, u.id_utilisateur, u.nom, u.prenom, u.email, a.date_assignation, a.statut
              FROM assignations a
              JOIN utilisateurs u ON a.id_utilisateur = u.id_utilisateur
              WHERE a.id_programme = :prog";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':prog', $programme_id);
    $stmt->execute();
    $assigned_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Tous les utilisateurs (non coach/admin) pour l'assignation
$all_users = $db->query("SELECT id_utilisateur, nom, prenom, email FROM utilisateurs WHERE role = 'utilisateur' ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les programmes du coach pour le sélecteur
$programmes = $db->prepare("SELECT id_programme, titre FROM programmes WHERE id_coach = :coach ORDER BY titre");
$programmes->bindParam(':coach', $coach_id);
$programmes->execute();
$programmes_list = $programmes->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Assigner un programme';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Assignation de programme</h1>
    <p class="text-gray-600 mt-2">Attribuez un programme à un utilisateur.</p>
</div>

<?php if ($message): ?>
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Sélection du programme -->
<div class="bg-white rounded-xl shadow-md p-6 mb-6">
    <h2 class="text-xl font-bold mb-4">1. Choisir un programme</h2>
    <form method="GET" action="">
        <select name="programme_id" class="w-full md:w-1/2 px-4 py-2 border rounded-lg" onchange="this.form.submit()">
            <option value="0">-- Sélectionnez un programme --</option>
            <?php foreach ($programmes_list as $p): ?>
                <option value="<?php echo $p['id_programme']; ?>" <?php echo ($programme_id == $p['id_programme']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($p['titre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($programme_id): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Formulaire d'assignation -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Assigner à un utilisateur</h2>
            <p class="text-sm text-gray-500 mb-4">Programme : <strong><?php echo htmlspecialchars($programme_titre); ?></strong></p>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Utilisateur</label>
                    <select name="id_utilisateur" required class="w-full px-3 py-2 border rounded">
                        <option value="">Choisir un utilisateur</option>
                        <?php foreach ($all_users as $user): ?>
                            <option value="<?php echo $user['id_utilisateur']; ?>">
                                <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="assign" class="w-full bg-purple-600 text-white py-2 rounded hover:bg-purple-700">Assigner</button>
            </form>
        </div>

        <!-- Liste des utilisateurs déjà assignés -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Utilisateurs assignés</h2>
            <?php if (count($assigned_users) > 0): ?>
                <div class="space-y-3">
                    <?php foreach ($assigned_users as $au): ?>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($au['prenom'] . ' ' . $au['nom']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($au['email']); ?></p>
                                <p class="text-xs">Assigné le <?php echo date('d/m/Y', strtotime($au['date_assignation'])); ?> | Statut: <?php echo $au['statut']; ?></p>
                            </div>
                            <a href="?programme_id=<?php echo $programme_id; ?>&delete_assign=<?php echo $au['id_assignation']; ?>" onclick="return confirm('Retirer cet utilisateur du programme ?')" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">Aucun utilisateur assigné à ce programme.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../include/footer.php'; ?>