<?php
require_once 'config.php';

// VERIFICAÇÃO MANUAL DE LOGIN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
    
    // Verificação rápida dos dados
$check_apostas = $pdo->query("SELECT COUNT(*) as total FROM apostas WHERE status = 'aprovada'")->fetch();
$check_boloes = $pdo->query("SELECT COUNT(*) as total FROM boloes")->fetch();

error_log("=== VERIFICAÇÃO INICIAL ===");
error_log("Total apostas aprovadas: " . $check_apostas['total']);
error_log("Total bolões: " . $check_boloes['total']);
    
}

// Filtros
$filtro_status = $_GET['status'] ?? 'todos';
$bolao_id = $_GET['bolao_id'] ?? null;

// Buscar bolões para filtro
$stmt_boloes = $pdo->query("SELECT id, titulo, status FROM boloes ORDER BY created_at DESC");
$boloes = $stmt_boloes->fetchAll(PDO::FETCH_ASSOC);

// Buscar apostas com filtros - CONSULTA CORRIGIDA
$sql = "
    SELECT 
        a.*,
        b.titulo as bolao_titulo,
        b.tipo as bolao_tipo,
        b.data_fim,
        b.status as bolao_status,
        u.nome as usuario_nome,
        u.email as usuario_email,
        e.nome as equipe_nome
    FROM apostas a
    INNER JOIN boloes b ON a.boolao_id = b.id
    INNER JOIN usuarios u ON a.usuario_id = u.id
    LEFT JOIN equipes e ON a.equipe_id = e.id
    WHERE 1=1
";

$params = [];

// Aplicar filtro de status
if ($filtro_status !== 'todos') {
    $sql .= " AND b.status = ?";
    $params[] = $filtro_status;
}

// Aplicar filtro de bolão específico
if ($bolao_id && is_numeric($bolao_id)) {
    $sql .= " AND a.boolao_id = ?";
    $params[] = $bolao_id;
}

$sql .= " ORDER BY a.created_at DESC";

error_log("=== DEBUG GERENCIAR_APOSTAS ===");
error_log("Filtro Status: " . $filtro_status);
error_log("Bolão ID: " . $bolao_id);
error_log("SQL: " . $sql);
error_log("Params: " . implode(', ', $params));
error_log("================================");

$stmt_apostas = $pdo->prepare($sql);
$stmt_apostas->execute($params);
$apostas = $stmt_apostas->fetchAll(PDO::FETCH_ASSOC);

// DEBUG COMPLETO: Verificar TODOS os campos das apostas
error_log("=== ESTRUTURA COMPLETA DAS APOSTAS ===");
if (count($apostas) > 0) {
    $primeira_aposta = $apostas[0];
    error_log("CAMPOS DISPONÍVEIS: " . implode(', ', array_keys($primeira_aposta)));
    
    foreach($apostas as $index => $aposta) {
        if ($index < 3) { // Mostrar apenas as 3 primeiras
            error_log("Aposta ID: " . $aposta['id'] . 
                     " | Bolão: " . $aposta['bolao_titulo'] . 
                     " | Valor: " . $aposta['valor'] .
                     " | Equipe ID: " . ($aposta['equipe_id'] ?? 'NULL') .
                     " | Pombo ID: " . ($aposta['pombo_id'] ?? 'NULL') .
                     " | Status: " . $aposta['status']);
        }
    }
} else {
    error_log("Nenhuma aposta encontrada");
}
error_log("=====================================");

// Calcular estatísticas dos bolões - CONSULTA CORRIGIDA
$sql_estatisticas = "
    SELECT 
        b.id,
        b.titulo,
        b.tipo,
        b.data_fim,
        b.status,
        COUNT(a.id) as total_apostas,
        COALESCE(SUM(a.valor), 0) as valor_acumulado
    FROM boloes b
    LEFT JOIN apostas a ON b.id = a.boolao_id AND a.status = 'aprovada'
    GROUP BY b.id
    ORDER BY b.created_at DESC
";

$stmt_estatisticas = $pdo->query($sql_estatisticas);
$boloes_com_estatisticas = $stmt_estatisticas->fetchAll(PDO::FETCH_ASSOC);

