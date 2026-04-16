<?php
// utilisateur/exercices.php
require_once '../config/database.php';
require_once '../include/functions.php';
redirectIfNotRole(['utilisateur']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Helper function for YouTube embed
function getYouTubeEmbedUrl($url) {
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
    return isset($matches[1]) ? 'https://www.youtube.com/embed/' . $matches[1] : null;
}

// Get programmes purchased by user
$stmt = $db->prepare("SELECT id_programme FROM assignations WHERE id_utilisateur = :user");
$stmt->bindParam(':user', $user_id);
$stmt->execute();
$programmes_achetes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$exercices = [];
if (!empty($programmes_achetes)) {
    // Check if programme_exercices table exists (fallback if not)
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
        // Fallback: use exercices.id_programme column (older structure)
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

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Mes exercices</h1>
    <p class="text-gray-600">Vidéos disponibles uniquement pour les programmes que vous avez achetés.</p>
</div>

<?php if (empty($programmes_achetes)): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Vous n'avez encore acheté aucun programme. 
        <a href="programmes_disponibles.php" class="underline font-semibold">Découvrez nos programmes</a>
    </div>
<?php elseif (count($exercices) == 0): ?>
    <div class="bg-gray-100 p-8 text-center rounded-xl">
        <i class="fas fa-video text-4xl text-gray-400 mb-2"></i>
        <p>Aucun exercice n'est encore associé à vos programmes achetés.</p>
        <p class="text-sm text-gray-500 mt-2">Veuillez contacter votre coach pour qu'il ajoute des exercices à votre programme.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($exercices as $exo): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover flex flex-col">
                <div class="p-6 flex-1">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($exo['nom_exercice']); ?></h3>
                        <?php
                        $difficultyColor = [
                            'facile' => 'green',
                            'moyen' => 'orange',
                            'difficile' => 'red'
                        ][$exo['niveau_difficulte']] ?? 'gray';
                        ?>
                        <span class="px-2 py-1 bg-<?php echo $difficultyColor; ?>-100 text-<?php echo $difficultyColor; ?>-700 text-xs rounded-full whitespace-nowrap ml-2">
                            <?php echo ucfirst($exo['niveau_difficulte']); ?>
                        </span>
                    </div>
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-tag mr-1 text-purple-500"></i>
                        <?php echo htmlspecialchars($exo['categorie'] ?: 'Général'); ?>
                    </p>
                    <p class="text-gray-500 text-sm"><?php echo nl2br(htmlspecialchars($exo['description'])); ?></p>

                    <?php if (!empty($exo['video_url'])): ?>
                        <div class="mt-4">
                            <?php
                            $video = $exo['video_url'];
                            if (strpos($video, 'uploads/videos/') === 0 && file_exists('../' . $video)): ?>
                                <video controls class="w-full rounded-lg max-h-48">
                                    <source src="../<?php echo $video; ?>" type="video/mp4">
                                    Votre navigateur ne supporte pas la lecture vidéo.
                                </video>
                            <?php else:
                                $embed = getYouTubeEmbedUrl($video);
                                if ($embed): ?>
                                    <div class="relative pb-[56.25%] h-0 rounded-lg overflow-hidden">
                                        <iframe class="absolute top-0 left-0 w-full h-full" src="<?php echo $embed; ?>" frameborder="0" allowfullscreen></iframe>
                                    </div>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($video); ?>" target="_blank" class="inline-flex items-center text-purple-600 hover:text-purple-800">
                                        <i class="fas fa-external-link-alt mr-1"></i> Voir la vidéo
                                    </a>
                                <?php endif;
                            endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 text-gray-400 text-sm">
                            <i class="fas fa-video-slash mr-1"></i> Aucune vidéo disponible
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include '../include/footer.php'; ?>