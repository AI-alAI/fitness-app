<?php
// admin/exercices.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Gestion du CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $nom = $_POST['nom_exercice'];
        $categorie = $_POST['categorie'];
        $description = $_POST['description'];
        $niveau = $_POST['niveau_difficulte'];
        $stmt = $db->prepare("INSERT INTO exercices (nom_exercice, categorie, description, niveau_difficulte) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $categorie, $description, $niveau]);
        $_SESSION['message'] = "Exercice ajouté.";
    } elseif (isset($_POST['edit'])) {
        $id = $_POST['id_exercice'];
        $nom = $_POST['nom_exercice'];
        $categorie = $_POST['categorie'];
        $description = $_POST['description'];
        $niveau = $_POST['niveau_difficulte'];
        $stmt = $db->prepare("UPDATE exercices SET nom_exercice = ?, categorie = ?, description = ?, niveau_difficulte = ? WHERE id_exercice = ?");
        $stmt->execute([$nom, $categorie, $description, $niveau, $id]);
        $_SESSION['message'] = "Exercice modifié.";
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['delete'];
        $stmt = $db->prepare("DELETE FROM exercices WHERE id_exercice = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Exercice supprimé.";
    }
    header("Location: exercices.php");
    exit();
}

// Récupérer tous les exercices
$exercices = $db->query("SELECT * FROM exercices ORDER BY categorie, nom_exercice")->fetchAll();

$page_title = 'Gestion des exercices (Admin)';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Gestion des exercices</h1>
    <p class="text-gray-600 mt-2">Ajoutez, modifiez ou supprimez des exercices (catalogue global).</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<!-- Bouton pour afficher/cacher le formulaire d'ajout -->
<button onclick="document.getElementById('addForm').classList.toggle('hidden')" class="mb-4 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">+ Ajouter un exercice</button>

<!-- Formulaire d'ajout (caché par défaut) -->
<div id="addForm" class="hidden bg-white p-6 rounded-xl shadow-md mb-6">
    <h2 class="text-xl font-bold mb-4">Nouvel exercice</h2>
    <form method="POST">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block font-semibold mb-1">Nom *</label><input type="text" name="nom_exercice" required class="w-full p-2 border rounded"></div>
            <div><label class="block font-semibold mb-1">Catégorie</label><input type="text" name="categorie" class="w-full p-2 border rounded"></div>
            <div class="md:col-span-2"><label class="block font-semibold mb-1">Description</label><textarea name="description" rows="2" class="w-full p-2 border rounded"></textarea></div>
            <div><label class="block font-semibold mb-1">Niveau</label><select name="niveau_difficulte" class="w-full p-2 border rounded"><option value="facile">Facile</option><option value="moyen">Moyen</option><option value="difficile">Difficile</option></select></div>
        </div>
        <button type="submit" name="add" class="mt-4 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Créer</button>
    </form>
</div>

<!-- Liste des exercices -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($exercices as $exo): ?>
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <form method="POST">
                    <input type="hidden" name="id_exercice" value="<?php echo $exo['id_exercice']; ?>">
                    <div class="flex justify-between items-start">
                        <input type="text" name="nom_exercice" value="<?php echo htmlspecialchars($exo['nom_exercice']); ?>" class="text-xl font-bold border-b border-gray-300 w-2/3 mb-2" required>
                        <div>
                            <button type="submit" name="edit" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-save"></i></button>
                            <button type="submit" name="delete" value="<?php echo $exo['id_exercice']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Supprimer cet exercice ?')"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <input type="text" name="categorie" value="<?php echo htmlspecialchars($exo['categorie']); ?>" class="w-full p-1 border rounded mb-2">
                    <textarea name="description" rows="2" class="w-full p-1 border rounded mb-2"><?php echo htmlspecialchars($exo['description']); ?></textarea>
                    <select name="niveau_difficulte" class="w-full p-1 border rounded">
                        <option value="facile" <?php echo $exo['niveau_difficulte'] == 'facile' ? 'selected' : ''; ?>>Facile</option>
                        <option value="moyen" <?php echo $exo['niveau_difficulte'] == 'moyen' ? 'selected' : ''; ?>>Moyen</option>
                        <option value="difficile" <?php echo $exo['niveau_difficulte'] == 'difficile' ? 'selected' : ''; ?>>Difficile</option>
                    </select>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (count($exercices) == 0): ?>
    <div class="bg-gray-100 rounded-xl p-8 text-center text-gray-500">Aucun exercice disponible.</div>
<?php endif; ?>

<?php include '../include/footer.php'; ?>