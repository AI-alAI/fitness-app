<?php
// auth/login_user.php
require_once '../config/database.php';
require_once '../include/functions.php';

// Rediriger si déjà connecté
if (isLoggedIn() && $_SESSION['role'] == 'utilisateur') {
    header("Location: ../utilisateur/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT * FROM utilisateurs WHERE email = :email AND role = 'utilisateur'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
                $_SESSION['role'] = $user['role'];
                header("Location: ../utilisateur/dashboard.php");
                exit();
            } else {
                $error = "Mot de passe incorrect";
            }
        } else {
            $error = "Aucun compte utilisateur trouvé avec cet email";
        }
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Utilisateur - Smart Fitness</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;}</style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <i class="fas fa-user text-4xl text-purple-600 mb-2"></i>
            <h1 class="text-3xl font-bold">Espace Utilisateur</h1>
            <p class="text-gray-600">Connectez-vous à votre compte</p>
        </div>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-4"><input type="email" name="email" placeholder="Email" required class="w-full p-3 border rounded"></div>
            <div class="mb-4"><input type="password" name="password" placeholder="Mot de passe" required class="w-full p-3 border rounded"></div>
            <button type="submit" class="w-full bg-purple-600 text-white py-3 rounded hover:bg-purple-700">Se connecter</button>
        </form>
        <div class="text-center mt-4">
            <p>Pas encore de compte ? <a href="register.php" class="text-purple-600">Inscription</a></p>
            <p class="text-sm mt-2">Vous êtes coach ? <a href="login_coach.php" class="text-purple-600">Connexion coach</a></p>
            <p class="text-sm">Administrateur ? <a href="login_admin.php" class="text-purple-600">Connexion admin</a></p>
        </div>
    </div>
</body>
</html>