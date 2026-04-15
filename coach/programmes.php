<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Récupérer l'id_coach
$stmt = $db->prepare("SELECT id_coach FROM coachs WHERE id_utilisateur = :user");
$stmt->bindParam(':user', $_SESSION['user_id']);
$stmt->execute();
$coach = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$coach) {
    die("Erreur : Vous n'êtes pas enregistré comme coach.");
}
$coach_id = $coach['id_coach'];

// Traitement CRUD programme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $titre = $_POST['titre'];
            $description = $_POST['description'];
            $niveau_cible = $_POST['niveau_cible'];
            $duree_semaines = $_POST['duree_semaines'];
            $price = floatval($_POST['price'] ?? 0);
            $date_creation = date('Y-m-d');
            $query = "INSERT INTO programmes (id_coach, titre, description, niveau_cible, duree_semaines, price, date_creation)
                      VALUES (:coach, :titre, :desc, :niveau, :duree, :price, :date)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':coach', $coach_id);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':desc', $description);
            $stmt->bindParam(':niveau', $niveau_cible);
            $stmt->bindParam(':duree', $duree_semaines);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':date', $date_creation);
            $stmt->execute();
            $programme_id = $db->lastInsertId();
            // Gestion des exercices associés
            if (isset($_POST['exercices']) && is_array($_POST['exercices'])) {
                foreach ($_POST['exercices'] as $exo_id) {
                    $stmt2 = $db->prepare("INSERT INTO programme_exercices (id_programme, id_exercice) VALUES (:prog, :exo)");
                    $stmt2->bindParam(':prog', $programme_id);
                    $stmt2->bindParam(':exo', $exo_id);
                    $stmt2->execute();
                }
            }
            $_SESSION['message'] = "Programme ajouté avec succès";
        } elseif ($_POST['action'] === 'edit') {
            $id_programme = $_POST['id_programme'];
            $titre = $_POST['titre'];
            $description = $_POST['description'];
            $niveau_cible = $_POST['niveau_cible'];
            $duree_semaines = $_POST['duree_semaines'];
            $price = floatval($_POST['price'] ?? 0);
            $query = "UPDATE programmes SET titre=:titre, description=:desc, niveau_cible=:niveau, duree_semaines=:duree, price=:price
                      WHERE id_programme=:id AND id_coach=:coach";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':desc', $description);
            $stmt->bindParam(':niveau', $niveau_cible);
            $stmt->bindParam(':duree', $duree_semaines);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':id', $id_programme);
            $stmt->bindParam(':coach', $coach_id);
            $stmt->execute();
            // Mettre à jour les exercices associés
            // Supprimer les anciens
            $del = $db->prepare("DELETE FROM programme_exercices WHERE id_programme = :prog");
            $del->bindParam(':prog', $id_programme);
            $del->execute();
            // Ajouter les nouveaux
            if (isset($_POST['exercices']) && is_array($_POST['exercices'])) {
                foreach ($_POST['exercices'] as $exo_id) {
                    $ins = $db->prepare("INSERT INTO programme_exercices (id_programme, id_exercice) VALUES (:prog, :exo)");
                    $ins->bindParam(':prog', $id_programme);
                    $ins->bindParam(':exo', $exo_id);
                    $ins->execute();
                }
            }
            $_SESSION['message'] = "Programme modifié";
        } elseif ($_POST['action'] === 'delete') {
            $id_programme = $_POST['id_programme'];
            $query = "DELETE FROM programmes WHERE id_programme=:id AND id_coach=:coach";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id_programme);
            $stmt->bindParam(':coach', $coach_id);
            $stmt->execute();
            $_SESSION['message'] = "Programme supprimé";
        }
        header("Location: programmes.php");
        exit();
    }
}

// Récupérer tous les programmes du coach
$query = "SELECT * FROM programmes WHERE id_coach = :coach ORDER BY date_creation DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':coach', $coach_id);
$stmt->execute();
$programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les exercices pour les listes déroulantes
$exercices = $db->query("SELECT id_exercice, nom_exercice, categorie FROM exercices ORDER BY categorie, nom_exercice")->fetchAll();

