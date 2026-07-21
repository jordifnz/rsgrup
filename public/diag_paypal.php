<?php
/**
 * Script de diagnóstico temporal — ELIMINAR tras verificar.
 * Acceso: https://rsgrup.openmelon.es/diag_paypal.php
 * Solo accesible desde IP local o con clave en GET.
 */
if (($_GET['k'] ?? '') !== 'rsgrup2026diag') {
    http_response_code(403); die('Acceso denegado');
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

// 1. Mostrar todas las filas de rsgrup_settings relacionadas con PayPal
echo "=== rsgrup_settings (paypal_*) ===\n";
$rows = Database::fetchAll("SELECT `key`, `value` FROM rsgrup_settings WHERE `key` LIKE 'paypal%' ORDER BY `key`");
if (!$rows) {
    echo "(sin filas paypal_* en la tabla)\n";
} else {
    foreach ($rows as $r) {
        $v = $r['value'] ?? '';
        echo $r['key'] . ' = ' . ($v === '' ? '(VACÍO)' : (strlen($v) > 8 ? substr($v,0,6).'...['.strlen($v).' chars]' : $v)) . "\n";
    }
}

// 2. Comprobar si OPcache está activo
echo "\n=== OPcache ===\n";
if (function_exists('opcache_get_status')) {
    $oc = opcache_get_status(false);
    echo 'enabled: ' . ($oc['opcache_enabled'] ? 'SI' : 'NO') . "\n";
} else {
    echo "opcache_get_status() no disponible\n";
}

// 3. Mostrar SHA del archivo PayPalService cargado en disco
echo "\n=== PayPalService en disco ===\n";
$f = dirname(__DIR__) . '/src/Services/PayPalService.php';
if (file_exists($f)) {
    echo 'SHA1: ' . sha1_file($f) . "\n";
    // Buscar la línea getSetting para confirmar qué columna usa
    $lines = file($f);
    foreach ($lines as $i => $line) {
        if (stripos($line, 'SELECT') !== false && stripos($line, 'setting') !== false) {
            echo 'Línea ' . ($i+1) . ': ' . trim($line) . "\n";
        }
        if (stripos($line, 'paypal_client_secret') !== false || stripos($line, 'paypal_secret') !== false) {
            echo 'Línea ' . ($i+1) . ': ' . trim($line) . "\n";
        }
    }
} else {
    echo "Archivo no encontrado: $f\n";
}

echo "\nDiagnóstico completado. Elimina este archivo tras revisar.\n";
