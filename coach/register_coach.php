<?php
require_once '../config/database.php';
require_once '../include/functions.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header("Location: ../" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $specialite = $_POST['specialite'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $age = $_POST['age'] ?? null;
    $niveau = $_POST['niveau'] ?? 'avance';
    
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Tous les champs obligatoires doivent être remplis";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Vérifier si l'email existe déjà
        $check = $db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = :email");
        $check->bindParam(':email', $email);
        $check->execute();
        
        if ($check->rowCount() > 0) {
            $error = "Cet email est déjà utilisé";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insérer dans utilisateurs avec role 'coach'
            $query = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, age, niveau, role, date_inscription) 
                      VALUES (:nom, :prenom, :email, :password, :age, :niveau, 'coach', NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':age', $age);
            $stmt->bindParam(':niveau', $niveau);
            
            if ($stmt->execute()) {
                $user_id = $db->lastInsertId();
                // Insérer dans la table coachs
                $query2 = "INSERT INTO coachs (id_utilisateur, specialite, bio) VALUES (:id, :specialite, :bio)";
                $stmt2 = $db->prepare($query2);
                $stmt2->bindParam(':id', $user_id);
                $stmt2->bindParam(':specialite', $specialite);
                $stmt2->bindParam(':bio', $bio);
                $stmt2->execute();
                
                $success = "Inscription coach réussie ! Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Une erreur est survenue lors de l'inscription";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Coach - Smart Fitness</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-full mb-4">
                <i class="fas fa-chalkboard-user text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Devenir Coach</h1>
            <p class="text-gray-600 mt-2">Inscrivez-vous pour encadrer des sportifs</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <?php echo htmlspecialchars($success); ?>
                <div class="mt-2"><a href="login.php" class="text-green-800 font-semibold underline">Se connecter</a></div>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nom *</label>
                    <input type="text" name="nom" required class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Prénom *</label>
                    <input type="text" name="prenom" required class="w-full px-4 py-3 border rounded-lg">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 border rounded-lg">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Mot de passe *</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 border rounded-lg">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Confirmer *</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-3 border rounded-lg">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Âge</label>
                    <input type="number" name="age" class="w-full px-4 py-3 border rounded-lg">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Niveau (en tant que sportif)</label>
                    <select name="niveau" class="w-full px-4 py-3 border rounded-lg">
                        <option value="debutant">Débutant</option>
                        <option value="intermediaire">Intermédiaire</option>
                        <option value="avance">Avancé</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Spécialité</label>
                    <input type="text" name="specialite" placeholder="ex: Musculation, Cardio, Yoga..." class="w-full px-4 py-3 border rounded-lg">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Bio / Présentation</label>
                    <textarea name="bio" rows="3" placeholder="Parlez de votre expérience..." class="w-full px-4 py-3 border rounded-lg"></textarea>
                </div>
            </div>
            
            <button type="submit" class="w-full mt-6 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold py-3 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition">
                <i class="fas fa-user-plus mr-2"></i>S'inscrire en tant que coach
            </button>
        </form>
        
        <div class="text-center mt-6">
            <p class="text-gray-600">Déjà un compte coach ? 
                <a href="../auth/login.php" class="text-purple-600 hover:text-purple-800 font-semibold">Se connecter</a>
            </p>
            <p class="text-gray-500 text-sm mt-2">
                Vous êtes un simple utilisateur ? <a href="register.php" class="text-purple-600">Inscription classique</a>
            </p>
        </div>
    </div>
</body>
</html>