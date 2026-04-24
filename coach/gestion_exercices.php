<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

$uploadDir = '../uploads/videos/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// getYouTubeEmbedUrl() is already defined in functions.php, so no local declaration needed.

// Get coach ID (for programme filtering)
$stmt = $db->prepare("SELECT id_coach FROM coachs WHERE id_utilisateur = :user");
$stmt->bindParam(':user', $_SESSION['user_id']);
$stmt->execute();
$coach = $stmt->fetch(PDO::FETCH_ASSOC);
$coach_id = $coach ? $coach['id_coach'] : 0;

// Get all programmes (for linking exercises)
$programmes = [];
if ($coach_id) {
    $stmt = $db->prepare("SELECT id_programme, titre FROM programmes WHERE id_coach = :coach ORDER BY titre");
    $stmt->bindParam(':coach', $coach_id);
    $stmt->execute();
    $programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // ADD
        if ($_POST['action'] === 'add') {
            $nom = $_POST['nom_exercice'];
            $categorie = $_POST['categorie'];
            $description = $_POST['description'];
            $niveau = $_POST['niveau_difficulte'];
            $id_programme = !empty($_POST['id_programme']) ? intval($_POST['id_programme']) : null;
            $video_url = '';

            if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['mp4', 'webm', 'ogg'];
                $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $newName = uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadDir . $newName)) {
                        $video_url = 'uploads/videos/' . $newName;
                    }
                }
            } elseif (!empty($_POST['video_url'])) {
                $video_url = $_POST['video_url'];
            }

            $stmt = $db->prepare("INSERT INTO exercices (nom_exercice, categorie, description, niveau_difficulte, video_url, id_programme) VALUES (:nom, :cat, :desc, :niv, :video, :prog)");
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':cat', $categorie);
            $stmt->bindParam(':desc', $description);
            $stmt->bindParam(':niv', $niveau);
            $stmt->bindParam(':video', $video_url);
            $stmt->bindParam(':prog', $id_programme);
            $stmt->execute();
            $_SESSION['message'] = "Exercice ajouté";
        }
        // EDIT
        elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id_exercice'];
            $nom = $_POST['nom_exercice'];
            $categorie = $_POST['categorie'];
            $description = $_POST['description'];
            $niveau = $_POST['niveau_difficulte'];
            $id_programme = !empty($_POST['id_programme']) ? intval($_POST['id_programme']) : null;
            $video_url = $_POST['existing_video_url'] ?? '';

            if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['mp4', 'webm', 'ogg'];
                $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $newName = uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadDir . $newName)) {
                        if (!empty($video_url) && strpos($video_url, 'uploads/') === 0 && file_exists('../' . $video_url)) {
                            unlink('../' . $video_url);
                        }
                        $video_url = 'uploads/videos/' . $newName;
                    }
                }
            } elseif (!empty($_POST['video_url']) && $_POST['video_url'] !== ($_POST['existing_video_url'] ?? '')) {
                $video_url = $_POST['video_url'];
            }

            $stmt = $db->prepare("UPDATE exercices SET nom_exercice=:nom, categorie=:cat, description=:desc, niveau_difficulte=:niv, video_url=:video, id_programme=:prog WHERE id_exercice=:id");
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':cat', $categorie);
            $stmt->bindParam(':desc', $description);
            $stmt->bindParam(':niv', $niveau);
            $stmt->bindParam(':video', $video_url);
            $stmt->bindParam(':prog', $id_programme);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $_SESSION['message'] = "Exercice modifié";
        }
        // DELETE
        elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id_exercice'];
            $stmt = $db->prepare("SELECT video_url FROM exercices WHERE id_exercice=:id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $exo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($exo && !empty($exo['video_url']) && strpos($exo['video_url'], 'uploads/') === 0) {
                $filePath = '../' . $exo['video_url'];
                if (file_exists($filePath)) unlink($filePath);
            }
            $stmt = $db->prepare("DELETE FROM exercices WHERE id_exercice=:id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $_SESSION['message'] = "Exercice supprimé";
        }
        header("Location: gestion_exercices.php");
        exit();
    }
}

