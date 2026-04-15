<?php
// utilisateur/objectifs.php
require_once '../config/database.php';
require_once '../include/functions.php';

redirectIfNotRole(['utilisateur', 'coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $type_objectif = $_POST['type_objectif'];
            $valeur_cible = $_POST['valeur_cible'];
            $date_echeance = $_POST['date_echeance'];
            
            $query = "INSERT INTO objectifs (id_utilisateur, type_objectif, valeur_cible, date_echeance, statut) 
                      VALUES (:id, :type, :valeur, :date, 'en_cours')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->bindParam(':type', $type_objectif);
            $stmt->bindParam(':valeur', $valeur_cible);
            $stmt->bindParam(':date', $date_echeance);
            $stmt->execute();
            $_SESSION['message'] = "Objectif ajouté avec succès";
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id_objectif'])) {
            $query = "DELETE FROM objectifs WHERE id_objectif = :id AND id_utilisateur = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['id_objectif']);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $_SESSION['message'] = "Objectif supprimé";
        } elseif ($_POST['action'] === 'update_status' && isset($_POST['id_objectif'])) {
            $statut = $_POST['statut'];
            $query = "UPDATE objectifs SET statut = :statut WHERE id_objectif = :id AND id_utilisateur = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':statut', $statut);
            $stmt->bindParam(':id', $_POST['id_objectif']);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $_SESSION['message'] = "Statut mis à jour";
        }
        header("Location: objectifs.php");
        exit();
    }
}

// Récupérer les objectifs
$query = "SELECT * FROM objectifs WHERE id_utilisateur = :id ORDER BY date_echeance ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$objectifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Mes objectifs';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Mes objectifs</h1>
    <p class="text-gray-600 mt-2">Définissez et suivez vos objectifs fitness</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<!-- Bouton ajouter -->
<button onclick="document.getElementById('addModal').classList.remove('hidden')" 
        class="mb-6 bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-3 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition transform hover:scale-105">
    <i class="fas fa-plus mr-2"></i>Ajouter un objectif
</button>

<!-- Tableau des objectifs -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objectif</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valeur cible</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Échéance</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($objectifs) > 0): ?>
                <?php foreach ($objectifs as $objectif): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4"><?php echo htmlspecialchars($objectif['type_objectif']); ?></td>
                        <td class="px-6 py-4"><?php echo $objectif['valeur_cible']; ?></td>
                        <td class="px-6 py-4"><?php echo date('d/m/Y', strtotime($objectif['date_echeance'])); ?></td>
                        <td class="px-6 py-4">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id_objectif" value="<?php echo $objectif['id_objectif']; ?>">
                                <select name="statut" onchange="this.form.submit()" class="text-sm rounded">
                                    <option value="en_cours" <?php echo $objectif['statut'] == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="atteint" <?php echo $objectif['statut'] == 'atteint' ? 'selected' : ''; ?>>Atteint</option>
                                    <option value="echoue" <?php echo $objectif['statut'] == 'echoue' ? 'selected' : ''; ?>>Échoué</option>
                                </select>
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cet objectif ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_objectif" value="<?php echo $objectif['id_objectif']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-bullseye text-4xl mb-2 block"></i>
                        Aucun objectif défini
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal ajout objectif -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Ajouter un objectif</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Type d'objectif *</label>
                <input type="text" name="type_objectif" required placeholder="ex: Perte de poids, Course à pied..." class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Valeur cible *</label>
                <input type="number" step="0.1" name="valeur_cible" required placeholder="ex: 5, 10, 20..." class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Date d'échéance *</label>
                <input type="date" name="date_echeance" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">Enregistrer</button>
        </form>
    </div>
</div>

<?php include '../include/footer.php'; ?>