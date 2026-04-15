<?php
// utilisateur/seances.php
require_once '../config/database.php';
require_once '../include/functions.php';

redirectIfNotRole(['utilisateur', 'coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $date_seance = $_POST['date_seance'];
            $duree = $_POST['duree'];
            $calories = $_POST['calories'];
            $notes = $_POST['notes'] ?? '';
            
            $query = "INSERT INTO seances (id_utilisateur, date_seance, duree, calories, notes) 
                      VALUES (:id, :date, :duree, :calories, :notes)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->bindParam(':date', $date_seance);
            $stmt->bindParam(':duree', $duree);
            $stmt->bindParam(':calories', $calories);
            $stmt->bindParam(':notes', $notes);
            $stmt->execute();
            $_SESSION['message'] = "Séance ajoutée avec succès";
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id_seance'])) {
            $query = "DELETE FROM seances WHERE id_seance = :id AND id_utilisateur = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['id_seance']);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $_SESSION['message'] = "Séance supprimée";
        }
        header("Location: seances.php");
        exit();
    }
}

// Récupérer les séances
$query = "SELECT * FROM seances WHERE id_utilisateur = :id ORDER BY date_seance DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$seances = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Mes séances';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Mes séances</h1>
    <p class="text-gray-600 mt-2">Gérez votre historique d'entraînement</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<!-- Bouton ajouter -->
<button onclick="document.getElementById('addModal').classList.remove('hidden')" 
        class="mb-6 bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-3 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition transform hover:scale-105">
    <i class="fas fa-plus mr-2"></i>Ajouter une séance
</button>

<!-- Tableau des séances -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durée (min)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calories</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($seances) > 0): ?>
                <?php foreach ($seances as $seance): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($seance['date_seance'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $seance['duree']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $seance['calories']; ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($seance['notes']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette séance ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_seance" value="<?php echo $seance['id_seance']; ?>">
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
                        <i class="fas fa-calendar-times text-4xl mb-2 block"></i>
                        Aucune séance enregistrée
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal ajout séance -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Ajouter une séance</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Date *</label>
                <input type="date" name="date_seance" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Durée (minutes) *</label>
                <input type="number" name="duree" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Calories *</label>
                <input type="number" name="calories" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Notes</label>
                <textarea name="notes" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">Enregistrer</button>
        </form>
    </div>
</div>

<?php include '../include/footer.php'; ?>