// Fetch all exercises with programme title
$exercices = $db->query("SELECT e.*, p.titre as programme_titre 
                         FROM exercices e
                         LEFT JOIN programmes p ON e.id_programme = p.id_programme
                         ORDER BY e.categorie, e.nom_exercice")->fetchAll();

// Statistics
$total_exercices = count($exercices);
$total_videos = count(array_filter($exercices, fn($e) => !empty($e['video_url'])));
$total_with_programme = count(array_filter($exercices, fn($e) => !empty($e['id_programme'])));

$page_title = 'Gestion des exercices';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Gestion des exercices</h1>
    <p class="text-gray-600">Ajoutez, modifiez ou supprimez des exercices. Vous pouvez uploader une vidéo (MP4, WebM, OGG) ou mettre un lien YouTube.</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-4 text-center">
        <p class="text-gray-500 text-sm">Total exercices</p>
        <p class="text-2xl font-bold text-purple-600"><?php echo $total_exercices; ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-4 text-center">
        <p class="text-gray-500 text-sm">Avec vidéo</p>
        <p class="text-2xl font-bold text-blue-600"><?php echo $total_videos; ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-4 text-center">
        <p class="text-gray-500 text-sm">Liés à un programme</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $total_with_programme; ?></p>
    </div>
</div>

<!-- Search and Add -->
<div class="flex flex-wrap justify-between items-center mb-6 gap-4">
    <div class="relative">
        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        <input type="text" id="searchExercise" placeholder="Rechercher un exercice..." class="pl-10 pr-4 py-2 border rounded-lg w-64 focus:outline-none focus:border-purple-500">
    </div>
    <button onclick="openAddModal()" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
        <i class="fas fa-plus mr-2"></i>Ajouter un exercice
    </button>
</div>

<!-- Exercises Grid -->
<div id="exercisesContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($exercices as $exo): ?>
        <div class="bg-white rounded-xl shadow-md overflow-hidden exercise-card" data-name="<?php echo strtolower(htmlspecialchars($exo['nom_exercice'])); ?>">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold"><?php echo htmlspecialchars($exo['nom_exercice']); ?></h3>
                        <?php if ($exo['programme_titre']): ?>
                            <p class="text-xs text-purple-600 mt-1">Programme: <?php echo htmlspecialchars($exo['programme_titre']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick='editExercice(<?php echo json_encode($exo); ?>)' class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" onsubmit="return confirm('Supprimer définitivement cet exercice ?');" class="inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_exercice" value="<?php echo $exo['id_exercice']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <p class="text-gray-600 text-sm mt-2"><?php echo nl2br(htmlspecialchars(substr($exo['description'], 0, 100))); ?></p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <span class="px-2 py-1 bg-gray-100 rounded"><?php echo htmlspecialchars($exo['categorie'] ?: 'Général'); ?></span>
                    <span class="px-2 py-1 rounded <?php echo $exo['niveau_difficulte']=='facile'?'bg-green-100':($exo['niveau_difficulte']=='moyen'?'bg-yellow-100':'bg-red-100'); ?>">
                        <?php echo ucfirst($exo['niveau_difficulte']); ?>
                    </span>
                </div>
                <?php if (!empty($exo['video_url'])): ?>
                    <div class="mt-4">
                        <?php
                        $video = $exo['video_url'];
                        if (strpos($video, 'uploads/videos/') === 0 && file_exists('../' . $video)): ?>
                            <video controls class="w-full rounded-lg max-h-48">
                                <source src="../<?php echo $video; ?>" type="video/mp4">
                            </video>
                        <?php else:
                            $embed = getYouTubeEmbedUrl($video); // Uses function from functions.php
                            if ($embed): ?>
                                <div class="relative pb-[56.25%] h-0 rounded-lg overflow-hidden">
                                    <iframe class="absolute top-0 left-0 w-full h-full" src="<?php echo $embed; ?>" frameborder="0" allowfullscreen></iframe>
                                </div>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($video); ?>" target="_blank" class="text-purple-600 text-sm">Lien vidéo externe</a>
                            <?php endif;
                        endif; ?>
                    </div>
                <?php else: ?>
                    <div class="mt-4 text-gray-400 text-sm"><i class="fas fa-video-slash mr-1"></i> Aucune vidéo</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (count($exercices) == 0): ?>
    <div class="text-center py-8 text-gray-500">Aucun exercice créé pour le moment.</div>
<?php endif; ?>

<!-- Modal Add -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-lg bg-white">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Nouvel exercice</h3><button onclick="closeAddModal()">&times;</button></div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="mb-2"><label class="font-semibold">Nom *</label><input type="text" name="nom_exercice" required class="w-full p-2 border rounded"></div>
            <div class="mb-2"><label class="font-semibold">Catégorie</label><input type="text" name="categorie" class="w-full p-2 border rounded"></div>
            <div class="mb-2"><label class="font-semibold">Description</label><textarea name="description" rows="2" class="w-full p-2 border rounded"></textarea></div>
            <div class="mb-2"><label class="font-semibold">Niveau</label><select name="niveau_difficulte" class="w-full p-2 border rounded"><option value="facile">Facile</option><option value="moyen">Moyen</option><option value="difficile">Difficile</option></select></div>
            <div class="mb-2"><label class="font-semibold">Associer à un programme</label><select name="id_programme" class="w-full p-2 border rounded">
                <option value="">-- Aucun --</option>
                <?php foreach ($programmes as $prog): ?>
                    <option value="<?php echo $prog['id_programme']; ?>"><?php echo htmlspecialchars($prog['titre']); ?></option>
                <?php endforeach; ?>
            </select></div>
            <div class="mb-2"><label class="font-semibold">Uploader une vidéo (MP4, WebM, OGG)</label><input type="file" name="video_file" accept="video/*" class="w-full p-2 border rounded" id="add_video_file"></div>
            <div class="mb-2"><label class="font-semibold">OU URL vidéo (YouTube)</label><input type="url" name="video_url" class="w-full p-2 border rounded" id="add_video_url" placeholder="https://www.youtube.com/watch?v=..."></div>
            <div class="mb-2" id="add_preview_area"></div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded mt-2">Ajouter</button>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-lg bg-white">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Modifier exercice</h3><button onclick="closeEditModal()">&times;</button></div>
        <form id="editForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_exercice" id="edit_id">
            <input type="hidden" name="existing_video_url" id="edit_existing_video">
            <div class="mb-2"><label class="font-semibold">Nom</label><input type="text" name="nom_exercice" id="edit_nom" required class="w-full p-2 border rounded"></div>
            <div class="mb-2"><label class="font-semibold">Catégorie</label><input type="text" name="categorie" id="edit_categorie" class="w-full p-2 border rounded"></div>
            <div class="mb-2"><label class="font-semibold">Description</label><textarea name="description" id="edit_description" rows="2" class="w-full p-2 border rounded"></textarea></div>
            <div class="mb-2"><label class="font-semibold">Niveau</label><select name="niveau_difficulte" id="edit_niveau" class="w-full p-2 border rounded"><option value="facile">Facile</option><option value="moyen">Moyen</option><option value="difficile">Difficile</option></select></div>
            <div class="mb-2"><label class="font-semibold">Associer à un programme</label><select name="id_programme" id="edit_programme" class="w-full p-2 border rounded">
                <option value="">-- Aucun --</option>
                <?php foreach ($programmes as $prog): ?>
                    <option value="<?php echo $prog['id_programme']; ?>"><?php echo htmlspecialchars($prog['titre']); ?></option>
                <?php endforeach; ?>
            </select></div>
            <div class="mb-2"><label class="font-semibold">Remplacer par une nouvelle vidéo (fichier)</label><input type="file" name="video_file" accept="video/*" class="w-full p-2 border rounded" id="edit_video_file"></div>
            <div class="mb-2"><label class="font-semibold">OU modifier l'URL vidéo existante</label><input type="url" name="video_url" id="edit_video_url" class="w-full p-2 border rounded" placeholder="https://..."></div>
            <div class="mb-2" id="edit_preview_area"></div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded">Enregistrer</button>
        </form>
    </div>
</div>

<script>
// Search filter
document.getElementById('searchExercise').addEventListener('keyup', function() {
    let searchValue = this.value.toLowerCase();
    document.querySelectorAll('.exercise-card').forEach(card => {
        let name = card.getAttribute('data-name');
        card.style.display = name.includes(searchValue) ? '' : 'none';
    });
});

// Preview functions
function updatePreview(fileInput, urlInput, previewDiv) {
    let videoUrl = '';
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        if (file.type.startsWith('video/')) {
            videoUrl = URL.createObjectURL(file);
        } else {
            previewDiv.innerHTML = '<p class="text-red-500 text-sm">Format non supporté</p>';
            return;
        }
    } else if (urlInput.value.trim() !== '') {
        videoUrl = urlInput.value.trim();
        const youtubeRegex = /(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&?\/\s]{11})/;
        const match = videoUrl.match(youtubeRegex);
        if (match) videoUrl = 'https://www.youtube.com/embed/' + match[1];
    } else {
        previewDiv.innerHTML = '';
        return;
    }
    if (videoUrl.includes('youtube.com/embed')) {
        previewDiv.innerHTML = `<div class="relative pb-[56.25%] h-0 mt-2"><iframe class="absolute top-0 left-0 w-full h-full" src="${videoUrl}" frameborder="0" allowfullscreen></iframe></div>`;
    } else {
        previewDiv.innerHTML = `<video controls class="w-full rounded-lg max-h-48 mt-2"><source src="${videoUrl}" type="video/mp4"></video>`;
    }
}

