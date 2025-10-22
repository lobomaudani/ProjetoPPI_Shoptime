<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "Iniciando migração de imagens...\n";

require_once 'connections/migrate_images_to_blob.php';

echo "\nMigração concluída. Você pode fechar esta página.";
echo "</pre>";