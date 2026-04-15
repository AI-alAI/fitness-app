<?php
// auth/register.php
require_once '../config/database.php';
require_once '../include/functions.php';

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
            
            $query = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, age, poids, taille, niveau, role) 
                      VALUES (:nom, :prenom, :email, :password, :age, :poids, :taille, :niveau, 'utilisateur')";
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
                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
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
    <title>Inscription - Smart Fitness</title>
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
                <i class="fas fa-user-plus text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Créer un compte</h1>
            <p class="text-gray-600 mt-2">Rejoignez Smart Fitness aujourd'hui</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <p><?php echo htmlspecialchars($success); ?></p>
                <a href="login.php" class="text-green-800 font-semibold underline">Se connecter</a>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nom *</label>
                    <input type="text" name="nom" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Prénom *</label>
                    <input type="text" name="prenom" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Mot de passe *</label>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Confirmer mot de passe *</label>
                    <input type="password" name="confirm_password" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Âge</label>
                    <input type="number" name="age" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Poids (kg)</label>
                    <input type="number" step="0.1" name="poids" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Taille (cm)</label>
                    <input type="number" step="0.1" name="taille" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Niveau</label>
                    <select name="niveau" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                        <option value="debutant">Débutant</option>
                        <option value="intermediaire">Intermédiaire</option>
                        <option value="avance">Avancé</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" 
                    class="w-full mt-6 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold py-3 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition transform hover:scale-105">
                <i class="fas fa-user-plus mr-2"></i>S'inscrire
            </button>
        </form>
        
        <div class="text-center mt-6">
            <p class="text-gray-600">Déjà un compte ? 
                <a href="login.php" class="text-purple-600 hover:text-purple-800 font-semibold">Se connecter</a>
            </p>
        </div>
    </div>
</body>
</html>