<?php
// auth/register_coach.php
require_once '../config/database.php';
require_once '../include/functions.php';

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
    $confirm = $_POST['confirm_password'] ?? '';
    $specialite = $_POST['specialite'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $age = $_POST['age'] ?? null;
    $niveau = $_POST['niveau'] ?? 'avance';

    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Tous les champs obligatoires doivent être remplis";
    } elseif ($password !== $confirm) {
        $error = "Mots de passe différents";
    } else {
        $database = new Database();
        $db = $database->getConnection();

        $check = $db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = :email");
        $check->bindParam(':email', $email);
        $check->execute();
        if ($check->rowCount() > 0) {
            $error = "Email déjà utilisé";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, age, niveau, role, date_inscription) 
                      VALUES (:nom, :prenom, :email, :pwd, :age, :niveau, 'coach', NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':pwd', $hash);
            $stmt->bindParam(':age', $age);
            $stmt->bindParam(':niveau', $niveau);
            if ($stmt->execute()) {
                $userId = $db->lastInsertId();
                $stmt2 = $db->prepare("INSERT INTO coachs (id_utilisateur, specialite, bio) VALUES (:id, :spec, :bio)");
                $stmt2->bindParam(':id', $userId);
                $stmt2->bindParam(':spec', $specialite);
                $stmt2->bindParam(':bio', $bio);
                $stmt2->execute();
                $success = "Inscription coach réussie ! <a href='login_coach.php'>Connectez-vous</a>";
            } else {
                $error = "Erreur lors de l'inscription";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Coach - Smart Fitness</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:linear-gradient(135deg,#1e3c2c 0%,#2a5a3a 100%);min-height:100vh;}</style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-8">
        <div class="text-center mb-8">
            <i class="fas fa-chalkboard-user text-4xl text-green-600 mb-2"></i>
            <h1 class="text-3xl font-bold">Devenir Coach</h1>
        </div>
        <?php if ($error): ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><input type="text" name="nom" placeholder="Nom" required class="w-full p-3 border rounded"></div>
                <div><input type="text" name="prenom" placeholder="Prénom" required class="w-full p-3 border rounded"></div>
                <div class="md:col-span-2"><input type="email" name="email" placeholder="Email" required class="w-full p-3 border rounded"></div>
                <div><input type="password" name="password" placeholder="Mot de passe" required class="w-full p-3 border rounded"></div>
                <div><input type="password" name="confirm_password" placeholder="Confirmer" required class="w-full p-3 border rounded"></div>
                <div><input type="number" name="age" placeholder="Âge" class="w-full p-3 border rounded"></div>
                <div>
                    <select name="niveau" class="w-full p-3 border rounded">
                        <option value="debutant">Débutant</option>
                        <option value="intermediaire">Intermédiaire</option>
                        <option value="avance" selected>Avancé</option>
                    </select>
                </div>
                <div class="md:col-span-2"><input type="text" name="specialite" placeholder="Spécialité (ex: Musculation)" class="w-full p-3 border rounded"></div>
                <div class="md:col-span-2"><textarea name="bio" rows="2" placeholder="Bio / Présentation" class="w-full p-3 border rounded"></textarea></div>
            </div>
            <button type="submit" class="w-full mt-6 bg-green-600 text-white py-3 rounded hover:bg-green-700">S'inscrire</button>
        </form>
        <div class="text-center mt-4">Déjà coach ? <a href="login_coach.php" class="text-green-600">Se connecter</a></div>
    </div>
</body>
</html>