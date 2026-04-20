<?php
// admin/utilisateurs.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Handle actions (change role/level, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $user_id = intval($_POST['user_id']);
        $role = $_POST['role'];
        $niveau = $_POST['niveau'];
        $stmt = $db->prepare("UPDATE utilisateurs SET role = :role, niveau = :niveau WHERE id_utilisateur = :id");
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':niveau', $niveau);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $_SESSION['message'] = "Utilisateur mis à jour.";
    } elseif (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        // Prevent deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['message'] = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            $stmt = $db->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $_SESSION['message'] = "Utilisateur supprimé.";
        }
    }
    header("Location: utilisateurs.php");
    exit();
}

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM utilisateurs WHERE 1=1";
$params = [];
if (!empty($search)) {
    $query .= " AND (nom LIKE :search OR prenom LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}
$query .= " ORDER BY date_inscription DESC";
$stmt = $db->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestion des utilisateurs';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Gestion des utilisateurs</h1>
    <p class="text-gray-600 mt-2">Liste de tous les comptes (utilisateurs, coachs, administrateurs).</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<!-- Search bar -->
<div class="mb-6 flex justify-between items-center">
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" placeholder="Rechercher par nom ou email" value="<?php echo htmlspecialchars($search); ?>" class="px-4 py-2 border rounded-lg w-64">
        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">Chercher</button>
        <?php if ($search): ?>
            <a href="utilisateurs.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">Réinitialiser</a>
        <?php endif; ?>
    </form>
</div>

<!-- Users table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom complet</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Niveau</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inscrit le</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['id_utilisateur']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-6 py-4">
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id_utilisateur']; ?>">
                                    <select name="role" onchange="this.form.submit()" class="text-sm border rounded px-2 py-1">
                                        <option value="utilisateur" <?php echo $user['role'] == 'utilisateur' ? 'selected' : ''; ?>>Utilisateur</option>
                                        <option value="coach" <?php echo $user['role'] == 'coach' ? 'selected' : ''; ?>>Coach</option>
                                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <input type="hidden" name="update_user" value="1">
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id_utilisateur']; ?>">
                                    <select name="niveau" onchange="this.form.submit()" class="text-sm border rounded px-2 py-1">
                                        <option value="debutant" <?php echo $user['niveau'] == 'debutant' ? 'selected' : ''; ?>>Débutant</option>
                                        <option value="intermediaire" <?php echo $user['niveau'] == 'intermediaire' ? 'selected' : ''; ?>>Intermédiaire</option>
                                        <option value="avance" <?php echo $user['niveau'] == 'avance' ? 'selected' : ''; ?>>Avancé</option>
                                    </select>
                                    <input type="hidden" name="update_user" value="1">
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($user['id_utilisateur'] != $_SESSION['user_id']): ?>
                                    <form method="POST" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?')" class="inline-block">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id_utilisateur']; ?>">
                                        <button type="submit" name="delete_user" value="1" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-400" title="Vous ne pouvez pas vous supprimer vous-même"><i class="fas fa-lock"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">Aucun utilisateur trouvé.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../include/footer.php'; ?>