// Buscar detalhes de um bolão específico se solicitado
$bolao_detalhes = null;
$apostas_bolao = [];
if ($bolao_id && is_numeric($bolao_id)) {
    // Detalhes do bolão
    $stmt_bolao = $pdo->prepare("SELECT * FROM boloes WHERE id = ?");
    $stmt_bolao->execute([$bolao_id]);
    $bolao_detalhes = $stmt_bolao->fetch(PDO::FETCH_ASSOC);
    
    
// CONSULTA CORRIGIDA: Apostas agrupadas por usuário para este bolão
$sql_apostas_bolao = "
    SELECT 
        u.id as usuario_id,
        u.nome as usuario_nome,
        u.email as usuario_email,
        SUM(a.valor) as total_apostado,
        COUNT(a.id) as total_apostas,
        GROUP_CONCAT(DISTINCT COALESCE(e.nome, 'N/A') SEPARATOR ', ') as equipes_apostadas
    FROM apostas a
    INNER JOIN usuarios u ON a.usuario_id = u.id
    LEFT JOIN equipes e ON a.equipe_id = e.id
    WHERE a.boolao_id = ? AND a.status = 'aprovada'
    GROUP BY u.id, u.nome, u.email
    ORDER BY total_apostado DESC
";
    
    $stmt_apostas_bolao = $pdo->prepare($sql_apostas_bolao);
    $stmt_apostas_bolao->execute([$bolao_id]);
    $apostas_bolao = $stmt_apostas_bolao->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estatísticas totais para este bolão
    $sql_total_bolao = "
        SELECT 
            COUNT(DISTINCT a.usuario_id) as total_usuarios,
            COUNT(a.id) as total_apostas,
            COALESCE(SUM(a.valor), 0) as valor_total
        FROM apostas a 
        WHERE a.boolao_id = ? AND a.status = 'aprovada'
    ";
    
    $stmt_total_bolao = $pdo->prepare($sql_total_bolao);
    $stmt_total_bolao->execute([$bolao_id]);
    $estatisticas_bolao = $stmt_total_bolao->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Apostas - BetMax</title>
    <style>
        /* Mantenha todo o CSS anterior igual */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary-bg: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            --card-bg: rgba(255, 255, 255, 0.95);
            --accent: #ff6b00;
            --accent-hover: #ff3c00;
            --text-dark: #333;
            --text-light: #666;
            --success: #4CAF50;
            --warning: #FF9800;
            --danger: #f44336;
            --info: #2196F3;
        }
        
        body {
            background: var(--primary-bg);
            min-height: 100vh;
            padding: 20px;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            min-height: 90vh;
        }
        
        /* Header */
        .admin-header {
            background: linear-gradient(135deg, #2d3748, #4a5568);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--accent);
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: var(--accent);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }
        
        /* Navigation */
        .admin-nav {
            background: #f8f9fa;
            padding: 15px 40px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .nav-menu {
            display: flex;
            gap: 20px;
            list-style: none;
        }
        
        .nav-menu a {
            color: var(--text-dark);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .nav-menu a:hover,
        .nav-menu a.active {
            background: var(--accent);
            color: white;
        }
        
        /* Main Content */
        .admin-main {
            padding: 40px;
        }
        
        .page-title {
            color: var(--text-dark);
            font-size: 2.5em;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .page-subtitle {
            color: var(--text-light);
            font-size: 1.2em;
            margin-bottom: 40px;
            text-align: center;
        }
        
        /* Filtros */
        .filtros-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filtro-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filtro-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.2);
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--accent-hover);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        /* Cards de Bolões */
        .boloes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .bolao-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
            cursor: pointer;
        }
        
        .bolao-card:hover {
            transform: translateY(-5px);
        }
        
        .bolao-header {
            background: linear-gradient(135deg, #2d3748, #4a5568);
            color: white;
            padding: 20px;
        }
        
        .bolao-title {
            font-size: 1.4em;
            margin-bottom: 10px;
        }
        
        .bolao-tipo {
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        .bolao-body {
            padding: 20px;
        }
        
        .bolao-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .bolao-info-label {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .bolao-info-value {
            font-weight: bold;
        }
        
        .valor-acumulado {
            color: var(--success);
            font-size: 1.2em;
        }
        
        .total-apostas {
            color: var(--info);
        }
        
        .tempo-restante {
            color: var(--warning);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-ativo {
            background: var(--success);
            color: white;
        }
        
        .status-finalizado {
            background: var(--danger);
            color: white;
        }
        
        /* Tabela de Apostas */
        .tabela-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .tabela-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .tabela-title {
            font-size: 1.5em;
            color: var(--text-dark);
            margin: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .badge-success {
            background: var(--success);
            color: white;
        }
        
        .badge-warning {
            background: var(--warning);
            color: white;
        }
        
        .badge-danger {
            background: var(--danger);
            color: white;
        }
        
        /* Detalhes do Bolão */
        .detalhes-bolao {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .detalhes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .voltar-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }
        
        .voltar-btn:hover {
            background: #545b62;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: var(--accent);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.9em;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .admin-main {
                padding: 20px;
            }
            
            .boloes-grid {
                grid-template-columns: 1fr;
            }
            
            .filtros-container {
                flex-direction: column;
            }
            
            .filtro-group {
                min-width: 100%;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .nav-menu a {
                padding: 8px 15px;
                font-size: 0.9em;
            }
            
            table {
                font-size: 0.9em;
            }
            
            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <div class="logo">BETMAX ADMIN</div>
            <div class="user-info">
                <div class="user-avatar"><?php echo substr($_SESSION['user_name'] ?? 'AD', 0, 2); ?></div>
                <div>
                    <div><?php echo $_SESSION['user_name'] ?? 'Administrador'; ?></div>
                    <div style="font-size: 0.9em; opacity: 0.8;">Administrador</div>
                </div>
            </div>
        </header>
        
        <!-- Navigation -->
        <nav class="admin-nav">
            <ul class="nav-menu">
                <li><a href="admin.php">Dashboard</a></li>
                <li><a href="gerenciar_usuarios.php">Usuários</a></li>
                <li><a href="gerenciar_boloes.php">Bolões</a></li>
                <li><a href="gerenciar_apostas.php" class="active">Apostas</a></li>
                <li><a href="relatorios.php">Relatórios</a></li>
                <li><a href="configuracoes.php">Configurações</a></li>
                <li><a href="logout.php" style="color: var(--danger);">Sair</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="admin-main">
            <h1 class="page-title">Gerenciar Apostas</h1>
            <p class="page-subtitle">Controle completo das apostas dos usuários</p>
            
            <!-- Filtros -->
            <div class="filtros-container">
                <div class="filtro-group">
                    <label for="filtro_status">Status do Bolão:</label>
                    <select id="filtro_status" name="status" class="form-control" onchange="atualizarFiltros()">
                        <option value="todos" <?php echo $filtro_status === 'todos' ? 'selected' : ''; ?>>Todos os Status</option>
                        <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                        <option value="finalizado" <?php echo $filtro_status === 'finalizado' ? 'selected' : ''; ?>>Finalizados</option>
                    </select>
                </div>
                
                <div class="filtro-group">
                    <label for="filtro_bolao">Bolão Específico:</label>
                    <select id="filtro_bolao" name="bolao_id" class="form-control" onchange="atualizarFiltros()">
                        <option value="">Todos os Bolões</option>
                        <?php foreach ($boloes as $bolao): ?>
                            <option value="<?php echo $bolao['id']; ?>" 
                                <?php echo $bolao_id == $bolao['id'] ? 'selected' : ''; ?>>
                                <?php echo $bolao['titulo']; ?> (<?php echo ucfirst($bolao['status']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filtro-group">
                    <button type="button" class="btn btn-secondary" onclick="limparFiltros()">
                        Limpar Filtros
                    </button>
                </div>
            </div>

            <?php if ($bolao_detalhes): ?>
                <!-- Detalhes do Bolão Específico -->
                <div class="detalhes-bolao">
                    <div class="detalhes-header">
                        <h2 class="tabela-title">Detalhes do Bolão: <?php echo $bolao_detalhes['titulo']; ?></h2>
                        <a href="?" class="voltar-btn">← Voltar para Todos os Bolões</a>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value">R$ <?php echo number_format($estatisticas_bolao['valor_total'] ?? 0, 2, ',', '.'); ?></div>
                            <div class="stat-label">Valor Total Apostado</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $estatisticas_bolao['total_usuarios'] ?? 0; ?></div>
                            <div class="stat-label">Usuários Participantes</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $estatisticas_bolao['total_apostas'] ?? 0; ?></div>
                            <div class="stat-label">Total de Apostas</div>
                        </div>
                    </div>

                    <div class="tabela-container">
                        <div class="tabela-header">
                            <h3 class="tabela-title">Apostas por Usuário</h3>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Email</th>
                                    <th>Total Apostado</th>
                                    <th>Nº de Apostas</th>
                                    <th>Equipes/Pombos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($apostas_bolao) > 0): ?>
                                    <?php foreach ($apostas_bolao as $aposta_user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($aposta_user['usuario_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($aposta_user['usuario_email']); ?></td>
                                            <td><strong>R$ <?php echo number_format($aposta_user['total_apostado'], 2, ',', '.'); ?></strong></td>
                                            <td><?php echo $aposta_user['total_apostas']; ?></td>
                                            <td><?php echo htmlspecialchars($aposta_user['equipes_apostadas'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 40px;">
                                            Nenhuma aposta encontrada para este bolão.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <!-- Lista de Todos os Bolões -->
                <div class="boloes-grid">
                    <?php foreach ($boloes_com_estatisticas as $bolao): ?>
                        <?php
                        // Calcular tempo restante
                        $data_fim = new DateTime($bolao['data_fim']);
                        $agora = new DateTime();
                        
                        if ($bolao['status'] === 'ativo' && $data_fim > $agora) {
                            $diferenca = $agora->diff($data_fim);
                            $tempo_restante = $diferenca->days . ' dias';
                        } else {
                            $tempo_restante = 'Finalizado';
                        }
                        ?>
                        
                        <div class="bolao-card" onclick="verBolao(<?php echo $bolao['id']; ?>)">
                            <div class="bolao-header">
                                <h3 class="bolao-title"><?php echo htmlspecialchars($bolao['titulo']); ?></h3>
                                <div class="bolao-tipo"><?php echo ucfirst(str_replace('_', ' ', $bolao['tipo'])); ?></div>
                            </div>
                            <div class="bolao-body">
                                <div class="bolao-info">
                                    <span class="bolao-info-label">Prêmio Acumulado:</span>
                                    <span class="bolao-info-value valor-acumulado">
                                        R$ <?php echo number_format($bolao['valor_acumulado'], 2, ',', '.'); ?>
                                    </span>
                                </div>
                                <div class="bolao-info">
                                    <span class="bolao-info-label">Total de Apostas:</span>
                                    <span class="bolao-info-value total-apostas">
                                        <?php echo $bolao['total_apostas']; ?>
                                    </span>
                                </div>
                                <div class="bolao-info">
                                    <span class="bolao-info-label">Status:</span>
                                    <span class="status-badge status-<?php echo $bolao['status']; ?>">
                                        <?php echo ucfirst($bolao['status']); ?>
                                    </span>
                                </div>
                                <div class="bolao-info">
                                    <span class="bolao-info-label">Termina em:</span>
                                    <span class="bolao-info-value tempo-restante">
                                        <?php echo $tempo_restante; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tabela de Todas as Apostas -->
                <div class="tabela-container">
                    <div class="tabela-header">
                        <h2 class="tabela-title">Todas as Apostas</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Bolão</th>
                                <th>Valor</th>
                                <th>Aposta em</th>
                                <th>Data</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($apostas) > 0): ?>
                                <?php foreach ($apostas as $aposta): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($aposta['usuario_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aposta['bolao_titulo']); ?></td>
                                        <td>R$ <?php echo number_format($aposta['valor'], 2, ',', '.'); ?></td>
                                        
                                        
<td>
    <?php
    // SOLUÇÃO CORRIGIDA: Mostrar o nome da equipe em quem apostou
    if (!empty($aposta['equipe_nome'])) {
        echo '🏀 ' . htmlspecialchars($aposta['equipe_nome']);
    } else {
        // Fallback para caso não encontre a equipe
        echo '🎯 Aposta no Bolão';
        
        // Debug no log
        error_log("Aposta ID {$aposta['id']}: equipe_id={$aposta['equipe_id']}, equipe_nome=" . ($aposta['equipe_nome'] ?? 'NULL'));
    }
    ?>
</td>


                                        <td><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $aposta['status'] === 'aprovada' ? 'success' : ($aposta['status'] === 'pendente' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($aposta['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        Nenhuma aposta encontrada com os filtros selecionados.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

<script>
    function atualizarFiltros() {
        const status = document.getElementById('filtro_status').value;
        const bolaoId = document.getElementById('filtro_bolao').value;
        
        let url = '?';
        if (status !== 'todos') url += 'status=' + encodeURIComponent(status) + '&';
        if (bolaoId) url += 'bolao_id=' + encodeURIComponent(bolaoId);
        
        // Remove o último & se existir
        url = url.replace(/&$/, '');
        window.location.href = url;
    }
    
    function limparFiltros() {
        window.location.href = '?';
    }
    
    function verBolao(bolaoId) {
        window.location.href = '?bolao_id=' + bolaoId;
    }
    
    // Adiciona os event listeners
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('filtro_status').addEventListener('change', atualizarFiltros);
        document.getElementById('filtro_bolao').addEventListener('change', atualizarFiltros);
    });
</script>



</body>
</html>