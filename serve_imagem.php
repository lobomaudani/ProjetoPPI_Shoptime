<?php
require_once __DIR__ . '/connections/conectarBD.php';

// Habilitar exibição de erros para debug (temporário)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// serve_imagem.php?id=NN  OR serve_imagem.php?pid=PRODUCT_ID
if (empty($_GET['id']) && empty($_GET['pid'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('ID da imagem/produto não fornecido');
}

$id = null;
$pid = null;
if (!empty($_GET['id'])) $id = (int) $_GET['id'];
if (!empty($_GET['pid'])) $pid = (int) $_GET['pid'];

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

// se veio pid (produto), buscar a primeira imagem desse produto
if ($pid) {
    $sql = 'SELECT ' . implode(', ', $selectCols) . ' FROM enderecoimagem WHERE Produtos_idProdutos = :pid ORDER BY idEnderecoImagem ASC LIMIT 1';
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->execute([':pid' => $pid]);
        $imagem = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        header('HTTP/1.0 500 Internal Server Error');
        exit('Erro ao buscar imagem por produto: ' . $e->getMessage());
    }
} else {
    $sql = 'SELECT ' . implode(', ', $selectCols) . ' FROM enderecoimagem WHERE ' . $idCol . ' = :id LIMIT 1';
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->execute([':id' => $id]);
        $imagem = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        header('HTTP/1.0 500 Internal Server Error');
        exit('Erro ao buscar imagem: ' . $e->getMessage());
    }
}

if (!$imagem) {
    header('HTTP/1.0 404 Not Found');
    exit('Imagem não encontrada');
}

// modo debug: retorna metadados seguros do registro como JSON para facilitar inspeção
if (!empty($_GET['debug'])) {
    $dbg = [];
    foreach ($imagem as $k => $v) {
        if (is_null($v)) { $dbg[$k] = null; continue; }
        if (is_string($v)) {
            $len = strlen($v);
            $preview = substr($v, 0, 200);
            // se parecer binário, mostre apenas tamanho e prefixo em hex
            $isBinary = preg_match('/[\x00-\x08\x0E-\x1F]/', $v);
            if ($isBinary) {
                $previewHex = strtoupper(substr(bin2hex(substr($v,0,16)),0,64));
                $dbg[$k] = ['type' => 'binary', 'length' => $len, 'hex_prefix' => $previewHex];
            } else {
                $dbg[$k] = ['type' => 'string', 'length' => $len, 'preview' => $preview];
            }
            continue;
        }
        if (is_resource($v)) {
            $dbg[$k] = ['type' => 'resource'];
            continue;
        }
        $dbg[$k] = ['type' => gettype($v)];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dbg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Priorizar ImagemBlob > ImagemUrl (se for blob) > ImagemUrl (se for URL)
if (!empty($imagem['ImagemBlob'])) {
    $mime = (!empty($imagem['MimeType']) ? $imagem['MimeType'] : 'image/jpeg');
    // limpar buffers e enviar binário
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=86400');
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . strlen($imagem['ImagemBlob']));
    echo $imagem['ImagemBlob'];
    exit;
}

        if (!empty($imagem['ImagemUrl'])) {
            $trim = trim($imagem['ImagemUrl']);
            // enviar header de debug com a origem da imagem
            header('X-Image-Source: ' . substr($trim, 0, 200));
        // se for data-uri
        if (strpos($trim, 'data:') === 0) {
            if (preg_match('#^data:(.*?);base64,(.*)$#', $trim, $m)) {
                header('Content-Type: ' . $m[1]);
                echo base64_decode($m[2]);
                exit;
            }
        }

        // se for URL absoluto, redireciona
        if (preg_match('#^https?://#i', $trim)) {
            header('Location: ' . $trim);
            exit;
        }

        // se for caminho relativo dentro do projeto, servir arquivo diretamente
        $filePath = __DIR__ . '/' . ltrim($trim, '/');
        if (file_exists($filePath) && is_file($filePath)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($filePath) ?: 'application/octet-stream';
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }

        // se não existir arquivo local, tentar tratar como binário armazenado na coluna
        $data = $imagem['ImagemUrl'];
        if (!empty($data)) {
            if (function_exists('finfo_open')) {
                $f = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_buffer($f, $data);
                finfo_close($f);
            } else {
                $mime = 'image/jpeg';
            }
            if (ob_get_level()) ob_end_clean();
            header('Content-Type: ' . ($mime ?: 'image/jpeg'));
            header('Cache-Control: public, max-age=86400');
            header('Accept-Ranges: bytes');
            header('Content-Length: ' . strlen($data));
            echo $data;
            exit;
        }
    }

header('HTTP/1.0 404 Not Found');
exit('Imagem não encontrada');
