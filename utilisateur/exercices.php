<?php
// utilisateur/exercices.php - Black & White Premium Design
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

<!-- Monochrome font & styles -->
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at 20% 30%, #1a1a1a 0%, #0a0a0a 100%);
        background-attachment: fixed;
        color: #e5e5e5;
    }
    /* Glass card – monochrome */
    .glass-card {
        background: rgba(30, 30, 35, 0.65);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 2rem;
        transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255,255,255,0.03);
    }
    .glass-card:hover {
        transform: translateY(-8px) scale(1.01);
        border-color: rgba(255, 255, 255, 0.25);
        box-shadow: 0 30px 45px -15px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.1);
        background: rgba(40, 40, 45, 0.75);
    }
    /* Difficulty badges – monochrome variants */
    .badge-easy {
        background: rgba(200, 200, 200, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #dddddd;
    }
    .badge-medium {
        background: rgba(160, 160, 160, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.4);
        color: #f0f0f0;
        text-shadow: 0 0 4px rgba(255,255,255,0.2);
    }
    .badge-hard {
        background: rgba(100, 100, 100, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.5);
        color: #ffffff;
        text-shadow: 0 0 4px rgba(255,255,255,0.3);
    }
    /* Video container */
    .video-wrapper {
        border-radius: 1rem;
        overflow: hidden;
        background: #111;
        box-shadow: 0 10px 20px -5px rgba(0,0,0,0.7);
        transition: transform 0.2s ease;
    }
    video, iframe {
        width: 100%;
        display: block;
    }
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
    }
    ::-webkit-scrollbar-track {
        background: #1a1a1a;
    }
    ::-webkit-scrollbar-thumb {
        background: #555;
        border-radius: 10px;
    }
    /* Hero gradient in grayscale */
    .hero-gradient {
        background: linear-gradient(135deg, #cccccc 0%, #ffffff 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    /* Button (monochrome) */
    .btn-monochrome {
        background: linear-gradient(135deg, #2c2c2c 0%, #1a1a1a 100%);
        border: 1px solid rgba(255,255,255,0.15);
        transition: all 0.2s;
    }
    .btn-monochrome:hover {
        background: linear-gradient(135deg, #3a3a3a 0%, #262626 100%);
        box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        transform: scale(1.02);
    }
</style>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Hero -->
    <div class="relative mb-16 text-center">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-64 h-64 bg-white rounded-full blur-[100px] opacity-10 -z-10"></div>
        <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight">
            <span class="hero-gradient">Mes exercices</span>
        </h1>
        <p class="text-gray-300 text-lg md:text-xl mt-4 max-w-2xl mx-auto opacity-90">
            Vidéos débloquées pour vos programmes
        </p>
        <div class="w-24 h-1 bg-gradient-to-r from-gray-400 to-white rounded-full mx-auto mt-6"></div>
    </div>

    <?php if (empty($programmes_achetes)): ?>
        <div class="glass-card p-8 text-center max-w-2xl mx-auto">
            <div class="text-7xl mb-4">🏋️</div>
            <h3 class="text-2xl font-semibold text-white">Aucun programme acheté</h3>
            <p class="text-gray-300 mt-2">Découvrez nos formations pour accéder aux exercices.</p>
            <a href="programmes_disponibles.php" class="inline-flex items-center gap-2 mt-6 px-6 py-3 btn-monochrome text-white rounded-full font-semibold transition-all">Découvrir →</a>
        </div>
    <?php elseif (count($exercices) == 0): ?>
        <div class="glass-card p-8 text-center max-w-2xl mx-auto">
            <div class="text-7xl mb-4">📹</div>
            <h3 class="text-2xl font-semibold text-white">Aucun exercice associé</h3>
            <p class="text-gray-300 mt-2">Votre coach n'a pas encore ajouté d'exercices.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($exercices as $exo): 
                $difficulty = $exo['niveau_difficulte'];
                $badgeClass = '';
                if ($difficulty == 'facile') $badgeClass = 'badge-easy';
                elseif ($difficulty == 'moyen') $badgeClass = 'badge-medium';
                else $badgeClass = 'badge-hard';
            ?>
                <div class="glass-card tilt-card overflow-hidden flex flex-col h-full">
                    <div class="p-5 pb-2">
                        <div class="flex justify-between items-start gap-2">
                            <h2 class="text-2xl font-bold text-white tracking-tight"><?php echo htmlspecialchars($exo['nom_exercice']); ?></h2>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?php echo $badgeClass; ?>">
                                <?php echo ucfirst($difficulty); ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-2 mt-2 text-gray-400 text-sm">
                            <i class="fas fa-tag"></i>
                            <span><?php echo htmlspecialchars($exo['categorie'] ?: 'Général'); ?></span>
                        </div>
                        <p class="text-gray-300 text-sm mt-3 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($exo['description'])); ?>
                        </p>
                    </div>

                    <?php if (!empty($exo['video_url'])): ?>
                        <div class="mt-3 px-5 pb-5">
                            <div class="video-wrapper">
                                <?php
                                $video = $exo['video_url'];
                                if (strpos($video, 'uploads/videos/') === 0 && file_exists('../' . $video)): ?>
                                    <video controls>
                                        <source src="../<?php echo $video; ?>" type="video/mp4">
                                    </video>
                                <?php else:
                                    $embed = getYouTubeEmbedUrl($video);
                                    if ($embed): ?>
                                        <div class="relative pt-[56.25%]">
                                            <iframe class="absolute inset-0 w-full h-full" src="<?php echo $embed; ?>" frameborder="0" allowfullscreen></iframe>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($video); ?>" target="_blank" class="flex items-center justify-center gap-2 text-gray-300 bg-gray-800/50 p-3 rounded-xl text-sm font-medium hover:bg-gray-700/50 transition">
                                            <i class="fas fa-external-link-alt"></i> Voir la vidéo
                                        </a>
                                    <?php endif;
                                endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-3 px-5 pb-5">
                            <div class="text-center text-gray-500 bg-gray-800/20 rounded-xl py-6 text-sm">
                                <i class="fas fa-video-slash text-2xl mb-1 block"></i>
                                Aucune vidéo
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/vanilla-tilt@1.8.0/dist/vanilla-tilt.min.js"></script>
<script>
    VanillaTilt.init(document.querySelectorAll('.glass-card'), {
        max: 3,
        speed: 400,
        glare: true,
        "max-glare": 0.15,
        gyroscope: false,
    });
    if (window.innerWidth < 768) {
        VanillaTilt.init(document.querySelectorAll('.glass-card'), { disable: true });
    }
</script>

<?php include '../include/footer.php'; ?>