<?php
session_start();
require_once 'config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

header('Content-Type: application/json');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$apiKey = $_ENV['GROQ_API_KEY'] ?? '';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']); exit;
}
if (empty($apiKey)) {
    echo json_encode(['error' => 'Clé API manquante']); exit;
}

$database = new Database();
$db       = $database->getConnection();
$userId   = $_SESSION['user_id'];
$message  = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['error' => 'Message vide']); exit;
}

// Load last 10 messages for context
$stmt = $db->prepare(
    "SELECT role, message FROM user_chat_memory 
     WHERE id_utilisateur = :id 
     ORDER BY created_at DESC LIMIT 10"
);
$stmt->bindParam(':id', $userId);
$stmt->execute();
$history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

// Build messages for Groq
$messages = [
    [
        'role'    => 'system',
        'content' => "Tu es un coach fitness professionnel, motivant et bienveillant, expert en nutrition et entraînement sportif. Tu parles français et tutoies toujours l'utilisateur. Réponds de manière concise (maximum 150 mots). Sois chaleureux, encourage l'utilisateur et personnalise tes conseils. Pour un programme d'entraînement, structure ta réponse avec : échauffement, exercices principaux (séries/répétitions), retour au calme."
    ]
];

foreach ($history as $row) {
    $messages[] = [
        'role'    => ($row['role'] === 'user' ? 'user' : 'assistant'),
        'content' => $row['message']
    ];
}
$messages[] = ['role' => 'user', 'content' => $message];

// Call Groq API
$body = json_encode([
    'model'       => 'llama-3.3-70b-versatile',
    'messages'    => $messages,
    'max_tokens'  => 300,
    'temperature' => 0.7,
]);

$ctx = stream_context_create([
    'http' => [
        'method'        => 'POST',
        'header'        => implode("\r\n", [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]),
        'content'       => $body,
        'ignore_errors' => true,
        'timeout'       => 30,
    ]
]);

$response = @file_get_contents('https://api.groq.com/openai/v1/chat/completions', false, $ctx);
$result   = json_decode($response, true);

$aiReply = $result['choices'][0]['message']['content'] ?? '';

if (empty($aiReply)) {
    error_log('Groq error: ' . json_encode($result));
    echo json_encode(['error' => 'groq_error', 'detail' => $result['error']['message'] ?? 'Erreur inconnue']);
    exit;
}

$aiReply = trim($aiReply);

// Save to DB
$insert = $db->prepare(
    "INSERT INTO user_chat_memory (id_utilisateur, role, message) 
     VALUES (:id, :role, :msg)"
);
$insert->execute([':id' => $userId, ':role' => 'user',      ':msg' => $message]);
$insert->execute([':id' => $userId, ':role' => 'assistant', ':msg' => $aiReply]);

echo json_encode(['reply' => $aiReply]);
?>