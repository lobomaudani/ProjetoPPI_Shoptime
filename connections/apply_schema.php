<?php
// connections/apply_schema.php
// Small, safe runner to apply sql/schema_v02.sql using the existing PDO connection ($conexao).
// Usage: include this file after $conexao is available, or call via browser as e.g. /connections/apply_schema.php?run=1
// Security: This file only runs when the query parameter 'run' is present and the current environment is development (localhost).

if (!isset($conexao) || !($conexao instanceof PDO)) {
    // try to include conectarBD.php to obtain $conexao
    $possible = __DIR__ . '/conectarBD.php';
    if (file_exists($possible)) {
        include_once $possible;
    }
}

if (!isset($conexao) || !($conexao instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO connection ($conexao) not available. Include this file after conectarBD.php or access it via a route that loads the connection.']);
    exit;
}

// Allow CLI runs (safe) or web runs from localhost or with a pre-shared token/file.
if (PHP_SAPI === 'cli') {
    // allow running from CLI
} else {
    $allowedHosts = ['127.0.0.1', '::1', 'localhost'];
    $remote = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Token can be provided via environment variable SCHEMA_RUN_TOKEN or file connections/.schema_token
    $tokenFile = __DIR__ . '/.schema_token';
    $envToken = getenv('SCHEMA_RUN_TOKEN') ?: null;
    $fileToken = is_readable($tokenFile) ? trim(file_get_contents($tokenFile)) : null;
    $expectedToken = $envToken ?: $fileToken;

    $ok = false;
    if (isset($_GET['run'])) {
        if ($expectedToken) {
            if (!empty($_GET['token']) && hash_equals($expectedToken, $_GET['token']))
                $ok = true;
        } elseif (in_array($remote, $allowedHosts, true)) {
            $ok = true;
        }
    }

    if (!$ok) {
        echo json_encode(['ok' => false, 'message' => 'Protected runner. To execute, call from localhost with ?run=1 or provide a valid token.']);
        exit;
    }
}

$file = __DIR__ . '/../sql/schema_v02.sql';
if (!file_exists($file)) {
    echo json_encode(['ok' => false, 'error' => 'SQL file not found: ' . $file]);
    exit;
}

$sql = file_get_contents($file);
if ($sql === false) {
    echo json_encode(['ok' => false, 'error' => 'Failed to read SQL file']);
    exit;
}

// Normalize line endings
$sql = str_replace(["\r\n", "\r"], "\n", $sql);

// Simple DELIMITER-aware splitter. It will split by 'DELIMITER' changes and collect statements.
$statements = [];
$currentDelimiter = ';';
$buffer = '';
$lines = explode("\n", $sql);
foreach ($lines as $line) {
    $trim = ltrim($line);
    if (stripos($trim, 'DELIMITER') === 0) {
        // flush buffer as statements using previous delimiter
        if (strlen(trim($buffer)) > 0) {
            $parts = explode($currentDelimiter, $buffer);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '')
                    $statements[] = $p;
            }
            $buffer = '';
        }
        $parts = preg_split('/\s+/', $trim);
        $currentDelimiter = $parts[1] ?? ';';
        continue;
    }
    $buffer .= $line . "\n";
}
// flush remaining buffer
if (strlen(trim($buffer)) > 0) {
    if ($currentDelimiter === ';') {
        $parts = explode($currentDelimiter, $buffer);
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '')
                $statements[] = $p;
        }
    } else {
        // non-standard delimiter: treat whole buffer as single statement and try to split by delimiter token
        $parts = explode($currentDelimiter, $buffer);
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '')
                $statements[] = $p;
        }
    }
}

$results = ['ok' => true, 'executed' => []];
// Execute statements in a transaction when possible
try {
    $conexao->beginTransaction();
    foreach ($statements as $stmt) {
        $trim = trim($stmt);
        if ($trim === '')
            continue;
        try {
            $conexao->exec($stmt);
            $results['executed'][] = ['sql' => (strlen($trim) > 200 ? substr($trim, 0, 200) . '...' : $trim), 'ok' => true];
        } catch (PDOException $e) {
            // collect error but continue
            $results['executed'][] = ['sql' => (strlen($trim) > 200 ? substr($trim, 0, 200) . '...' : $trim), 'ok' => false, 'error' => $e->getMessage()];
            // Do not throw: attempt to continue applying other statements
        }
    }
    $conexao->commit();
} catch (Exception $e) {
    try {
        $conexao->rollBack();
    } catch (Exception $_) {
    }
    $results['ok'] = false;
    $results['error'] = 'Transaction failed: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
