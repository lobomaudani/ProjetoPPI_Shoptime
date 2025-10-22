<?php
require_once 'conectarBD.php';

// Adicionar coluna BLOB se não existir
try {
    $conexao->exec("ALTER TABLE enderecoimagem ADD COLUMN ImagemBlob LONGBLOB DEFAULT NULL");
    $conexao->exec("ALTER TABLE enderecoimagem ADD COLUMN MimeType VARCHAR(255) DEFAULT NULL");
    echo "Colunas para BLOB adicionadas com sucesso.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") === false) {
        die("Erro ao adicionar colunas: " . $e->getMessage());
    }
    echo "Colunas BLOB já existem.\n";
}

// Buscar todas as imagens que ainda não são BLOB
$stmt = $conexao->query("SELECT * FROM enderecoimagem WHERE ImagemUrl LIKE 'uploads/%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Encontradas " . count($rows) . " imagens para migrar.\n";

$updateStmt = $conexao->prepare("UPDATE enderecoimagem SET ImagemBlob = :blob, MimeType = :mime WHERE idEnderecoimagem = :id");

foreach ($rows as $row) {
    $imagePath = __DIR__ . '/../' . $row['ImagemUrl'];
    if (!file_exists($imagePath)) {
        echo "Arquivo não encontrado: {$row['ImagemUrl']}\n";
        continue;
    }

    $imageData = file_get_contents($imagePath);
    if ($imageData === false) {
        echo "Erro ao ler arquivo: {$row['ImagemUrl']}\n";
        continue;
    }

    // Detectar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $imagePath);
    finfo_close($finfo);

    try {
        $updateStmt->execute([
            ':blob' => $imageData,
            ':mime' => $mimeType,
            ':id' => $row['id']
        ]);
        echo "Imagem migrada com sucesso: {$row['ImagemUrl']}\n";

        // Após migração bem-sucedida, remover arquivo físico
        unlink($imagePath);
    } catch (PDOException $e) {
        echo "Erro ao migrar imagem {$row['ImagemUrl']}: " . $e->getMessage() . "\n";
    }
}

echo "Migração concluída.\n";