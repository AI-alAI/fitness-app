<?php
// admin/programmes.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Supprimer un programme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_programme = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM programmes WHERE id_programme = :id");
    $stmt->bindParam(':id', $id_programme);
    $stmt->execute();
    $_SESSION['message'] = "Programme supprimé (les assignations et liaisons avec exercices sont également supprimées).";
    header("Location: programmes.php");
    exit();
}

// Récupérer tous les programmes avec les infos du coach
$query = "SELECT p.*, u.nom, u.prenom, u.email,
          (SELECT COUNT(*) FROM assignations WHERE id_programme = p.id_programme) as nb_assignations
          FROM programmes p
          JOIN coachs c ON p.id_coach = c.id_coach
          JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
          ORDER BY p.date_creation DESC";
$programmes = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Tous les programmes (Admin)';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Tous les programmes</h1>
    <p class="text-gray-600 mt-2">Liste des programmes créés par les coachs. Vous pouvez les supprimer en cas d’abus.</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Titre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coach</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Niveau</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durée</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Créé le</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($programmes) > 0): ?>
                    <?php foreach ($programmes as $prog): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4"><?php echo htmlspecialchars($prog['titre']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($prog['prenom'] . ' ' . $prog['nom']); ?><br><span class="text-xs text-gray-500"><?php echo htmlspecialchars($prog['email']); ?></span></td>
                            <td class="px-6 py-4"><?php echo ucfirst($prog['niveau_cible']); ?></td>
                            <td class="px-6 py-4"><?php echo $prog['duree_semaines']; ?> sem.</td>
                            <td class="px-6 py-4"><?php echo number_format($prog['price'], 2); ?> €</td>
                            <td class="px-6 py-4"><?php echo $prog['nb_assignations']; ?></td>
                            <td class="px-6 py-4"><?php echo date('d/m/Y', strtotime($prog['date_creation'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="?delete=<?php echo $prog['id_programme']; ?>" onclick="return confirm('Supprimer ce programme ? Toutes les assignations et liaisons avec exercices seront également supprimées.')" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">Aucun programme trouvé.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../include/footer.php'; ?>