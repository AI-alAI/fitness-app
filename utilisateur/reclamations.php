<?php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['utilisateur']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Handle new complaint submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reclamation'])) {
    $sujet = trim($_POST['sujet']);
    $message = trim($_POST['message']);
    if (!empty($sujet) && !empty($message)) {
        $stmt = $db->prepare("INSERT INTO reclamations (id_utilisateur, sujet, message) VALUES (:user, :sujet, :msg)");
        $stmt->bindParam(':user', $user_id);
        $stmt->bindParam(':sujet', $sujet);
        $stmt->bindParam(':msg', $message);
        $stmt->execute();
        $_SESSION['message'] = "Votre réclamation a été envoyée.";
        header("Location: reclamations.php");
        exit();
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// Fetch user's complaints
$stmt = $db->prepare("SELECT * FROM reclamations WHERE id_utilisateur = :user ORDER BY date_creation DESC");
$stmt->bindParam(':user', $user_id);
$stmt->execute();
$reclamations = $stmt->fetchAll();

$page_title = 'Mes réclamations';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Mes réclamations</h1>
    <p class="text-gray-600">Contactez l'administrateur pour tout problème.</p>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 p-4 mb-4 rounded"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<button onclick="document.getElementById('addModal').classList.remove('hidden')" class="mb-6 bg-purple-600 text-white px-4 py-2 rounded">Nouvelle réclamation</button>

<div class="space-y-4">
    <?php foreach ($reclamations as $r): ?>
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-bold"><?php echo htmlspecialchars($r['sujet']); ?></h3>
                <span class="px-2 py-1 text-xs rounded-full 
                    <?php echo $r['statut'] == 'ouvert' ? 'bg-red-100 text-red-700' : ($r['statut'] == 'en_cours' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'); ?>">
                    <?php echo ucfirst($r['statut']); ?>
                </span>
            </div>
            <p class="text-gray-700 mt-2"><?php echo nl2br(htmlspecialchars($r['message'])); ?></p>
            <p class="text-xs text-gray-400 mt-2">Envoyé le <?php echo date('d/m/Y H:i', strtotime($r['date_creation'])); ?></p>
            <?php if ($r['reponse_admin']): ?>
                <div class="mt-3 p-3 bg-gray-50 rounded">
                    <p class="font-semibold">Réponse de l'administrateur :</p>
                    <p><?php echo nl2br(htmlspecialchars($r['reponse_admin'])); ?></p>
                    <p class="text-xs text-gray-400 mt-1">Répondu le <?php echo date('d/m/Y H:i', strtotime($r['date_reponse'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (count($reclamations) == 0): ?>
        <p class="text-gray-500 text-center py-8">Aucune réclamation.</p>
    <?php endif; ?>
</div>

<!-- Modal nouvel réclamation -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Nouvelle réclamation</h3><button onclick="document.getElementById('addModal').classList.add('hidden')">&times;</button></div>
        <form method="POST">
            <div class="mb-3"><input type="text" name="sujet" placeholder="Sujet" required class="w-full p-2 border rounded"></div>
            <div class="mb-3"><textarea name="message" rows="4" placeholder="Décrivez votre problème..." required class="w-full p-2 border rounded"></textarea></div>
            <button type="submit" name="submit_reclamation" class="w-full bg-purple-600 text-white py-2 rounded">Envoyer</button>
        </form>
    </div>
</div>

<?php include '../include/footer.php'; ?>