<?php
// ajax_send_chat.php
session_start();
require_once 'config/database.php';
require_once __DIR__ . '/vendor/autoload.php'; // Composer autoload

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';

header('Content-Type: application/json');

if (empty($apiKey)) {
    echo json_encode(['error' => 'Clé API manquante dans .env']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$userId = $_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    echo json_encode(['error' => 'Message vide']);
    exit;
}

// --- Load last 10 messages for context ---
$stmt = $db->prepare("SELECT role, message FROM user_chat_memory WHERE id_utilisateur = :id ORDER BY created_at DESC LIMIT 10");
$stmt->bindParam(':id', $userId);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$history = array_reverse($rows); // oldest first

// --- System prompt ---
$systemPrompt = "Tu es un coach fitness professionnel, motivant, expert en nutrition et entraînement. Tu parles français, tutoies l'utilisateur. Réponds de manière concise (max 150 mots). Pour un programme, structure avec échauffement, exercices (séries/répétitions), retour au calme.";

// --- Gemini history format ---
$geminiHistory = [];
$geminiHistory[] = ['role' => 'user', 'parts' => [['text' => $systemPrompt . " Commence la conversation."]]];
$geminiHistory[] = ['role' => 'model', 'parts' => [['text' => "Bonjour ! Je suis ton coach IA. Comment puis-je t'aider ?"]]];
foreach ($history as $row) {
    $geminiHistory[] = [
        'role' => ($row['role'] == 'user' ? 'user' : 'model'),
        'parts' => [['text' => $row['message']]]
    ];
}
$geminiHistory[] = ['role' => 'user', 'parts' => [['text' => $message]]];

// --- Fallback function (used when quota exceeded) ---
function fallbackResponse($msg) {
    $msgLower = strtolower($msg);
    if (strpos($msgLower, 'perte de poids') !== false) {
        return "Pour perdre du poids, associe cardio modéré (30 min, 3x/semaine) à un déficit calorique modéré. Exemple : 10 min échauffement, 20 min fractionné, 10 min gainage.";
    } elseif (strpos($msgLower, 'muscle') !== false || strpos($msgLower, 'prise de masse') !== false) {
        return "Pour la prise de muscle, base-toi sur des exercices polyarticulaires : squats, développé couché, tractions. 3-4 séries de 8-12 répétitions, repose 60-90s.";
    } elseif (strpos($msgLower, 'endurance') !== false) {
        return "Pour l'endurance, pratique 30-45 min de cardio continu à 70% de ta fréquence cardiaque max, 3 fois par semaine. Ajoute du fractionné une fois par semaine.";
    } elseif (strpos($msgLower, 'bonjour') !== false || strpos($msgLower, 'salut') !== false) {
        return "Bonjour ! Je suis ton coach IA. Pose-moi une question sur le sport, la nutrition, ou demande un programme personnalisé.";
    } else {
        return "⚠️ Notre coach IA est très sollicité actuellement. Voici un conseil générique : Fixe-toi des objectifs réalistes, hydrate-toi bien, varie tes entraînements et consulte un médecin pour toute douleur persistante.";
    }
}

// --- Call Gemini API (with fallback on quota error) ---
$model = 'gemini-2.0-flash-lite';
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;
$data = ['contents' => $geminiHistory];
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($data),
        'ignore_errors' => true,
        'timeout' => 30
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);
$result = json_decode($response, true);

$aiReply = '';
if (isset($result['error']) && strpos($result['error']['message'] ?? '', 'Quota exceeded') !== false) {
    $aiReply = fallbackResponse($message);
} else {
    $aiReply = $result['candidates'][0]['content']['parts'][0]['text'] ?? fallbackResponse($message);
}

// --- Save conversation to database ---
$stmt = $db->prepare("INSERT INTO user_chat_memory (id_utilisateur, role, message) VALUES (:id, 'user', :msg)");
$stmt->bindParam(':id', $userId);
$stmt->bindParam(':msg', $message);
$stmt->execute();

$stmt = $db->prepare("INSERT INTO user_chat_memory (id_utilisateur, role, message) VALUES (:id, 'assistant', :msg)");
$stmt->bindParam(':id', $userId);
$stmt->bindParam(':msg', $aiReply);
$stmt->execute();

echo json_encode(['reply' => $aiReply]);
?>