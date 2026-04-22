<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $id = intval($_POST['id']);
    $reponse = trim($_POST['reponse']);
    $statut = $_POST['statut'];
    if (!empty($reponse)) {
        $stmt = $db->prepare("UPDATE reclamations SET reponse_admin = :reponse, date_reponse = NOW(), statut = :statut WHERE id = :id");
        $stmt->bindParam(':reponse', $reponse);
        $stmt->bindParam(':statut', $statut);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $_SESSION['message'] = "Réponse envoyée.";
        header("Location: reclamations.php");
        exit();
    }
}

// Fetch all complaints
$reclamations = $db->query("SELECT r.*, u.nom, u.prenom, u.email FROM reclamations r JOIN utilisateurs u ON r.id_utilisateur = u.id_utilisateur ORDER BY r.date_creation DESC")->fetchAll();

$page_title = 'Réclamations';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Réclamations</h1>
    <p class="text-gray-600">Gérez les demandes des utilisateurs.</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 p-4 mb-4 rounded"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<div class="space-y-4">
    <?php foreach ($reclamations as $r): ?>
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-bold"><?php echo htmlspecialchars($r['sujet']); ?></h3>
                    <p class="text-sm text-gray-600">Par <?php echo htmlspecialchars($r['prenom'] . ' ' . $r['nom']); ?> (<?php echo $r['email']; ?>)</p>
                </div>
                <span class="px-2 py-1 text-xs rounded-full <?php echo $r['statut'] == 'ouvert' ? 'bg-red-100 text-red-700' : ($r['statut'] == 'en_cours' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'); ?>">
                    <?php echo ucfirst($r['statut']); ?>
                </span>
            </div>
            <p class="text-gray-700 mt-2"><?php echo nl2br(htmlspecialchars($r['message'])); ?></p>
            <p class="text-xs text-gray-400 mt-2"><?php echo date('d/m/Y H:i', strtotime($r['date_creation'])); ?></p>
            <?php if ($r['reponse_admin']): ?>
                <div class="mt-3 p-3 bg-gray-50 rounded">
                    <p class="font-semibold">Votre réponse :</p>
                    <p><?php echo nl2br(htmlspecialchars($r['reponse_admin'])); ?></p>
                </div>
            <?php endif; ?>
            <form method="POST" class="mt-3 border-t pt-3">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <textarea name="reponse" rows="2" class="w-full p-2 border rounded mb-2" placeholder="Votre réponse..."></textarea>
                <div class="flex gap-2">
                    <select name="statut" class="border rounded px-2 py-1">
                        <option value="ouvert" <?php echo $r['statut'] == 'ouvert' ? 'selected' : ''; ?>>Ouvert</option>
                        <option value="en_cours" <?php echo $r['statut'] == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="resolu" <?php echo $r['statut'] == 'resolu' ? 'selected' : ''; ?>>Résolu</option>
                    </select>
                    <button type="submit" name="reply" class="bg-purple-600 text-white px-4 py-1 rounded">Répondre</button>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if (count($reclamations) == 0): ?>
        <p class="text-gray-500 text-center py-8">Aucune réclamation.</p>
    <?php endif; ?>
</div>

<?php include '../include/footer.php'; ?>