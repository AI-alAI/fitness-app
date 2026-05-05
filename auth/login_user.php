<?php
// auth/login_user.php
require_once '../config/database.php';
require_once '../include/functions.php';

// Si déjà connecté, rediriger
if (isLoggedIn() && $_SESSION['role'] == 'utilisateur') {
    header("Location: ../utilisateur/dashboard.php");
    exit();
}

$error = '';
$email = $_POST['email'] ?? $_COOKIE['remember_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (!empty($email) && !empty($password)) {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = :email AND role = 'utilisateur'");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['role'] = $user['role'];

            if ($remember) {
                setcookie('remember_email', $email, time() + 86400 * 30, "/");
            } else {
                setcookie('remember_email', '', time() - 3600, "/");
            }
            header("Location: ../utilisateur/dashboard.php");
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Utilisateur - Smart Fitness</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;}</style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-6">
            <i class="fas fa-user-circle text-5xl text-purple-600 mb-2"></i>
            <h1 class="text-2xl font-bold text-gray-800">Connexion Utilisateur</h1>
            <p class="text-gray-500 text-sm">Accédez à votre espace fitness</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
                <input type="password" name="password" id="password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                <button type="button" id="togglePassword" class="absolute right-3 top-9 text-gray-500"><i class="fas fa-eye"></i></button>
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center"><input type="checkbox" name="remember" class="mr-2"> Se souvenir de moi</label>
            </div>
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 rounded-lg transition">Se connecter</button>
        </form>

        <div class="text-center mt-6 text-sm">
            <p>Pas encore de compte ? <a href="register.php" class="text-purple-600 font-semibold">Inscription</a></p>
            <p class="mt-2 text-gray-500">Coach ou Admin ? <a href="login_coach.php" class="text-purple-600">Espace coach</a></p>
        </div>
    </div>

    <script>
        const toggle = document.getElementById('togglePassword');
        const pwd = document.getElementById('password');
        toggle.addEventListener('click', () => {
            const type = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
            pwd.setAttribute('type', type);
            toggle.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    </script>
</body>
</html>