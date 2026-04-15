<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();

$uploadDir = '../uploads/videos/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// Fonction pour extraire l'ID YouTube
function getYouTubeEmbedUrl($url) {
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
    return isset($matches[1]) ? 'https://www.youtube.com/embed/' . $matches[1] : null;
}

// CRUD (identique à avant, mais j'ajoute la gestion de la suppression de fichier)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // ADD
        if ($_POST['action'] === 'add') {
            $nom = $_POST['nom_exercice'];
            $categorie = $_POST['categorie'];
            $description = $_POST['description'];
            $niveau = $_POST['niveau_difficulte'];
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

            $stmt = $db->prepare("INSERT INTO exercices (nom_exercice, categorie, description, niveau_difficulte, video_url) VALUES (:nom, :cat, :desc, :niv, :video)");
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':cat', $categorie);
            $stmt->bindParam(':desc', $description);
            $stmt->bindParam(':niv', $niveau);
            $stmt->bindParam(':video', $video_url);
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

            $stmt = $db->prepare("UPDATE exercices SET nom_exercice=:nom, categorie=:cat, description=:desc, niveau_difficulte=:niv, video_url=:video WHERE id_exercice=:id");
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':cat', $categorie);
            $stmt->bindParam(':desc', $description);
            $stmt->bindParam(':niv', $niveau);
            $stmt->bindParam(':video', $video_url);
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

$exercices = $db->query("SELECT * FROM exercices ORDER BY categorie, nom_exercice")->fetchAll();
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

<button onclick="document.getElementById('addModal').classList.remove('hidden')" class="mb-6 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700">Ajouter un exercice</button>

<!-- Liste des exercices avec aperçu vidéo -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($exercices as $exo): ?>
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <h3 class="text-xl font-bold"><?php echo htmlspecialchars($exo['nom_exercice']); ?></h3>
                    <div class="flex space-x-2">
                        <button onclick='editExercice(<?php echo json_encode($exo); ?>)' class="text-blue-600"><i class="fas fa-edit"></i></button>
                        <form method="POST" onsubmit="return confirm('Supprimer ?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_exercice" value="<?php echo $exo['id_exercice']; ?>"><button type="submit" class="text-red-600"><i class="fas fa-trash"></i></button></form>
                    </div>
                </div>
                <p class="text-gray-600 text-sm mt-2"><?php echo nl2br(htmlspecialchars($exo['description'])); ?></p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <span class="px-2 py-1 bg-gray-100 rounded"><?php echo htmlspecialchars($exo['categorie']); ?></span>
                    <span class="px-2 py-1 rounded <?php echo $exo['niveau_difficulte']=='facile'?'bg-green-100':($exo['niveau_difficulte']=='moyen'?'bg-yellow-100':'bg-red-100'); ?>"><?php echo ucfirst($exo['niveau_difficulte']); ?></span>
                </div>
                <!-- Aperçu vidéo dans la carte -->
                <?php if (!empty($exo['video_url'])): ?>
                    <div class="mt-4">
                        <?php
                        $video = $exo['video_url'];
                        if (strpos($video, 'uploads/videos/') === 0 && file_exists('../' . $video)): ?>
                            <video controls class="w-full rounded-lg max-h-48">
                                <source src="../<?php echo $video; ?>" type="video/mp4">
                            </video>
                        <?php else:
                            $embed = getYouTubeEmbedUrl($video);
                            if ($embed): ?>
                                <div class="relative pb-[56.25%] h-0 rounded-lg overflow-hidden">
                                    <iframe class="absolute top-0 left-0 w-full h-full" src="<?php echo $embed; ?>" frameborder="0" allowfullscreen></iframe>
                                </div>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($video); ?>" target="_blank" class="text-purple-600 text-sm">Lien vidéo externe</a>
                            <?php endif;
                        endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Ajout avec prévisualisation -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-lg bg-white">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Nouvel exercice</h3><button onclick="document.getElementById('addModal').classList.add('hidden')">&times;</button></div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="mb-2"><label>Nom *</label><input type="text" name="nom_exercice" required class="w-full p-2 border rounded"></div>
            <div class="mb-2"><label>Catégorie</label><input type="text" name="categorie" class="w-full p-2 border rounded"></div>
            <div class="mb-2"><label>Description</label><textarea name="description" rows="2" class="w-full p-2 border rounded"></textarea></div>
            <div class="mb-2"><label>Niveau</label><select name="niveau_difficulte" class="w-full p-2 border rounded"><option value="facile">Facile</option><option value="moyen">Moyen</option><option value="difficile">Difficile</option></select></div>
            <div class="mb-2"><label>Uploader une vidéo (MP4, WebM, OGG)</label><input type="file" name="video_file" accept="video/*" class="w-full p-2 border rounded" id="add_video_file"></div>
            <div class="mb-2"><label>OU URL vidéo (YouTube)</label><input type="url" name="video_url" class="w-full p-2 border rounded" id="add_video_url" placeholder="https://www.youtube.com/watch?v=..."></div>
            <div class="mb-2" id="add_preview_area"></div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded mt-2">Ajouter</button>
        </form>
    </div>
