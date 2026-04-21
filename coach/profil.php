<?php
// coach/profil.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Fetch current user data
$stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch coach specific data (specialite, bio)
$stmt = $db->prepare("SELECT specialite, bio FROM coachs WHERE id_utilisateur = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$coach = $stmt->fetch(PDO::FETCH_ASSOC);

$success = '';
$error = '';

// Handle profile update (personal + coach data)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $age = intval($_POST['age']);
    $niveau = $_POST['niveau'];
    $specialite = trim($_POST['specialite']);
    $bio = trim($_POST['bio']);

    // Email uniqueness check (skip if same email)
    if ($email !== $user['email']) {
        $check = $db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = :email AND id_utilisateur != :id");
        $check->bindParam(':email', $email);
        $check->bindParam(':id', $user_id);
        $check->execute();
        if ($check->rowCount() > 0) {
            $error = "Cet email est déjà utilisé par un autre compte.";
        }
    }

    if (empty($error)) {
        // Update utilisateurs table
        $stmt = $db->prepare("UPDATE utilisateurs SET nom = :nom, prenom = :prenom, email = :email, age = :age, niveau = :niveau WHERE id_utilisateur = :id");
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':niveau', $niveau);
        $stmt->bindParam(':id', $user_id);
        $user_update = $stmt->execute();

        // Update coachs table
        $stmt2 = $db->prepare("UPDATE coachs SET specialite = :spec, bio = :bio WHERE id_utilisateur = :id");
        $stmt2->bindParam(':spec', $specialite);
        $stmt2->bindParam(':bio', $bio);
        $stmt2->bindParam(':id', $user_id);
        $coach_update = $stmt2->execute();

        if ($user_update && $coach_update) {
            $_SESSION['user_name'] = $prenom . ' ' . $nom;
            $success = "Profil mis à jour avec succès.";
            // Refresh local data
            $user['nom'] = $nom;
            $user['prenom'] = $prenom;
            $user['email'] = $email;
            $user['age'] = $age;
            $user['niveau'] = $niveau;
            $coach['specialite'] = $specialite;
            $coach['bio'] = $bio;
        } else {
            $error = "Erreur lors de la mise à jour.";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!password_verify($current_password, $user['mot_de_passe'])) {
        $error = "Mot de passe actuel incorrect.";
    } elseif (strlen($new_password) < 6) {
        $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE utilisateurs SET mot_de_passe = :pwd WHERE id_utilisateur = :id");
        $stmt->bindParam(':pwd', $hashed);
        $stmt->bindParam(':id', $user_id);
        if ($stmt->execute()) {
            $success = "Mot de passe modifié avec succès.";
        } else {
            $error = "Erreur lors du changement de mot de passe.";
        }
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo']) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/profiles/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        $error = "Format de fichier non autorisé (JPEG, PNG, GIF, WEBP).";
    } else {
        $newName = uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $newName)) {
            if (!empty($user['photo']) && file_exists($uploadDir . $user['photo'])) {
                unlink($uploadDir . $user['photo']);
            }
            $stmt = $db->prepare("UPDATE utilisateurs SET photo = :photo WHERE id_utilisateur = :id");
            $stmt->bindParam(':photo', $newName);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $user['photo'] = $newName;
            $success = "Photo de profil mise à jour.";
        } else {
            $error = "Erreur lors de l'upload.";
        }
    }
}

$page_title = 'Mon profil (Coach)';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Mon profil</h1>
    <p class="text-gray-600">Modifiez vos informations personnelles et professionnelles.</p>
</div>

<?php if ($success): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($success); ?></div>
<?php elseif ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Photo de profil -->
    <div class="bg-white rounded-xl shadow-md p-6 text-center">
        <div class="w-32 h-32 mx-auto rounded-full bg-gray-200 overflow-hidden flex items-center justify-center mb-4">
            <?php if (!empty($user['photo']) && file_exists('../uploads/profiles/' . $user['photo'])): ?>
                <img src="../uploads/profiles/<?php echo htmlspecialchars($user['photo']); ?>" alt="Photo de profil" class="w-full h-full object-cover">
            <?php else: ?>
                <i class="fas fa-user-circle text-6xl text-gray-400"></i>
            <?php endif; ?>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <input type="file" name="photo" accept="image/*" class="w-full text-sm border rounded p-1">
            </div>
            <button type="submit" name="upload_photo" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">Changer la photo</button>
        </form>
    </div>

    <!-- Formulaire informations personnelles et professionnelles -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Informations</h2>
        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-1">Nom *</label>
                    <input type="text" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Prénom *</label>
                    <input type="text" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold mb-1">Email *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Âge</label>
                    <input type="number" name="age" value="<?php echo $user['age']; ?>" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Niveau (sportif)</label>
                    <select name="niveau" class="w-full px-3 py-2 border rounded-lg">
                        <option value="debutant" <?php echo $user['niveau'] == 'debutant' ? 'selected' : ''; ?>>Débutant</option>
                        <option value="intermediaire" <?php echo $user['niveau'] == 'intermediaire' ? 'selected' : ''; ?>>Intermédiaire</option>
                        <option value="avance" <?php echo $user['niveau'] == 'avance' ? 'selected' : ''; ?>>Avancé</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold mb-1">Spécialité</label>
                    <input type="text" name="specialite" value="<?php echo htmlspecialchars($coach['specialite'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold mb-1">Bio / Présentation</label>
                    <textarea name="bio" rows="3" class="w-full px-3 py-2 border rounded-lg"><?php echo htmlspecialchars($coach['bio'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" name="update_profile" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>

<!-- Changement de mot de passe -->
<div class="bg-white rounded-xl shadow-md p-6 mt-6">
    <h2 class="text-xl font-bold mb-4">Changer mon mot de passe</h2>
    <form method="POST" class="max-w-md">
        <div class="mb-4">
            <label class="block text-sm font-bold mb-1">Mot de passe actuel *</label>
            <input type="password" name="current_password" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-bold mb-1">Nouveau mot de passe *</label>
            <input type="password" name="new_password" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-bold mb-1">Confirmer le nouveau mot de passe *</label>
            <input type="password" name="confirm_password" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <button type="submit" name="change_password" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700">Changer le mot de passe</button>
    </form>
</div>

<?php include '../include/footer.php'; ?>