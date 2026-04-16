<?php
// utilisateur/seances.php
require_once '../config/database.php';
require_once '../include/functions.php';

redirectIfNotRole(['utilisateur', 'coach', 'admin']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Handle POST actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $date_seance = $_POST['date_seance'];
        $duree = intval($_POST['duree']);
        $calories = intval($_POST['calories']);
        $notes = trim($_POST['notes'] ?? '');
        $id_programme = !empty($_POST['id_programme']) ? intval($_POST['id_programme']) : null;
        
        $query = "INSERT INTO seances (id_utilisateur, date_seance, duree, calories, notes, id_programme) 
                  VALUES (:id, :date, :duree, :calories, :notes, :programme)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->bindParam(':date', $date_seance);
        $stmt->bindParam(':duree', $duree);
        $stmt->bindParam(':calories', $calories);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':programme', $id_programme);
        $stmt->execute();
        $_SESSION['message'] = "Séance ajoutée avec succès";
        
    } elseif ($action === 'edit') {
        $id_seance = intval($_POST['id_seance']);
        $date_seance = $_POST['date_seance'];
        $duree = intval($_POST['duree']);
        $calories = intval($_POST['calories']);
        $notes = trim($_POST['notes'] ?? '');
        $id_programme = !empty($_POST['id_programme']) ? intval($_POST['id_programme']) : null;
        
        $query = "UPDATE seances SET date_seance=:date, duree=:duree, calories=:calories, notes=:notes, id_programme=:programme 
                  WHERE id_seance=:id AND id_utilisateur=:user";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':date', $date_seance);
        $stmt->bindParam(':duree', $duree);
        $stmt->bindParam(':calories', $calories);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':programme', $id_programme);
        $stmt->bindParam(':id', $id_seance);
        $stmt->bindParam(':user', $user_id);
        $stmt->execute();
        $_SESSION['message'] = "Séance modifiée avec succès";
        
    } elseif ($action === 'delete' && isset($_POST['id_seance'])) {
        $id_seance = intval($_POST['id_seance']);
        $query = "DELETE FROM seances WHERE id_seance = :id AND id_utilisateur = :user";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id_seance);
        $stmt->bindParam(':user', $user_id);
        $stmt->execute();
        $_SESSION['message'] = "Séance supprimée";
    }
    
    header("Location: seances.php");
    exit();
}

// Get filter parameters
$filter_programme = isset($_GET['programme']) ? intval($_GET['programme']) : 0;
$filter_month = isset($_GET['month']) ? $_GET['month'] : '';

// Build query for sessions
$sql = "SELECT s.*, p.titre as programme_titre 
        FROM seances s 
        LEFT JOIN programmes p ON s.id_programme = p.id_programme 
        WHERE s.id_utilisateur = :user";
$params = [':user' => $user_id];

if ($filter_programme > 0) {
    $sql .= " AND s.id_programme = :programme";
    $params[':programme'] = $filter_programme;
}
if (!empty($filter_month)) {
    $sql .= " AND DATE_FORMAT(s.date_seance, '%Y-%m') = :month";
    $params[':month'] = $filter_month;
}
$sql .= " ORDER BY s.date_seance DESC";

$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$seances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "SELECT COUNT(*) as total, SUM(duree) as total_duree, SUM(calories) as total_calories 
              FROM seances WHERE id_utilisateur = :user";
$stmt_stats = $db->prepare($stats_sql);
$stmt_stats->bindParam(':user', $user_id);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Get user's purchased programmes (for linking sessions)
$prog_sql = "SELECT DISTINCT p.id_programme, p.titre 
             FROM programmes p
             JOIN assignations a ON p.id_programme = a.id_programme
             WHERE a.id_utilisateur = :user
             ORDER BY p.titre";
