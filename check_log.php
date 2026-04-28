<?php
$logFile = ini_get('error_log');
echo "Log file: $logFile\n\n";
if ($logFile && file_exists($logFile)) {
    $lines = file($logFile);
    $last20 = array_slice($lines, -20);
    echo implode('', $last20);
} else {
    echo "Log file not found or not set.\n";
    // Try common locations
    $paths = [
        'C:/wamp64/logs/php_error.log',
        'C:/xampp/php/logs/php_error_log',
        '/var/log/php_errors.log',
        __DIR__ . '/php_errors.log',
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) {
            echo "Found at: $p\n";
            $lines = file($p);
            echo implode('', array_slice($lines, -20));
            break;
        }
    }
}
?>