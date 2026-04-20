<?php
// auth/login_admin.php
require_once '../config/database.php';
require_once '../include/functions.php';

// Si déjà connecté en tant qu'admin, rediriger
if (isLoggedIn() && $_SESSION['role'] == 'admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT * FROM utilisateurs WHERE email = :email AND role = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
                $_SESSION['role'] = $user['role'];
                header("Location: ../admin/dashboard.php");
                exit();
            } else {
                $error = "Mot de passe incorrect";
            }
        } else {
            $error = "Accès réservé aux administrateurs";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - Smart Fitness</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>body{background:linear-gradient(135deg,#2d2d2d 0%,#1a1a1a 100%);min-height:100vh;}</style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <i class="fas fa-user-shield text-4xl text-gray-700 mb-2"></i>
            <h1 class="text-3xl font-bold">Administration</h1>
            <p class="text-gray-600">Accès réservé</p>
        </div>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-4"><input type="email" name="email" placeholder="Email" required class="w-full p-3 border rounded"></div>
            <div class="mb-4"><input type="password" name="password" placeholder="Mot de passe" required class="w-full p-3 border rounded"></div>
            <button type="submit" class="w-full bg-gray-700 text-white py-3 rounded hover:bg-gray-800">Se connecter</button>
        </form>
        <div class="text-center mt-4 text-sm">
            <a href="login_user.php" class="text-gray-600">Espace utilisateur</a> | 
            <a href="login_coach.php" class="text-gray-600">Espace coach</a>
        </div>
    </div>
</body>
</html>