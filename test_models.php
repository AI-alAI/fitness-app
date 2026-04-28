<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$apiKey = $_ENV['OPENROUTER_API_KEY'] ?? '';

$models = [
    'google/gemma-3-4b-it:free',
    'google/gemma-3-27b-it:free',
    'google/gemma-3n-e4b-it:free',
    'meta-llama/llama-4-scout:free',
    'meta-llama/llama-4-maverick:free',
    'mistralai/mistral-small-3.1-24b-instruct:free',
    'nvidia/llama-3.1-nemotron-nano-8b-v1:free',
    'nvidia/llama-3.3-nemotron-super-49b-v1:free',
    'qwen/qwen3-8b:free',
    'qwen/qwen3-14b:free',
    'qwen/qwen3-30b-a3b:free',
    'deepseek/deepseek-v3-base:free',
    'arcee-ai/arcee-blitz:free',
    'openai/gpt-oss-120b:free',
];

foreach ($models as $model) {
    $data = [
        'model'      => $model,
        'messages'   => [['role' => 'user', 'content' => 'Say hi']],
        'max_tokens' => 20
    ];
    $options = ['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey\r\nHTTP-Referer: https://smartfitness.local\r\nX-Title: SmartFitness\r\n",
        'content'       => json_encode($data),
        'ignore_errors' => true,
        'timeout'       => 15
    ]];
    $response = @file_get_contents('https://openrouter.ai/api/v1/chat/completions', false, stream_context_create($options));
    $result   = json_decode($response, true);

    $content = $result['choices'][0]['message']['content'] ?? null;
    $error   = $result['error']['message'] ?? null;

    if ($content) {
        echo "✅ $model → OK\n";
    } else {
        echo "❌ $model → " . ($error ?? 'empty') . "\n";
    }
}
?>