<?php
echo "PHP Version: " . phpversion() . "\n";
echo "opcache.enable: " . (ini_get('opcache.enable') ? 'ON' : 'OFF') . "\n";
echo "opcache.file_cache: " . (ini_get('opcache.file_cache') ?? 'disabled') . "\n";

if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    if ($status) {
        echo "Opcache enabled: YES\n";
        echo "Memory used: " . ($status['memory_usage']['used_memory'] ?? 'N/A') . "\n";
        echo "Interned strings: " . ($status['interned_strings_usage']['used_memory'] ?? 'N/A') . "\n";
    } else {
        echo "Opcache enabled: but no status (might be disabled)\n";
    }
} else {
    echo "opcache_get_status() not available\n";
}

// Also verify this file is from the right location
echo "\nLoaded PHP config: " . php_ini_loaded_file() . "\n";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
