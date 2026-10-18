<?php
require_once 'config.php'; // Seu arquivo de conexão PDO

// ID do bolão que você quer alimentar
$boolao_id = 1; // exemplo, substituir pelo ID real do bolão

// Caminho do CSV
$arquivo_csv = __DIR__ . '/pombos.csv';

if (!file_exists($arquivo_csv)) {
    die("Arquivo CSV não encontrado!");
}

// Abrir o arquivo CSV
if (($handle = fopen($arquivo_csv, 'r')) !== false) {

    // Ler a primeira linha com os nomes das equipes
    $equipes = fgetcsv($handle);

    if (!$equipes) {
        die("Erro ao ler a primeira linha do CSV (nomes das equipes).");
    }

    try {
        $pdo->beginTransaction();

        // Inserir equipes no banco
        $equipe_ids = [];
        foreach ($equipes as $equipe_nome) {
            $equipe_nome = trim($equipe_nome);
            if (!empty($equipe_nome)) {
                $stmt = $pdo->prepare("INSERT INTO equipes (boolao_id, nome) VALUES (?, ?)");
                $stmt->execute([$boolao_id, $equipe_nome]);
                $equipe_ids[] = $pdo->lastInsertId();
            } else {
                $equipe_ids[] = null; // manter índice para alinhar com pombos
            }
        }

        // Ler as linhas seguintes (pombos)
        while (($linha = fgetcsv($handle)) !== false) {
            foreach ($linha as $col => $pombo_nome) {
                $pombo_nome = trim($pombo_nome);
                $equipe_id = $equipe_ids[$col] ?? null;

                if (!empty($pombo_nome) && $equipe_id) {
                    $stmt = $pdo->prepare("INSERT INTO pombos (equipe_id, nome) VALUES (?, ?)");
                    $stmt->execute([$equipe_id, $pombo_nome]);
                }
            }
        }

        $pdo->commit();
        echo "Equipes e pombos importados com sucesso!";

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Erro ao inserir no banco: " . $e->getMessage());
    }

    fclose($handle);
} else {
    die("Não foi possível abrir o arquivo CSV.");
}
?>