// Add modal preview
const addFile = document.getElementById('add_video_file');
const addUrl = document.getElementById('add_video_url');
const addPreview = document.getElementById('add_preview_area');
addFile.addEventListener('change', () => updatePreview(addFile, addUrl, addPreview));
addUrl.addEventListener('input', () => updatePreview(addFile, addUrl, addPreview));

// Edit modal preview
let editFile = document.getElementById('edit_video_file');
let editUrl = document.getElementById('edit_video_url');
let editPreview = document.getElementById('edit_preview_area');
editFile.addEventListener('change', () => updatePreview(editFile, editUrl, editPreview));
editUrl.addEventListener('input', () => updatePreview(editFile, editUrl, editPreview));

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('add_video_file').value = '';
    document.getElementById('add_video_url').value = '';
    document.getElementById('add_preview_area').innerHTML = '';
}
function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}
function editExercice(exo) {
    document.getElementById('edit_id').value = exo.id_exercice;
    document.getElementById('edit_nom').value = exo.nom_exercice;
    document.getElementById('edit_categorie').value = exo.categorie || '';
    document.getElementById('edit_description').value = exo.description || '';
    document.getElementById('edit_niveau').value = exo.niveau_difficulte;
    document.getElementById('edit_programme').value = exo.id_programme || '';
    document.getElementById('edit_video_url').value = exo.video_url || '';
    document.getElementById('edit_existing_video').value = exo.video_url || '';
    document.getElementById('edit_video_file').value = '';
    // Preview existing video
    let previewDiv = document.getElementById('edit_preview_area');
    if (exo.video_url) {
        let video = exo.video_url;
        if (video.includes('youtube.com/watch') || video.includes('youtu.be')) {
            let match = video.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&?\/\s]{11})/);
            if (match) {
                previewDiv.innerHTML = `<div class="relative pb-[56.25%] h-0 mt-2"><iframe class="absolute top-0 left-0 w-full h-full" src="https://www.youtube.com/embed/${match[1]}" frameborder="0" allowfullscreen></iframe></div>`;
            } else {
                previewDiv.innerHTML = `<video controls class="w-full rounded-lg max-h-48 mt-2"><source src="${video}" type="video/mp4"></video>`;
            }
        } else if (video.startsWith('uploads/')) {
            previewDiv.innerHTML = `<video controls class="w-full rounded-lg max-h-48 mt-2"><source src="../${video}" type="video/mp4"></video>`;
        } else {
            previewDiv.innerHTML = `<video controls class="w-full rounded-lg max-h-48 mt-2"><source src="${video}" type="video/mp4"></video>`;
        }
    } else {
        previewDiv.innerHTML = '';
    }
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php include '../include/footer.php'; ?>