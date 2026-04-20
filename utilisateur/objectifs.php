<?php
// utilisateur/objectifs.php
require_once '../config/database.php';
require_once '../include/functions.php';

redirectIfNotRole(['utilisateur', 'coach', 'admin']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Handle POST actions (add, edit, delete, update status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $type_objectif = trim($_POST['type_objectif']);
        $valeur_cible = floatval($_POST['valeur_cible']);
        $date_echeance = $_POST['date_echeance'];
        
        $query = "INSERT INTO objectifs (id_utilisateur, type_objectif, valeur_cible, date_echeance, statut) 
                  VALUES (:id, :type, :valeur, :date, 'en_cours')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->bindParam(':type', $type_objectif);
        $stmt->bindParam(':valeur', $valeur_cible);
        $stmt->bindParam(':date', $date_echeance);
        $stmt->execute();
        $_SESSION['message'] = "Objectif ajouté avec succès";
        
    } elseif ($action === 'edit') {
        $id_objectif = intval($_POST['id_objectif']);
        $type_objectif = trim($_POST['type_objectif']);
        $valeur_cible = floatval($_POST['valeur_cible']);
        $date_echeance = $_POST['date_echeance'];
        
        $query = "UPDATE objectifs SET type_objectif=:type, valeur_cible=:valeur, date_echeance=:date 
                  WHERE id_objectif=:id AND id_utilisateur=:user";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':type', $type_objectif);
        $stmt->bindParam(':valeur', $valeur_cible);
        $stmt->bindParam(':date', $date_echeance);
        $stmt->bindParam(':id', $id_objectif);
        $stmt->bindParam(':user', $user_id);
        $stmt->execute();
        $_SESSION['message'] = "Objectif modifié";
        
    } elseif ($action === 'delete' && isset($_POST['id_objectif'])) {
        $id_objectif = intval($_POST['id_objectif']);
        $query = "DELETE FROM objectifs WHERE id_objectif = :id AND id_utilisateur = :user";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id_objectif);
        $stmt->bindParam(':user', $user_id);
        $stmt->execute();
        $_SESSION['message'] = "Objectif supprimé";
        
    } elseif ($action === 'update_status' && isset($_POST['id_objectif'])) {
        $id_objectif = intval($_POST['id_objectif']);
        $statut = $_POST['statut'];
        $query = "UPDATE objectifs SET statut = :statut WHERE id_objectif = :id AND id_utilisateur = :user";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':statut', $statut);
        $stmt->bindParam(':id', $id_objectif);
        $stmt->bindParam(':user', $user_id);
        $stmt->execute();
        $_SESSION['message'] = "Statut mis à jour";
    }
    
    header("Location: objectifs.php");
    exit();
}

// Get filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query with optional status filter
$sql = "SELECT * FROM objectifs WHERE id_utilisateur = :user";
if ($filter_status !== 'all') {
    $sql .= " AND statut = :status";
}
$sql .= " ORDER BY 
            CASE statut 
                WHEN 'en_cours' THEN 1 
                WHEN 'atteint' THEN 2 
                WHEN 'echoue' THEN 3 
            END, 
            date_echeance ASC";

$stmt = $db->prepare($sql);
$stmt->bindParam(':user', $user_id);
if ($filter_status !== 'all') {
    $stmt->bindParam(':status', $filter_status);
}
$stmt->execute();
$objectifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$stats = [
    'total' => 0,
    'en_cours' => 0,
    'atteint' => 0,
    'echoue' => 0
];
foreach ($objectifs as $obj) {
    $stats['total']++;
    $stats[$obj['statut']]++;
}

// Helper function to get days remaining
function getDaysRemaining($date_echeance) {
    $today = new DateTime();
    $echeance = new DateTime($date_echeance);
    $interval = $today->diff($echeance);
    $days = $interval->days;
    if ($today > $echeance) {
        return -$days;
    }
    return $days;
}

function getStatusBadge($statut) {
    switch ($statut) {
        case 'en_cours':
            return '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">En cours</span>';
        case 'atteint':
            return '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Atteint</span>';
        case 'echoue':
            return '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Échoué</span>';
        default:
            return '<span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">Inconnu</span>';
    }
}

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

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-4 text-center card-hover">
        <p class="text-gray-500 text-sm">Total objectifs</p>
        <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-4 text-center card-hover">
        <p class="text-gray-500 text-sm">En cours</p>
        <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['en_cours']; ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-4 text-center card-hover">
        <p class="text-gray-500 text-sm">Atteints</p>
        <p class="text-3xl font-bold text-green-600"><?php echo $stats['atteint']; ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-4 text-center card-hover">
        <p class="text-gray-500 text-sm">Échoués</p>
        <p class="text-3xl font-bold text-red-600"><?php echo $stats['echoue']; ?></p>
    </div>
