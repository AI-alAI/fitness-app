<?php
// utilisateur/workout_log.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['utilisateur']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Récupérer la liste des exercices depuis la table `exercices`
$exercises = $db->query("SELECT id_exercice, nom_exercice FROM exercices ORDER BY nom_exercice")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_workout'])) {
    $id_exercice = intval($_POST['id_exercice']);
    $sets = intval($_POST['sets']);
    $reps = intval($_POST['reps']);
    $weight = floatval($_POST['weight']);

    // Validation simple
    if ($id_exercice > 0 && $sets > 0 && $reps > 0 && $weight >= 0) {
        $stmt = $db->prepare("INSERT INTO workout_logs (id_utilisateur, id_exercice, sets, reps, weight_kg) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $id_exercice, $sets, $reps, $weight]);
        $success = "Workout ajouté avec succès !";
    } else {
        $error = "Veuillez remplir correctement tous les champs (sets, reps, poids ≥ 0).";
    }
}

$page_title = 'Ajouter un workout';
include '../include/header.php';
?>

<div class="max-w-2xl mx-auto mt-8 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">💪 Ajouter une séance</h1>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-6 rounded-xl shadow-md space-y-4">
        <div>
            <label class="block font-medium text-gray-700">Exercice</label>
            <select name="id_exercice" required class="w-full border rounded px-3 py-2">
                <option value="">-- Choisir un exercice --</option>
                <?php foreach ($exercises as $ex): ?>
                    <option value="<?= $ex['id_exercice'] ?>"><?= htmlspecialchars($ex['nom_exercice']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block font-medium text-gray-700">Sets</label>
                <input type="number" name="sets" min="1" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium text-gray-700">Reps</label>
                <input type="number" name="reps" min="1" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium text-gray-700">Poids (kg)</label>
                <input type="number" step="0.5" name="weight" min="0" required class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <button type="submit" name="add_workout" class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700 transition">
            Ajouter le workout
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="workout_history.php" class="text-purple-600 underline">Voir mon historique complet →</a>
    </div>
</div>

<?php include '../include/footer.php'; ?>