// Pour chaque programme, récupérer les exercices associés (pour affichage)
foreach ($programmes as &$prog) {
    $stmt = $db->prepare("SELECT e.id_exercice, e.nom_exercice FROM programme_exercices pe JOIN exercices e ON pe.id_exercice = e.id_exercice WHERE pe.id_programme = :prog");
    $stmt->bindParam(':prog', $prog['id_programme']);
    $stmt->execute();
    $prog['exercices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = 'Gestion des programmes';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Mes programmes</h1>
    <p class="text-gray-600 mt-2">Créez, modifiez ou supprimez vos programmes. Vous pouvez associer des exercices.</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<button onclick="document.getElementById('addModal').classList.remove('hidden')" class="mb-6 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700">Nouveau programme</button>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($programmes as $prog): ?>
        <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <h3 class="text-xl font-bold"><?php echo htmlspecialchars($prog['titre']); ?></h3>
                    <div class="flex space-x-2">
                        <button onclick='editProgramme(<?php echo json_encode($prog); ?>)' class="text-blue-600"><i class="fas fa-edit"></i></button>
                        <form method="POST" onsubmit="return confirm('Supprimer ?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_programme" value="<?php echo $prog['id_programme']; ?>"><button type="submit" class="text-red-600"><i class="fas fa-trash"></i></button></form>
                    </div>
                </div>
                <p class="text-gray-600 text-sm mt-2"><?php echo nl2br(htmlspecialchars($prog['description'])); ?></p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <span class="px-2 py-1 bg-gray-100 rounded">Niveau: <?php echo ucfirst($prog['niveau_cible']); ?></span>
                    <span class="px-2 py-1 bg-gray-100 rounded"><?php echo $prog['duree_semaines']; ?> semaines</span>
                    <span class="px-2 py-1 bg-purple-100 rounded"><?php echo number_format($prog['price'], 2); ?> €</span>
                </div>
                <?php if (count($prog['exercices']) > 0): ?>
                    <div class="mt-3 text-sm">
                        <span class="font-semibold">Exercices associés :</span>
                        <?php foreach ($prog['exercices'] as $ex): ?>
                            <span class="inline-block bg-gray-200 px-2 py-1 rounded-full text-xs mr-1"><?php echo htmlspecialchars($ex['nom_exercice']); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="assignations.php?programme_id=<?php echo $prog['id_programme']; ?>" class="text-purple-600 text-sm hover:underline">Assigner à des utilisateurs →</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Ajout -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Nouveau programme</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-3"><label>Titre *</label><input type="text" name="titre" required class="w-full p-2 border rounded"></div>
            <div class="mb-3"><label>Description</label><textarea name="description" rows="2" class="w-full p-2 border rounded"></textarea></div>
            <div class="mb-3"><label>Niveau cible</label><select name="niveau_cible" class="w-full p-2 border rounded"><option value="debutant">Débutant</option><option value="intermediaire">Intermédiaire</option><option value="avance">Avancé</option></select></div>
            <div class="mb-3"><label>Durée (semaines)</label><input type="number" name="duree_semaines" class="w-full p-2 border rounded"></div>
            <div class="mb-3"><label>Prix (€)</label><input type="number" step="0.01" name="price" class="w-full p-2 border rounded"></div>
            <div class="mb-3"><label>Exercices associés (Ctrl+clic pour plusieurs)</label><select name="exercices[]" multiple class="w-full p-2 border rounded h-32">
                <?php foreach ($exercices as $ex): ?>
                    <option value="<?php echo $ex['id_exercice']; ?>"><?php echo htmlspecialchars($ex['categorie'] . ' - ' . $ex['nom_exercice']); ?></option>
                <?php endforeach; ?>
            </select></div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded">Créer</button>
        </form>
    </div>
</div>

<!-- Modal Édition -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4"><h3 class="text-lg font-bold">Modifier programme</h3><button onclick="document.getElementById('editModal').classList.add('hidden')">&times;</button></div>
        <form id="editForm" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_programme" id="edit_id">
            <div class="mb-3"><label>Titre</label><input type="text" name="titre" id="edit_titre" required class="w-full p-2 border rounded"></div>
            <div class="mb-3"><label>Description</label><textarea name="description" id="edit_description" rows="2" class="w-full p-2 border rounded"></textarea></div>
            <div class="mb-3"><label>Niveau cible</label><select name="niveau_cible" id="edit_niveau" class="w-full p-2 border rounded"><option value="debutant">Débutant</option><option value="intermediaire">Intermédiaire</option><option value="avance">Avancé</option></select></div>
            <div class="mb-3"><label>Durée (semaines)</label><input type="number" name="duree_semaines" id="edit_duree" class="w-full p-2 border rounded"></div>
            <div class="mb-3"><label>Prix (€)</label><input type="number" step="0.01" name="price" id="edit_price" class="w-full p-2 border rounded"></div>
            <div class="mb-3"><label>Exercices associés (Ctrl+clic)</label><select name="exercices[]" multiple id="edit_exercices" class="w-full p-2 border rounded h-32">
                <?php foreach ($exercices as $ex): ?>
                    <option value="<?php echo $ex['id_exercice']; ?>"><?php echo htmlspecialchars($ex['categorie'] . ' - ' . $ex['nom_exercice']); ?></option>
                <?php endforeach; ?>
            </select></div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded">Enregistrer</button>
        </form>
    </div>
</div>

<script>
function editProgramme(prog) {
    document.getElementById('edit_id').value = prog.id_programme;
    document.getElementById('edit_titre').value = prog.titre;
    document.getElementById('edit_description').value = prog.description;
    document.getElementById('edit_niveau').value = prog.niveau_cible;
    document.getElementById('edit_duree').value = prog.duree_semaines;
    document.getElementById('edit_price').value = prog.price;
    // Pré-sélectionner les exercices associés
    let exoSelect = document.getElementById('edit_exercices');
    for(let i = 0; i < exoSelect.options.length; i++) {
        exoSelect.options[i].selected = false;
    }
    if (prog.exercices) {
        let exoIds = prog.exercices.map(e => e.id_exercice);
        for(let i = 0; i < exoSelect.options.length; i++) {
            if (exoIds.includes(parseInt(exoSelect.options[i].value))) {
                exoSelect.options[i].selected = true;
            }
        }
    }
    document.getElementById('editModal').classList.remove('hidden');
}
</script>

<?php include '../include/footer.php'; ?>