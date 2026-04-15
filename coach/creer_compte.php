<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

$database = new Database();
$db = $database->getConnection();
$error = '';
$success = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $age = $_POST['age'] ?? null;
    $poids = $_POST['poids'] ?? null;
    $taille = $_POST['taille'] ?? null;
    $niveau = $_POST['niveau'] ?? 'debutant';
    
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Tous les champs obligatoires doivent être remplis";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        // Vérifier si l'email existe déjà
        $check = $db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = :email");
        $check->bindParam(':email', $email);
        $check->execute();
        
        if ($check->rowCount() > 0) {
            $error = "Cet email est déjà utilisé";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, age, poids, taille, niveau, role, date_inscription)
                      VALUES (:nom, :prenom, :email, :password, :age, :poids, :taille, :niveau, 'utilisateur', NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':age', $age);
            $stmt->bindParam(':poids', $poids);
            $stmt->bindParam(':taille', $taille);
            $stmt->bindParam(':niveau', $niveau);
            
            if ($stmt->execute()) {
                $success = "Compte utilisateur créé avec succès ! L'utilisateur peut maintenant se connecter.";
                // Réinitialiser le formulaire
                $_POST = [];
            } else {
                $error = "Une erreur est survenue lors de la création du compte";
            }
        }
    }
}

$page_title = 'Créer un compte utilisateur';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Créer un compte utilisateur</h1>
    <p class="text-gray-600 mt-2">Ajoutez un nouveau client (rôle utilisateur)</p>
</div>

<?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-md p-6 max-w-2xl">
    <form method="POST" action="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Nom *</label>
                <input type="text" name="nom" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-purple-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Prénom *</label>
                <input type="text" name="prenom" value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="md:col-span-2">
                <label class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Mot de passe *</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Confirmer mot de passe *</label>
                <input type="password" name="confirm_password" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Âge</label>
                <input type="number" name="age" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Poids (kg)</label>
                <input type="number" step="0.1" name="poids" value="<?php echo htmlspecialchars($_POST['poids'] ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Taille (cm)</label>
                <input type="number" step="0.1" name="taille" value="<?php echo htmlspecialchars($_POST['taille'] ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Niveau</label>
                <select name="niveau" class="w-full px-4 py-2 border rounded-lg">
                    <option value="debutant" <?php echo (($_POST['niveau'] ?? '') == 'debutant') ? 'selected' : ''; ?>>Débutant</option>
                    <option value="intermediaire" <?php echo (($_POST['niveau'] ?? '') == 'intermediaire') ? 'selected' : ''; ?>>Intermédiaire</option>
                    <option value="avance" <?php echo (($_POST['niveau'] ?? '') == 'avance') ? 'selected' : ''; ?>>Avancé</option>
                </select>
            </div>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold py-2 px-6 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition">
                <i class="fas fa-user-plus mr-2"></i>Créer le compte
            </button>
        </div>
    </form>
</div>

<?php include '../include/footer.php'; ?>