</div>

<!-- Modal Édition avec prévisualisation -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-lg bg-white">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Modifier exercice</h3><button onclick="document.getElementById('editModal').classList.add('hidden')">&times;</button></div>
        <form id="editForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_exercice" id="edit_id">
            <input type="hidden" name="existing_video_url" id="edit_existing_video">
            <div class="mb-2"><label>Nom</label><input type="text" name="nom_exercice" id="edit_nom" required class="w-full p-2 border rounded"></div>
            <div class="mb-2"><label>Catégorie</label><input type="text" name="categorie" id="edit_categorie" class="w-full p-2 border rounded"></div>
            <div class="mb-2"><label>Description</label><textarea name="description" id="edit_description" rows="2" class="w-full p-2 border rounded"></textarea></div>
            <div class="mb-2"><label>Niveau</label><select name="niveau_difficulte" id="edit_niveau" class="w-full p-2 border rounded"><option value="facile">Facile</option><option value="moyen">Moyen</option><option value="difficile">Difficile</option></select></div>
            <div class="mb-2"><label>Remplacer par une nouvelle vidéo (fichier)</label><input type="file" name="video_file" accept="video/*" class="w-full p-2 border rounded" id="edit_video_file"></div>
            <div class="mb-2"><label>OU modifier l'URL vidéo existante</label><input type="url" name="video_url" id="edit_video_url" class="w-full p-2 border rounded" placeholder="https://..."></div>
            <div class="mb-2" id="edit_preview_area"></div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded">Enregistrer</button>
        </form>
    </div>
</div>

<script>
// Fonction pour générer l'aperçu vidéo (fichier ou URL)
function updatePreview(fileInput, urlInput, previewDiv, isEdit = false) {
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
        // Si c'est YouTube, on convertit en embed pour preview
        const youtubeRegex = /(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&?\/\s]{11})/;
        const match = videoUrl.match(youtubeRegex);
        if (match) {
            videoUrl = 'https://www.youtube.com/embed/' + match[1];
        }
    } else {
        previewDiv.innerHTML = '';
        return;
    }

    // Créer l'élément vidéo/iframe
    if (videoUrl.includes('youtube.com/embed')) {
        previewDiv.innerHTML = `<div class="relative pb-[56.25%] h-0 mt-2"><iframe class="absolute top-0 left-0 w-full h-full" src="${videoUrl}" frameborder="0" allowfullscreen></iframe></div>`;
    } else if (videoUrl.startsWith('blob:') || videoUrl.endsWith('.mp4') || videoUrl.includes('uploads/')) {
        previewDiv.innerHTML = `<video controls class="w-full rounded-lg max-h-48 mt-2"><source src="${videoUrl}" type="video/mp4"></video>`;
    } else {
        previewDiv.innerHTML = `<video controls class="w-full rounded-lg max-h-48 mt-2"><source src="${videoUrl}" type="video/mp4"></video>`;
    }
}

// Pour le modal d'ajout
const addFile = document.getElementById('add_video_file');
const addUrl = document.getElementById('add_video_url');
const addPreview = document.getElementById('add_preview_area');
addFile.addEventListener('change', () => updatePreview(addFile, addUrl, addPreview));
addUrl.addEventListener('input', () => updatePreview(addFile, addUrl, addPreview));

// Pour le modal d'édition
let editFile = document.getElementById('edit_video_file');
let editUrl = document.getElementById('edit_video_url');
let editPreview = document.getElementById('edit_preview_area');
editFile.addEventListener('change', () => updatePreview(editFile, editUrl, editPreview, true));
editUrl.addEventListener('input', () => updatePreview(editFile, editUrl, editPreview, true));

function editExercice(exo) {
    document.getElementById('edit_id').value = exo.id_exercice;
    document.getElementById('edit_nom').value = exo.nom_exercice;
    document.getElementById('edit_categorie').value = exo.categorie;
    document.getElementById('edit_description').value = exo.description;
    document.getElementById('edit_niveau').value = exo.niveau_difficulte;
    document.getElementById('edit_video_url').value = exo.video_url || '';
    document.getElementById('edit_existing_video').value = exo.video_url || '';
    // Nettoyer preview et fichiers
    document.getElementById('edit_video_file').value = '';
    // Afficher preview de la vidéo existante
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
</script>

<?php include '../include/footer.php'; ?>