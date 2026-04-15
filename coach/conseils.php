<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Ajouter un conseil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_conseil'])) {
    $type = $_POST['type'];
    $contenu = $_POST['contenu'];
    $user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
    $query = "INSERT INTO recommandations (id_utilisateur, type, contenu, date_creation) VALUES (:uid, :type, :contenu, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':uid', $user_id);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':contenu', $contenu);
    $stmt->execute();
    $_SESSION['message'] = "Conseil ajouté.";
    header("Location: conseils.php");
    exit();
}

// Supprimer un conseil
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "DELETE FROM recommandations WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $_SESSION['message'] = "Conseil supprimé.";
    header("Location: conseils.php");
    exit();
}

// Récupérer tous les conseils (avec nom utilisateur si dédié)
$query = "SELECT r.*, u.nom, u.prenom 
          FROM recommandations r
          LEFT JOIN utilisateurs u ON r.id_utilisateur = u.id_utilisateur
          ORDER BY r.date_creation DESC";
$conseils = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Utilisateurs pour filtre
$users = $db->query("SELECT id_utilisateur, nom, prenom FROM utilisateurs WHERE role = 'utilisateur'")->fetchAll();

$page_title = 'Conseils personnalisés';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Conseils & Recommandations IA</h1>
    <p class="text-gray-600 mt-2">Donnez des conseils personnalisés à vos utilisateurs</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulaire -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Nouveau conseil</h2>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Type</label>
                <select name="type" class="w-full px-3 py-2 border rounded">
                    <option value="nutrition">Nutrition</option>
                    <option value="entrainement">Entraînement</option>
                    <option value="motivation">Motivation</option>
                    <option value="recuperation">Récupération</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Destinataire</label>
                <select name="user_id" class="w-full px-3 py-2 border rounded">
                    <option value="">Tous les utilisateurs</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id_utilisateur']; ?>"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Contenu</label>
                <textarea name="contenu" rows="4" required class="w-full px-3 py-2 border rounded"></textarea>
            </div>
            <button type="submit" name="add_conseil" class="w-full bg-purple-600 text-white py-2 rounded hover:bg-purple-700">Publier le conseil</button>
        </form>
    </div>

    <!-- Liste des conseils -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Conseils envoyés</h2>
        <?php if (count($conseils) > 0): ?>
            <div class="space-y-4">
                <?php foreach ($conseils as $c): ?>
                    <div class="border-b pb-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800"><?php echo ucfirst($c['type']); ?></span>
                                <span class="text-sm text-gray-500 ml-2"><?php echo date('d/m/Y H:i', strtotime($c['date_creation'])); ?></span>
                                <p class="mt-1 text-gray-700"><?php echo nl2br(htmlspecialchars($c['contenu'])); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Pour: <?php echo $c['id_utilisateur'] ? htmlspecialchars($c['prenom'] . ' ' . $c['nom']) : 'Tous les utilisateurs'; ?></p>
                            </div>
                            <a href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Supprimer ce conseil ?')" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Aucun conseil pour le moment.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../include/footer.php'; ?>