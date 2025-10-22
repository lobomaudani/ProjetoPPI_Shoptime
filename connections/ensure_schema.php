<?php
// Este arquivo garante esquema e triggers essenciais ao iniciar a conexão.
// Executá-lo é seguro: usa IF NOT EXISTS e captura erros para não interromper a aplicação.

try {
    // garantir tabela favoritos
    $conexao->exec("CREATE TABLE IF NOT EXISTS favoritos (
        Usuarios_idUsuarios INT NOT NULL,
        Produtos_idProdutos INT NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (Usuarios_idUsuarios, Produtos_idProdutos),
        INDEX fk_Fav_Usuarios (Usuarios_idUsuarios),
        INDEX fk_Fav_Produtos (Produtos_idProdutos)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // ignora erros de criação de tabela
}

try {
    // adicionar coluna FavoritosCount em produtos (MySQL 8+ permite IF NOT EXISTS)
    $conexao->exec("ALTER TABLE produtos ADD COLUMN IF NOT EXISTS FavoritosCount INT NOT NULL DEFAULT 0");
} catch (Exception $e) {
    // ignora
}

try {
    // criar triggers para favoritos: usar information_schema para checar existência
    $chk = $conexao->prepare("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = :name");
    $chk->execute([':name' => 'trg_favoritos_after_insert']);
    if (!$chk->fetchColumn()) {
        // O MySQL precisa de DELIMITER quando executado no cliente, mas via exec() funciona com um único statement
        $conexao->exec("CREATE TRIGGER trg_favoritos_after_insert AFTER INSERT ON favoritos FOR EACH ROW BEGIN UPDATE produtos SET FavoritosCount = FavoritosCount + 1 WHERE idProdutos = NEW.Produtos_idProdutos; END;");
    }
    $chk->execute([':name' => 'trg_favoritos_after_delete']);
    if (!$chk->fetchColumn()) {
        $conexao->exec("CREATE TRIGGER trg_favoritos_after_delete AFTER DELETE ON favoritos FOR EACH ROW BEGIN UPDATE produtos SET FavoritosCount = GREATEST(0, FavoritosCount - 1) WHERE idProdutos = OLD.Produtos_idProdutos; END;");
    }
} catch (Exception $e) {
    // ignora
}

try {
    // Trigger exemplo: ao inserir itenscompras reduz o estoque automaticamente
    // primeiro checar se trigger existe
    $chk->execute([':name' => 'trg_itenscompras_after_insert']);
    if (!$chk->fetchColumn()) {
        $conexao->exec("CREATE TRIGGER trg_itenscompras_after_insert AFTER INSERT ON itenscompras FOR EACH ROW BEGIN UPDATE produtos SET Quantidade = IFNULL(Quantidade,0) - NEW.QuantidadeComprada WHERE idProdutos = NEW.Produtos_idProdutos; END;");
    }
    $chk->execute([':name' => 'trg_itenscompras_after_delete']);
    if (!$chk->fetchColumn()) {
        $conexao->exec("CREATE TRIGGER trg_itenscompras_after_delete AFTER DELETE ON itenscompras FOR EACH ROW BEGIN UPDATE produtos SET Quantidade = IFNULL(Quantidade,0) + OLD.QuantidadeComprada WHERE idProdutos = OLD.Produtos_idProdutos; END;");
    }
} catch (Exception $e) {
    // ignora
}

// Exemplo: criar índice composto em produtos para buscas frequentes (nome + categoria)
try {
    $conexao->exec("ALTER TABLE produtos ADD INDEX IF NOT EXISTS idx_nome_categoria (Nome(100), Categorias_idCategorias)");
} catch (Exception $e) {
}

// Fim do ensure_schema
