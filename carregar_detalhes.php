<?php
require_once 'config.php';

if (!isset($_GET['bolao_id'])) {
    echo "<p style='color:red;'>Bolão não informado.</p>";
    exit;
}

$bolao_id = intval($_GET['bolao_id']);

$stmt = $pdo->prepare("
    SELECT 
        a.id AS aposta_id,
        u.nome AS usuario,
        e.nome AS equipe,
        a.valor,
        a.status,
        a.created_at
    FROM apostas a
    LEFT JOIN usuarios u ON a.usuario_id = u.id
    LEFT JOIN equipes e ON a.equipe_id = e.id
    WHERE a.boolao_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$bolao_id]);
$apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$apostas) {
    echo "<p>Nenhuma aposta ativa neste bolão ainda.</p>";
    exit;
}
?>

<table border="1" cellspacing="0" cellpadding="6" width="100%" style="border-collapse:collapse;">
    <thead style="background:#f2f2f2;">
        <tr>
            <th>ID</th>
            <th>Usuário</th>
            <th>Equipe</th>
            <th>Valor</th>
            <th>Status</th>
            <th>Data</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($apostas as $a): ?>
        <tr>
            <td><?= htmlspecialchars($a['aposta_id']); ?></td>
            <td><?= htmlspecialchars($a['usuario'] ?? 'Desconhecido'); ?></td>
            <td><?= htmlspecialchars($a['equipe'] ?? '—'); ?></td>
            <td>R$ <?= number_format($a['valor'], 2, ',', '.'); ?></td>
            <td><?= ucfirst($a['status']); ?></td>
            <td><?= date('d/m/Y H:i', strtotime($a['created_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
