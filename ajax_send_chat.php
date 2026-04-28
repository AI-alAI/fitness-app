<?php
session_start();
require_once 'config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$apiKey = $_ENV['OPENROUTER_API_KEY'] ?? '';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}
if (empty($apiKey)) {
    echo json_encode(['error' => 'Clé API manquante dans .env']);
    exit;
}

$database = new Database();
$db      = $database->getConnection();
$userId  = $_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['error' => 'Message vide']);
    exit;
}

// Fetch last 8 messages for context
$stmt = $db->prepare(
    "SELECT role, message
     FROM user_chat_memory
     WHERE id_utilisateur = :id
     ORDER BY created_at DESC
     LIMIT 8"
);
$stmt->bindParam(':id', $userId);
$stmt->execute();
$history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

$systemPrompt = "Tu es un coach fitness professionnel, motivant, expert en nutrition et entraînement. Tu parles français, tutoies l'utilisateur, et réponds de manière concise (max 150 mots). Pour un programme, structure : échauffement, exercices (séries/répétitions), retour au calme.";

$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($history as $row) {
    $messages[] = [
        'role'    => ($row['role'] === 'user') ? 'user' : 'assistant',
        'content' => $row['message']
    ];
}
$messages[] = ['role' => 'user', 'content' => $message];

// ✅ Updated working free model IDs (April 2026)
$freeModels = [
    'openai/gpt-oss-120b:free',       // best quality
    'google/gemma-3-4b-it:free',      // reliable fallback
    'google/gemma-3n-e4b-it:free',    // second fallback
];

$url      = 'https://openrouter.ai/api/v1/chat/completions';
$aiReply  = null;
$lastError = 'Tous les modèles ont échoué';

foreach ($freeModels as $model) {
    $data = [
        'model'       => $model,
        'messages'    => $messages,
        'temperature' => 0.7,
        'max_tokens'  => 500
    ];

    $options = [
        'http' => [
            'method'        => 'POST',
            'header'        =>
                "Content-Type: application/json\r\n" .
                "Authorization: Bearer " . $apiKey . "\r\n" .
                "HTTP-Referer: https://smartfitness.local\r\n" .
                "X-Title: SmartFitness\r\n",
            'content'       => json_encode($data),
            'ignore_errors' => true,
            'timeout'       => 30
        ]
    ];

    $ctx      = stream_context_create($options);
    $response = @file_get_contents($url, false, $ctx);

    error_log("[SmartFitness] Trying model: $model");

    if ($response === false) {
        $lastError = 'Impossible de contacter OpenRouter';
        error_log("[SmartFitness] Connection failed for: $model");
        continue;
    }

    $result = json_decode($response, true);
    error_log("[SmartFitness] Response for $model: " . $response);

    // Skip this model if API returned an error
    if (isset($result['error'])) {
        $lastError = $result['error']['message'] ?? 'Erreur inconnue';
        $code      = $result['error']['code'] ?? '';
        error_log("[SmartFitness] Error for $model: code=$code msg=$lastError");

        // Auth error — no point trying other models
        if ($code == 401 || str_contains((string)$lastError, 'invalid') || str_contains((string)$lastError, 'auth')) {
            echo json_encode(['error' => "Clé API invalide. Régénère ta clé sur openrouter.ai/keys"]);
            exit;
        }
        continue; // try next model
    }

    $messageObj = $result['choices'][0]['message'] ?? [];

    // 1. Normal content
    $aiReply = trim($messageObj['content'] ?? '');

    // 2. Fallback: reasoning_details (reasoning models)
    if (empty($aiReply)) {
        foreach ($messageObj['reasoning_details'] ?? [] as $detail) {
            if (!empty($detail['text'])) {
                $aiReply = trim($detail['text']);
                break;
            }
        }
    }

    // 3. Fallback: reasoning field
    if (empty($aiReply) && !empty($messageObj['reasoning'])) {
        $aiReply = trim($messageObj['reasoning']);
    }

    if (!empty($aiReply)) {
        error_log("[SmartFitness] ✅ Success with model: $model");
        break; // got a reply — stop trying
    }

    $lastError = 'Réponse vide';
    error_log("[SmartFitness] Empty reply from: $model");
}

if (empty($aiReply)) {
    echo json_encode(['error' => "Aucun modèle disponible. Réessaie dans quelques minutes."]);
    exit;
}

// Save both turns to DB
$insert = $db->prepare(
    "INSERT INTO user_chat_memory (id_utilisateur, role, message, created_at)
     VALUES (:id, :role, :msg, NOW())"
);
$insert->execute([':id' => $userId, ':role' => 'user',      ':msg' => $message]);
$insert->execute([':id' => $userId, ':role' => 'assistant', ':msg' => $aiReply]);

echo json_encode(['reply' => $aiReply]);
?>