$stmt_prog = $db->prepare($prog_sql);
$stmt_prog->bindParam(':user', $user_id);
$stmt_prog->execute();
$programmes = $stmt_prog->fetchAll(PDO::FETCH_ASSOC);

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

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-6 text-center card-hover">
        <i class="fas fa-calendar-check text-3xl text-purple-600 mb-2"></i>
        <p class="text-gray-500 text-sm">Total séances</p>
        <p class="text-3xl font-bold"><?php echo $stats['total'] ?? 0; ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6 text-center card-hover">
        <i class="fas fa-clock text-3xl text-purple-600 mb-2"></i>
        <p class="text-gray-500 text-sm">Durée totale</p>
        <p class="text-3xl font-bold"><?php echo $stats['total_duree'] ?? 0; ?> min</p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6 text-center card-hover">
        <i class="fas fa-fire text-3xl text-purple-600 mb-2"></i>
        <p class="text-gray-500 text-sm">Calories totales</p>
        <p class="text-3xl font-bold"><?php echo $stats['total_calories'] ?? 0; ?></p>
    </div>
</div>

<!-- Filters and Add Button -->
<div class="flex flex-wrap justify-between items-center mb-6 gap-4">
    <div class="flex flex-wrap gap-3">
        <select id="filter-programme" class="px-4 py-2 border rounded-lg focus:outline-none focus:border-purple-500">
            <option value="0">Tous les programmes</option>
            <?php foreach ($programmes as $prog): ?>
                <option value="<?php echo $prog['id_programme']; ?>" <?php echo ($filter_programme == $prog['id_programme']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($prog['titre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="month" id="filter-month" value="<?php echo $filter_month; ?>" class="px-4 py-2 border rounded-lg focus:outline-none focus:border-purple-500">
        <button id="filter-reset" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Réinitialiser</button>
    </div>
    <button onclick="openAddModal()" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-2 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition">
        <i class="fas fa-plus mr-2"></i>Ajouter une séance
    </button>
</div>

<!-- Sessions Table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Programme</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durée</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Calories</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($seances) > 0): ?>
                    <?php foreach ($seances as $seance): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($seance['date_seance'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($seance['programme_titre'] ?? '-'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $seance['duree']; ?> min</td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $seance['calories']; ?></td>
                            <td class="px-6 py-4 max-w-xs truncate"><?php echo htmlspecialchars($seance['notes']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button onclick='openEditModal(<?php echo json_encode($seance); ?>)' class="text-blue-600 hover:text-blue-800 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
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
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-calendar-times text-4xl mb-2 block"></i>
                            Aucune séance enregistrée
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajouter / Modifier -->
<div id="sessionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modal-title" class="text-lg font-bold">Ajouter une séance</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="sessionForm" method="POST">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="id_seance" id="edit-id">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Date *</label>
                <input type="date" name="date_seance" id="date_seance" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Programme (optionnel)</label>
                <select name="id_programme" id="id_programme" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($programmes as $prog): ?>
                        <option value="<?php echo $prog['id_programme']; ?>"><?php echo htmlspecialchars($prog['titre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Durée (minutes) *</label>
                <input type="number" name="duree" id="duree" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Calories *</label>
                <input type="number" name="calories" id="calories" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">Enregistrer</button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modal-title').innerText = 'Ajouter une séance';
    document.getElementById('form-action').value = 'add';
    document.getElementById('edit-id').value = '';
    document.getElementById('date_seance').value = '';
    document.getElementById('id_programme').value = '';
    document.getElementById('duree').value = '';
    document.getElementById('calories').value = '';
    document.getElementById('notes').value = '';
    document.getElementById('sessionModal').classList.remove('hidden');
}

function openEditModal(seance) {
    document.getElementById('modal-title').innerText = 'Modifier la séance';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('edit-id').value = seance.id_seance;
    document.getElementById('date_seance').value = seance.date_seance;
    document.getElementById('id_programme').value = seance.id_programme || '';
    document.getElementById('duree').value = seance.duree;
    document.getElementById('calories').value = seance.calories;
    document.getElementById('notes').value = seance.notes || '';
    document.getElementById('sessionModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('sessionModal').classList.add('hidden');
}

// Filter functionality
document.getElementById('filter-programme').addEventListener('change', function() {
    applyFilters();
});
document.getElementById('filter-month').addEventListener('change', function() {
    applyFilters();
});
document.getElementById('filter-reset').addEventListener('click', function() {
    window.location.href = 'seances.php';
});
function applyFilters() {
    const programme = document.getElementById('filter-programme').value;
    const month = document.getElementById('filter-month').value;
    let url = 'seances.php?';
    if (programme != 0) url += 'programme=' + programme + '&';
    if (month) url += 'month=' + month;
    window.location.href = url;
}
</script>

<?php include '../include/footer.php'; ?>