<?php
require_once __DIR__ . '/connections/conectarBD.php';

// serve_imagem.php?id=NN
if (empty($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('ID da imagem não fornecido');
}

$id = (int) $_GET['id'];

// detectar colunas reais da tabela enderecoimagem para ser tolerante a esquemas diferentes
try {
    $colsStmt = $conexao->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'enderecoimagem'");
    $colsStmt->execute();
    $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Erro ao inspecionar esquema: ' . $e->getMessage());
}

if (empty($cols)) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Tabela enderecoimagem não encontrada no banco de dados');
}

// encontrar a coluna de id (ex.: idEnderecoImagem)
$idCol = null;
foreach ($cols as $c) {
    if (preg_match('/^id/i', $c) && stripos($c, 'endereco') !== false) {
        $idCol = $c;
        break;
    }
}
if (!$idCol) {
    // fallback: procurar qualquer coluna que comece com id
    foreach ($cols as $c) {
        if (preg_match('/^id/i', $c)) {
            $idCol = $c;
            break;
        }
    }
}

if (!$idCol) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Coluna de ID não encontrada na tabela enderecoimagem');
}

// montar lista de colunas para selecionar
$selectCols = [];
if (in_array('ImagemBlob', $cols, true))
    $selectCols[] = 'ImagemBlob';
if (in_array('MimeType', $cols, true))
    $selectCols[] = 'MimeType';
if (in_array('ImagemUrl', $cols, true))
    $selectCols[] = 'ImagemUrl';

if (empty($selectCols)) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Nenhuma coluna de imagem disponível na tabela enderecoimagem');
}

$sql = 'SELECT ' . implode(', ', $selectCols) . ' FROM enderecoimagem WHERE ' . $idCol . ' = :id LIMIT 1';
try {
    $stmt = $conexao->prepare($sql);
    $stmt->execute([':id' => $id]);
    $imagem = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Erro ao buscar imagem: ' . $e->getMessage());
}

if (!$imagem) {
    header('HTTP/1.0 404 Not Found');
    exit('Imagem não encontrada');
}

// Priorizar ImagemBlob > ImagemUrl (se for blob) > ImagemUrl (se for URL)
if (!empty($imagem['ImagemBlob'])) {
    $mime = (!empty($imagem['MimeType']) ? $imagem['MimeType'] : 'image/jpeg');
    header('Content-Type: ' . $mime);
    if (function_exists('mb_strlen')) {
        header('Content-Length: ' . mb_strlen($imagem['ImagemBlob'], '8bit'));
    } else {
        header('Content-Length: ' . strlen($imagem['ImagemBlob']));
    }
    echo $imagem['ImagemBlob'];
    exit;
}

if (!empty($imagem['ImagemUrl'])) {
    // se for uma string que parece caminho público/URL, redireciona
    if (is_string($imagem['ImagemUrl'])) {
        $trim = trim($imagem['ImagemUrl']);
        if (preg_match('#^(https?://|/|uploads/)#i', $trim)) {
            header('Location: ' . $trim);
            exit;
        }
        // caso contenha dados binários em ImagemUrl (coluna usada como BLOB por schema), detectar mime e servir
        $data = $imagem['ImagemUrl'];
        if (!empty($data)) {
            // tentar detectar mime
            if (function_exists('finfo_open')) {
                $f = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_buffer($f, $data);
                finfo_close($f);
            } else {
                $mime = 'image/jpeg';
            }
            header('Content-Type: ' . ($mime ?: 'image/jpeg'));
            if (function_exists('mb_strlen')) {
                header('Content-Length: ' . mb_strlen($data, '8bit'));
            } else {
                header('Content-Length: ' . strlen($data));
            }
            echo $data;
            exit;
        }
    }
}

header('HTTP/1.0 404 Not Found');
exit('Imagem não encontrada');
