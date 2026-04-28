<?php
// ajax_send_chat.php – OpenRouter avec modèle fiable
session_start();
require_once 'config/database.php';

$apiKey = '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';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}
if (empty($apiKey)) {
    echo json_encode(['error' => 'Clé API OpenRouter manquante']);
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

// Récupérer les 8 derniers messages
$stmt = $db->prepare(
    "SELECT role, message FROM user_chat_memory 
     WHERE id_utilisateur = :id 
     ORDER BY created_at DESC LIMIT 8"
);
$stmt->bindParam(':id', $userId);
$stmt->execute();
$historyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$history = array_reverse($historyRows);

$systemPrompt = "Tu es un coach fitness professionnel, motivant, expert en nutrition et entraînement. Tu parles français, tutoies l'utilisateur, et réponds de manière concise (max 150 mots). Pour un programme, structure : échauffement, exercices (séries/répétitions), retour au calme.";

$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($history as $row) {
    $role = ($row['role'] === 'user') ? 'user' : 'assistant';
    $messages[] = ['role' => $role, 'content' => $row['message']];
}
$messages[] = ['role' => 'user', 'content' => $message];

// Modèle fiable et gratuit sur OpenRouter (vérifié)
$model = 'meta-llama/llama-3.2-3b-instruct:free';

$url = 'https://openrouter.ai/api/v1/chat/completions';
$data = [
    'model' => $model,
    'messages' => $messages,
    'temperature' => 0.7,
    'max_tokens' => 500
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n" .
                    "Authorization: Bearer " . $apiKey . "\r\n",
        'content' => json_encode($data),
        'ignore_errors' => true,
        'timeout' => 30
    ]
];

$ctx = stream_context_create($options);
$response = @file_get_contents($url, false, $ctx);
$result = json_decode($response, true);

if (isset($result['error'])) {
    $errorMsg = $result['error']['message'] ?? 'Erreur inconnue';
    error_log("OpenRouter error: " . $errorMsg);
    echo json_encode(['error' => 'Erreur API : ' . $errorMsg]);
    exit;
}
if (empty($result['choices'][0]['message']['content'])) {
    echo json_encode(['error' => 'Réponse vide de l’API']);
    exit;
}

$aiReply = trim($result['choices'][0]['message']['content']);

// Sauvegarde en base
$insert = $db->prepare(
    "INSERT INTO user_chat_memory (id_utilisateur, role, message, created_at)
     VALUES (:id, :role, :msg, NOW())"
);
$insert->execute([':id' => $userId, ':role' => 'user', ':msg' => $message]);
$insert->execute([':id' => $userId, ':role' => 'assistant', ':msg' => $aiReply]);

echo json_encode(['reply' => $aiReply]);
?>