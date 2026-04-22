<?php
// coach/reclamations.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['coach', 'admin']);

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

// Fetch coach's complaints
$stmt = $db->prepare("SELECT * FROM reclamations WHERE id_utilisateur = :user ORDER BY date_creation DESC");
$stmt->bindParam(':user', $user_id);
$stmt->execute();
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status counts
$status_counts = [
    'ouvert' => 0,
    'en_cours' => 0,
    'resolu' => 0
];
foreach ($reclamations as $r) {
    if (isset($status_counts[$r['statut']])) $status_counts[$r['statut']]++;
}

$page_title = 'Mes réclamations';
include '../include/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Mes réclamations</h1>
    <p class="text-gray-600 mt-2">Contactez l'administrateur pour tout problème.</p>
</div>

<!-- Status summary cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-red-50 rounded-xl p-4 text-center border border-red-200">
        <p class="text-2xl font-bold text-red-600"><?php echo $status_counts['ouvert']; ?></p>
        <p class="text-sm text-red-700">En attente</p>
    </div>
    <div class="bg-yellow-50 rounded-xl p-4 text-center border border-yellow-200">
        <p class="text-2xl font-bold text-yellow-600"><?php echo $status_counts['en_cours']; ?></p>
        <p class="text-sm text-yellow-700">En cours</p>
    </div>
    <div class="bg-green-50 rounded-xl p-4 text-center border border-green-200">
        <p class="text-2xl font-bold text-green-600"><?php echo $status_counts['resolu']; ?></p>
        <p class="text-sm text-green-700">Résolues</p>
    </div>
</div>

<!-- Success/Error messages -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Modern button -->
<div class="mb-6">
    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium px-6 py-3 rounded-lg shadow-md transition transform hover:scale-105 flex items-center gap-2">
        <i class="fas fa-plus-circle"></i> Nouvelle réclamation
    </button>
</div>

<!-- Complaints list -->
<div class="space-y-4">
    <?php foreach ($reclamations as $r): ?>
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 transition hover:shadow-lg">
            <div class="p-5">
                <div class="flex flex-wrap justify-between items-start gap-2">
                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($r['sujet']); ?></h3>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full 
                        <?php echo $r['statut'] == 'ouvert' ? 'bg-red-100 text-red-700' : ($r['statut'] == 'en_cours' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'); ?>">
                        <?php echo $r['statut'] == 'ouvert' ? 'En attente' : ($r['statut'] == 'en_cours' ? 'En cours' : 'Résolu'); ?>
                    </span>
                </div>
                <p class="text-gray-700 mt-3"><?php echo nl2br(htmlspecialchars($r['message'])); ?></p>
                <div class="mt-3 flex justify-between items-center text-sm text-gray-500">
                    <span><i class="far fa-calendar-alt mr-1"></i> <?php echo date('d/m/Y H:i', strtotime($r['date_creation'])); ?></span>
                    <?php if ($r['statut'] != 'resolu'): ?>
                        <span class="text-yellow-600"><i class="fas fa-clock mr-1"></i> En traitement</span>
                    <?php else: ?>
                        <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Terminé</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($r['reponse_admin'])): ?>
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg border-l-4 border-purple-500">
                        <p class="font-semibold text-purple-700"><i class="fas fa-reply mr-2"></i>Réponse de l'administrateur :</p>
                        <p class="text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars($r['reponse_admin'])); ?></p>
                        <p class="text-xs text-gray-400 mt-2">Répondu le <?php echo date('d/m/Y H:i', strtotime($r['date_reponse'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (count($reclamations) == 0): ?>
        <div class="bg-gray-50 rounded-xl p-12 text-center text-gray-500 border-2 border-dashed border-gray-300">
            <i class="fas fa-inbox text-5xl mb-3 text-gray-400"></i>
            <p class="text-lg">Aucune réclamation pour le moment.</p>
            <p class="text-sm">Cliquez sur "Nouvelle réclamation" pour contacter l'administrateur.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal nouvelle réclamation (improved design) -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto z-50">
    <div class="relative top-20 mx-auto p-6 border-0 w-96 shadow-2xl rounded-2xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Nouvelle réclamation</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sujet *</label>
                <input type="text" name="sujet" placeholder="Ex: Problème de paiement, bug technique..." required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                <textarea name="message" rows="5" placeholder="Décrivez votre problème en détail..." required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"></textarea>
            </div>
            <button type="submit" name="submit_reclamation" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium py-2 rounded-lg transition shadow-md">
                <i class="fas fa-paper-plane mr-2"></i> Envoyer
            </button>
        </form>
    </div>
</div>

<?php include '../include/footer.php'; ?>