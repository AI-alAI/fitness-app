<?php
// utilisateur/workout_history.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['utilisateur']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Suppression d’un workout
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->prepare("DELETE FROM workout_logs WHERE id = ? AND id_utilisateur = ?")->execute([$id, $user_id]);
    header("Location: workout_history.php?msg=deleted");
    exit();
}

// Édition d’un workout (sets, reps, weight)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_workout'])) {
    $id = intval($_POST['id']);
    $sets = intval($_POST['sets']);
    $reps = intval($_POST['reps']);
    $weight = floatval($_POST['weight']);
    $db->prepare("UPDATE workout_logs SET sets = ?, reps = ?, weight_kg = ? WHERE id = ? AND id_utilisateur = ?")
       ->execute([$sets, $reps, $weight, $id, $user_id]);
    header("Location: workout_history.php?msg=updated");
    exit();
}

// Récupération des workouts avec le nom de l’exercice (jointure)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'asc' : 'desc';

$sql = "SELECT w.*, e.nom_exercice 
        FROM workout_logs w
        JOIN exercices e ON w.id_exercice = e.id_exercice
        WHERE w.id_utilisateur = :user";
$params = [':user' => $user_id];

if ($search) {
    $sql .= " AND e.nom_exercice LIKE :search";
    $params[':search'] = "%$search%";
}
if ($filter_date) {
    $sql .= " AND w.date_log = :date";
    $params[':date'] = $filter_date;
}
$sql .= " ORDER BY w.date_log " . ($order === 'asc' ? 'ASC' : 'DESC');

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Historique des workouts';
include '../include/header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">📜 Historique des entraînements</h1>
    <p class="text-gray-500 mb-6">Retrouvez tous vos exercices enregistrés.</p>

    <?php if (isset($_GET['msg'])): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            <?= $_GET['msg'] === 'deleted' ? 'Workout supprimé.' : 'Workout mis à jour.' ?>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="bg-white p-4 rounded-xl shadow mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium">Rechercher exercice</label>
            <input type="text" id="searchInput" value="<?= htmlspecialchars($search) ?>" class="border rounded px-3 py-2 w-48">
        </div>
        <div>
            <label class="block text-sm font-medium">Filtrer par date</label>
            <input type="date" id="dateFilter" value="<?= $filter_date ?>" class="border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium">Ordre</label>
            <select id="orderSelect" class="border rounded px-3 py-2">
                <option value="desc" <?= $order === 'desc' ? 'selected' : '' ?>>Plus récent d'abord</option>
                <option value="asc" <?= $order === 'asc' ? 'selected' : '' ?>>Plus ancien d'abord</option>
            </select>
        </div>
        <button id="applyFilters" class="bg-purple-600 text-white px-4 py-2 rounded">Appliquer</button>
        <a href="workout_history.php" class="text-sm text-purple-600 underline self-center">Réinitialiser</a>
    </div>

    <!-- Tableau -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exercice</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sets</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reps</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Poids (kg)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($workouts as $w): ?>
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4"><?= date('d/m/Y', strtotime($w['date_log'])) ?></td>
                    <td class="px-6 py-4 font-medium"><?= htmlspecialchars($w['nom_exercice']) ?></td>
                    <td class="px-6 py-4">
                        <form method="POST" class="inline-flex gap-1">
                            <input type="hidden" name="id" value="<?= $w['id'] ?>">
                            <input type="number" name="sets" value="<?= $w['sets'] ?>" class="w-16 border rounded px-1 py-1" required>
                            <input type="number" name="reps" value="<?= $w['reps'] ?>" class="w-16 border rounded px-1 py-1" required>
                            <input type="number" step="0.5" name="weight" value="<?= $w['weight_kg'] ?>" class="w-20 border rounded px-1 py-1" required>
                            <button type="submit" name="edit_workout" class="bg-blue-500 text-white px-2 py-1 rounded text-sm">💾</button>
                        </form>
                    </td>
                    <td class="px-6 py-4">
                        <a href="?delete=<?= $w['id'] ?>" onclick="return confirm('Supprimer ce workout ?')" class="text-red-600 hover:underline">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($workouts)): ?>
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Aucun workout enregistré.<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.getElementById('applyFilters')?.addEventListener('click', () => {
        const search = document.getElementById('searchInput').value;
        const date = document.getElementById('dateFilter').value;
        const order = document.getElementById('orderSelect').value;
        window.location.href = `?search=${encodeURIComponent(search)}&date=${date}&order=${order}`;
    });
</script>

<?php include '../include/footer.php'; ?>