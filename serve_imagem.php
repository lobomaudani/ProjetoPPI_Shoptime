<?php
require_once 'connections/conectarBD.php';

// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (empty($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('ID da imagem não fornecido');
}

$id = (int) $_GET['id'];

// Debug
error_log("Requisição de imagem ID: " . $id);

$stmt = $conexao->prepare("SELECT idEnderecoimagem, ImagemBlob, MimeType, ImagemUrl FROM enderecoimagem WHERE idEnderecoimagem = :id");
$stmt->execute([':id' => $id]);
$imagem = $stmt->fetch(PDO::FETCH_ASSOC);

// Debug
error_log("Resultado da query: " . print_r($imagem, true));

if (!$imagem) {
    error_log("Imagem não encontrada para ID: " . $id);
    header('HTTP/1.0 404 Not Found');
    exit('Imagem não encontrada');
}

// Se tiver BLOB, serve ele
if (!empty($imagem['ImagemBlob'])) {
    error_log("Servindo BLOB para imagem ID: " . $id);
    header('Content-Type: ' . ($imagem['MimeType'] ?? 'image/jpeg'));
    header('Content-Length: ' . strlen($imagem['ImagemBlob']));
    echo $imagem['ImagemBlob'];
    exit;
}

// Se não tiver BLOB mas tiver URL
if (!empty($imagem['ImagemUrl'])) {
    error_log("Redirecionando para URL: " . $imagem['ImagemUrl']);
    // Se o caminho começa com 'uploads/', ajustar para o caminho correto
    if (strpos($imagem['ImagemUrl'], 'uploads/') === 0) {
        $path = __DIR__ . '/' . $imagem['ImagemUrl'];
        if (file_exists($path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $path);
            finfo_close($finfo);
            header('Content-Type: ' . $mimeType);
            readfile($path);
            exit;
        }
    }
    header('Location: ' . $imagem['ImagemUrl']);
    exit;
}

error_log("Nem BLOB nem URL encontrados para imagem ID: " . $id);
header('HTTP/1.0 404 Not Found');
exit('Imagem não encontrada');