</div>

<!-- Filters and Add Button -->
<div class="flex flex-wrap justify-between items-center mb-6 gap-4">
    <div class="flex gap-2">
        <a href="?status=all" class="px-4 py-2 rounded-lg <?php echo $filter_status == 'all' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">Tous</a>
        <a href="?status=en_cours" class="px-4 py-2 rounded-lg <?php echo $filter_status == 'en_cours' ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">En cours</a>
        <a href="?status=atteint" class="px-4 py-2 rounded-lg <?php echo $filter_status == 'atteint' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">Atteints</a>
        <a href="?status=echoue" class="px-4 py-2 rounded-lg <?php echo $filter_status == 'echoue' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">Échoués</a>
    </div>
    <button onclick="openAddModal()" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-2 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition">
        <i class="fas fa-plus mr-2"></i>Ajouter un objectif
    </button>
</div>

<!-- Goals Table (responsive) -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Objectif</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cible</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Échéance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jours restants</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($objectifs) > 0): ?>
                    <?php foreach ($objectifs as $obj): ?>
                        <?php
                        $daysLeft = getDaysRemaining($obj['date_echeance']);
                        $daysClass = '';
                        if ($obj['statut'] == 'en_cours') {
                            if ($daysLeft < 0) $daysClass = 'text-red-600 font-semibold';
                            elseif ($daysLeft <= 7) $daysClass = 'text-orange-600';
                            else $daysClass = 'text-green-600';
                        }
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($obj['type_objectif']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($obj['valeur_cible']); ?></td>
                            <td class="px-6 py-4"><?php echo date('d/m/Y', strtotime($obj['date_echeance'])); ?></td>
                            <td class="px-6 py-4 <?php echo $daysClass; ?>">
                                <?php if ($obj['statut'] == 'en_cours'): ?>
                                    <?php if ($daysLeft < 0): ?>
                                        Expiré
                                    <?php else: ?>
                                        <?php echo $daysLeft; ?> jour(s)
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id_objectif" value="<?php echo $obj['id_objectif']; ?>">
                                    <select name="statut" onchange="this.form.submit()" class="text-sm border rounded px-2 py-1">
                                        <option value="en_cours" <?php echo $obj['statut'] == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                        <option value="atteint" <?php echo $obj['statut'] == 'atteint' ? 'selected' : ''; ?>>Atteint</option>
                                        <option value="echoue" <?php echo $obj['statut'] == 'echoue' ? 'selected' : ''; ?>>Échoué</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button onclick='openEditModal(<?php echo json_encode($obj); ?>)' class="text-blue-600 hover:text-blue-800 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cet objectif ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_objectif" value="<?php echo $obj['id_objectif']; ?>">
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
                            <i class="fas fa-bullseye text-4xl mb-2 block"></i>
                            Aucun objectif défini
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajout -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Ajouter un objectif</h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Type d'objectif *</label>
                <select name="type_objectif" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">-- Sélectionner --</option>
                    <option value="Perte de poids (kg)">Perte de poids (kg)</option>
                    <option value="Gain musculaire (kg)">Gain musculaire (kg)</option>
                    <option value="Course à pied (km)">Course à pied (km)</option>
                    <option value="Nombre de séances">Nombre de séances</option>
                    <option value="Calories brûlées">Calories brûlées</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Valeur cible *</label>
                <input type="number" step="0.1" name="valeur_cible" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Date d'échéance *</label>
                <input type="date" name="date_echeance" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">Enregistrer</button>
        </form>
    </div>
</div>

<!-- Modal Édition -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Modifier l'objectif</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="editForm" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_objectif" id="edit_id">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Type d'objectif *</label>
                <input type="text" name="type_objectif" id="edit_type" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Valeur cible *</label>
                <input type="number" step="0.1" name="valeur_cible" id="edit_valeur" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Date d'échéance *</label>
                <input type="date" name="date_echeance" id="edit_date" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">Enregistrer</button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}
function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}
function openEditModal(obj) {
    document.getElementById('edit_id').value = obj.id_objectif;
    document.getElementById('edit_type').value = obj.type_objectif;
    document.getElementById('edit_valeur').value = obj.valeur_cible;
    document.getElementById('edit_date').value = obj.date_echeance;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php include '../include/footer.php'; ?>