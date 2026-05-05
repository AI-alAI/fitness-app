<?php
// utilisateur/exercices.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['utilisateur']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get programmes purchased by user
$stmt = $db->prepare("SELECT id_programme FROM assignations WHERE id_utilisateur = :user");
$stmt->bindParam(':user', $user_id);
$stmt->execute();
$programmes_achetes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$exercices = [];
if (!empty($programmes_achetes)) {
    $table_check = $db->query("SHOW TABLES LIKE 'programme_exercices'")->rowCount();
    if ($table_check > 0) {
        $in = str_repeat('?,', count($programmes_achetes) - 1) . '?';
        $sql = "SELECT e.* FROM exercices e
                JOIN programme_exercices pe ON e.id_exercice = pe.id_exercice
                WHERE pe.id_programme IN ($in)
                GROUP BY e.id_exercice
                ORDER BY e.categorie, e.nom_exercice";
        $stmt = $db->prepare($sql);
        $stmt->execute($programmes_achetes);
        $exercices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $in = str_repeat('?,', count($programmes_achetes) - 1) . '?';
        $sql = "SELECT * FROM exercices WHERE id_programme IN ($in) ORDER BY categorie, nom_exercice";
        $stmt = $db->prepare($sql);
        $stmt->execute($programmes_achetes);
        $exercices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$page_title = 'Mes exercices';
include '../include/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">Mes exercices</h1>
        <p class="text-gray-600 mt-2 text-lg">Vidéos disponibles pour les programmes que vous avez achetés</p>
        <div class="w-24 h-1 bg-purple-600 rounded-full mx-auto mt-4"></div>
    </div>

    <?php if (empty($programmes_achetes)): ?>
        <div class="bg-white rounded-xl shadow-md p-8 text-center max-w-2xl mx-auto">
            <div class="text-6xl mb-4">🏋️</div>
            <h3 class="text-2xl font-semibold text-gray-800">Aucun programme acheté</h3>
            <p class="text-gray-500 mt-2">Découvrez nos formations pour accéder aux exercices.</p>
            <a href="programmes_disponibles.php" class="inline-block mt-6 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">Découvrir →</a>
        </div>
    <?php elseif (count($exercices) == 0): ?>
        <div class="bg-white rounded-xl shadow-md p-8 text-center max-w-2xl mx-auto">
            <div class="text-6xl mb-4">📹</div>
            <h3 class="text-2xl font-semibold text-gray-800">Aucun exercice associé</h3>
            <p class="text-gray-500 mt-2">Votre coach n'a pas encore ajouté d'exercices à ce programme.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($exercices as $exo): 
                $difficulty = $exo['niveau_difficulte'];
                $badgeColor = $difficulty == 'facile' ? 'bg-green-100 text-green-700' : ($difficulty == 'moyen' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                $video = $exo['video_url'];
            ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition duration-300 flex flex-col">
                    <div class="p-6 flex-1">
                        <div class="flex justify-between items-start">
                            <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($exo['nom_exercice']); ?></h3>
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $badgeColor; ?>">
                                <?php echo ucfirst($difficulty); ?>
                            </span>
                        </div>
                        <div class="mt-2 flex items-center text-gray-500 text-sm">
                            <i class="fas fa-tag mr-1"></i>
                            <span><?php echo htmlspecialchars($exo['categorie'] ?: 'Général'); ?></span>
                        </div>
                        <p class="text-gray-600 text-sm mt-3">
                            <?php echo nl2br(htmlspecialchars($exo['description'])); ?>
                        </p>
                    </div>

                    <div class="px-6 pb-6">
                        <?php if (!empty($video)): ?>
                            <?php
                            // Vérifier si c'est une vidéo uploadée (stockée dans uploads/videos/)
                            if (strpos($video, 'uploads/videos/') === 0) {
                                $filePath = '../' . $video;
                                if (file_exists($filePath)): ?>
                                    <video controls class="w-full rounded-lg shadow">
                                        <source src="<?php echo '../' . $video; ?>" type="video/mp4">
                                    </video>
                                <?php else: ?>
                                    <div class="text-center text-red-500 bg-red-50 rounded-lg py-3 text-sm">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Vidéo non trouvée
                                    </div>
                                <?php endif;
                            } else {
                                // Essayer d'extraire un embed YouTube
                                $embed = getYouTubeEmbedUrl($video);
                                if ($embed): ?>
                                    <div class="relative pt-[56.25%]">
                                        <iframe class="absolute inset-0 w-full h-full rounded-lg" src="<?php echo $embed; ?>" frameborder="0" allowfullscreen></iframe>
                                    </div>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($video); ?>" target="_blank" class="block text-center text-purple-600 hover:underline">
                                        <i class="fas fa-external-link-alt mr-1"></i> Voir la vidéo (lien externe)
                                    </a>
                                <?php endif;
                            }
                            ?>
                        <?php else: ?>
                            <div class="text-center text-gray-400 bg-gray-50 rounded-lg py-4 text-sm">
                                <i class="fas fa-video-slash mr-1"></i> Aucune vidéo
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../include/footer.php'; ?>