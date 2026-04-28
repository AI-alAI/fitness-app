<?php
$paths = [
    'C:/xampp/php/logs/php_error_log',
    'C:/xampp/php/php_error_log',
    'C:/xampp/apache/logs/error.log',
    'C:/xampp/apache/logs/php_error_log',
    dirname(__FILE__) . '/php_error_log',
];

foreach ($paths as $p) {
    echo "Checking: $p → ";
    if (file_exists($p)) {
        echo "✅ FOUND\n\n";
        $lines = file($p);
        $last30 = array_slice($lines, -30);
        echo implode('', $last30);
        echo "\n---END---\n";
    } else {
        echo "❌ not found\n";
    }
}

// Also show PHP config
echo "\n\nPHP error_log setting: " . ini_get('error_log') . "\n";
echo "PHP version: " . phpversion() . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
?>