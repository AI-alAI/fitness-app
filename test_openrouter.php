<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$apiKey = $_ENV['OPENROUTER_API_KEY'] ?? '';

echo "API Key loaded: " . (empty($apiKey) ? "❌ NOT FOUND" : "✅ " . substr($apiKey, 0, 20) . "...") . "\n";

$data = [
    'model'    => 'openrouter/free',
    'messages' => [
        ['role' => 'user', 'content' => 'Say hello in one word']
    ],
    'max_tokens' => 50
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
$response = @file_get_contents('https://openrouter.ai/api/v1/chat/completions', false, $ctx);

echo "Raw response:\n";
echo $response . "\n";
?>