<?php
require_once 'config.php';

// Atualiza bolões expirados automaticamente
$agora = date('Y-m-d H:i:s'); // pega hora atual
$pdo->exec("UPDATE boloes SET status='finalizado' WHERE data_fim <= '$agora' AND status='ativo'");

// Bolões ativos
$stmtAtivos = $pdo->prepare("SELECT * FROM boloes WHERE status = 'ativo' ORDER BY created_at DESC");
$stmtAtivos->execute();
$boloesAtivos = $stmtAtivos->fetchAll(PDO::FETCH_ASSOC);

// Bolões finalizados
$stmtFinalizados = $pdo->prepare("SELECT * FROM boloes WHERE status = 'finalizado' ORDER BY created_at DESC");
$stmtFinalizados->execute();
$boloesFinalizados = $stmtFinalizados->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetMax - Sistema de Apostas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a1a2e;
            --secondary: #16213e;
            --accent: #ff5722;
            --accent-hover: #e64a19;
            --text: #ffffff;
            --text-secondary: #b3b3b3;
            --success: #4caf50;
            --warning: #ff9800;
            --card-bg: #25253d;
            --card-border: #39394d;
            --live: #ff0037;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, #0f0f1b 100%);
            color: var(--text);
            min-height: 100vh;
        }
        
        .container {
            display: flex;
            max-width: 1600px;
            margin: 0 auto;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: var(--secondary);
            padding: 20px 0;
            border-right: 1px solid var(--card-border);
            display: flex;
            flex-direction: column;
        }
        
        .logo {
            padding: 0 20px 20px;
            font-size: 28px;
            font-weight: bold;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--card-border);
            margin-bottom: 20px;
        }
        
        .logo span {
            color: var(--text);
        }
        
        .nav-item {
            padding: 15px 25px;
            margin: 5px 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
        }
        
        .nav-item:hover, .nav-item.active {
            background: var(--card-bg);
        }
        
        .nav-item i {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }
        
        .user-section {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid var(--card-border);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
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
            font-size: 18px;
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-weight: bold;
        }
        
        .user-balance {
            color: var(--accent);
            font-weight: bold;
            font-size: 16px;
        }
        
        .logout-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: var(--card-bg);
            color: var(--text);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: var(--accent);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .welcome-message h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .welcome-message p {
            color: var(--text-secondary);
        }
        
        .search-bar {
            display: flex;
            background: var(--card-bg);
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid var(--card-border);
        }
        
        .search-bar input {
            padding: 12px 20px;
            background: transparent;
            border: none;
            color: var(--text);
            width: 300px;
            outline: none;
        }
        
        .search-bar button {
            padding: 12px 20px;
            background: var(--accent);
            border: none;
            color: white;
            cursor: pointer;
        }
        
        /* Dashboard Sections */
        .dashboard-section {
            margin-bottom: 40px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .view-all {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid var(--card-border);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            padding: 15px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-weight: bold;
            font-size: 18px;
        }
        
        .card-badge {
            background: var(--live);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.7; }
            50% { opacity: 1; }
            100% { opacity: 0.7; }
        }
        
        .card-body {
            padding: 20px;
        }
        
        .card-description {
            color: var(--text-secondary);
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .card-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .card-detail-item {
            text-align: center;
        }
        
        .detail-label {
            font-size: 12px;
            color: var(--text-secondary);
            display: block;
        }
        
        .detail-value {
            font-weight: bold;
            font-size: 18px;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--accent-hover);
        }
        
        

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* Sports Section */
        .sports-banner {
            background: linear-gradient(to right, #2b5876, #4e4376);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .banner-content h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .banner-content p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 15px;
            max-width: 500px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .cards-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .search-bar input {
                width: 200px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--card-border);
            }
            
            .main-content {
                padding: 20px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .search-bar {
                width: 100%;
            }
            
            .search-bar input {
                width: 100%;
            }
        }
        


/* Modal Detalhes - Mesmo estilo do modal de apostas */
#modalDetalhes {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    z-index: 1001;
    padding: 20px;
    overflow-y: auto;
}

#modalDetalhes:not(.hidden) {
    display: flex;
    justify-content: center;
    align-items: flex-start; /* Mudei de center para flex-start */
    min-height: 100vh;
}

#modalDetalhes .modal-content {
    background: linear-gradient(135deg, var(--card-bg) 0%, #2a2a4a 100%);
    color: var(--text);
    padding: 30px;
    border-radius: 20px;
    max-width: 800px;
    width: 100%;
    margin: 40px auto; /* Adicionei margem */
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    border: 1px solid var(--card-border);
    position: relative;
}

#modalDetalhes .modal-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent), #ff8c00, var(--accent));
}

/* Tabela estilizada */
.apostas-table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    overflow: hidden;
}

.apostas-table thead {
    background: rgba(255, 87, 34, 0.2);
}

.apostas-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: var(--accent);
    border-bottom: 2px solid var(--accent);
}

.apostas-table td {
    padding: 12px 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.apostas-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.08);
}

.status-aprovada {
    color: #4CAF50;
    font-weight: 600;
}

.status-pendente {
    color: #FF9800;
    font-weight: 600;
}

/* Responsividade da tabela */
@media (max-width: 768px) {
    .apostas-table {
        font-size: 14px;
    }
    
    .apostas-table th,
    .apostas-table td {
        padding: 8px 10px;
    }
}



/* Modal Apostar */
#modalAposta {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    z-index: 1000;
    padding: 20px;
    overflow-y: auto;
}

#modalAposta:not(.hidden) {
    display: flex;
    justify-content: center;
    align-items: flex-start; /* Mudei de center para flex-start */
    min-height: 100vh;
}

#modalAposta .modal-content {
    background: linear-gradient(135deg, var(--card-bg) 0%, #2a2a4a 100%);
    color: var(--text);
    padding: 30px;
    border-radius: 20px;
    max-width: 500px;
    width: 100%;
    margin: 40px auto; /* Adicionei margem */
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    border: 1px solid var(--card-border);
    position: relative;
    overflow: hidden;
}

#modalAposta .modal-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent), #ff8c00, var(--accent));
}

/* Título do modal */
#modalAposta h2 {
    font-size: 24px;
    font-weight: bold;
    margin: 0;
    text-align: center;
    color: var(--text);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

#modalAposta h2::before {
    content: '⚽';
    font-size: 20px;
}

/* Container das equipes */
.teams-selection {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.team-option {
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
    overflow: hidden;
}

.team-option::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: transparent;
    transition: background 0.3s ease;
}

.team-option:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--accent);
    transform: translateY(-2px);
}

.team-option:hover::before {
    background: var(--accent);
}

.team-option.selected {
    background: rgba(255, 87, 34, 0.15);
    border-color: var(--accent);
    box-shadow: 0 5px 15px rgba(255, 87, 34, 0.2);
}

.team-option.selected::before {
    background: var(--accent);
}

.team-logo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    color: white;
    flex-shrink: 0;
}

.team-info {
    flex: 1;
}

.team-name {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 4px;
}

.team-odds {
    font-size: 14px;
    color: var(--accent);
    font-weight: bold;
}

.team-badge {
    background: var(--accent);
    color: white;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: bold;
}

/* Input de valor */
.bet-amount-section {
    background: rgba(255, 255, 255, 0.05);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid var(--card-border);
}

.bet-amount-section label {
    display: block;
    color: var(--text);
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bet-amount-section label::before {
    content: '💰';
}

.amount-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.amount-input-wrapper::before {
    content: 'R$';
    position: absolute;
    left: 12px;
    color: var(--text-secondary);
    font-weight: 600;
    z-index: 1;
}

#valorAposta {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid var(--card-border);
    padding: 12px 12px 12px 35px;
    border-radius: 8px;
    width: 100%;
    color: var(--text);
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
}

#valorAposta:focus {
    outline: none;
    border-color: var(--accent);
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 2px rgba(255, 87, 34, 0.2);
}

.quick-amounts {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.quick-amount {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid var(--card-border);
    color: var(--text);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.quick-amount:hover {
    background: var(--accent);
    color: white;
    transform: scale(1.05);
}

/* Botões do modal */
.modal-buttons {
    display: flex;
    gap: 12px;
    margin-top: 10px;
}

.modal-buttons .btn-primary {
    flex: 1;
    background: linear-gradient(135deg, var(--accent), #ff6b35);
    color: #fff;
    padding: 15px;
    border-radius: 10px;
    font-weight: bold;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.modal-buttons .btn-primary::before {
    content: '✅';
}

.modal-buttons .btn-primary:hover {
    background: linear-gradient(135deg, var(--accent-hover), #ff5722);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 87, 34, 0.4);
}

.modal-buttons .btn-secondary {
    flex: 1;
    background: transparent;
    border: 1px solid var(--card-border);
    color: var(--text);
    padding: 15px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.modal-buttons .btn-secondary::before {
    content: '❌';
}

.modal-buttons .btn-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--text-secondary);
    transform: translateY(-2px);
}

/* Responsividade */
@media (max-width: 480px) {
    #modalAposta .modal-content {
        padding: 20px;
        margin: 10px;
    }
    
    .modal-buttons {
        flex-direction: column;
    }
    
    .team-option {
        padding: 12px;
    }
}
        
      
      /* Lista vertical de equipes no modal */
#opcoesAposta {
    display: flex;
    flex-direction: column; /* força a lista vertical */
    gap: 10px; /* espaço entre as linhas */
}

.team-option {
    padding: 12px 15px;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.team-option:hover {
    background: rgba(255,255,255,0.1);
}

.team-option.selected {
    background: rgba(255, 87, 34, 0.15);
    border: 2px solid var(--accent);
}
      
      .btn-select {
    background: var(--accent);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 6px 12px;
    cursor: pointer;
    font-weight: bold;
}
.btn-select:hover {
    background: #ff5722;
}
      
      
        
    </style>
</head>

<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-dice"></i>
                <span>BetMax</span>
            </div>
            
            <div class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Início</span>
            </div>
            <div class="nav-item">
                <i class="fas fa-trophy"></i>
                <span>Bolões</span>
            </div>
            <div class="nav-item">
                <i class="fas fa-basketball-ball"></i>
                <span>Esportes</span>
            </div>
            <div class="nav-item">
                <i class="fas fa-gift"></i>
                <span>Promoções</span>
            </div>
            <div class="nav-item">
                <i class="fas fa-history"></i>
                <span>Histórico</span>
            </div>
            <div class="nav-item">
                <i class="fas fa-headset"></i>
                <span>Suporte</span>
            </div>
            
            <div class="user-section">
                <div class="user-info">
                    <div class="user-avatar">JD</div>
                    <div class="user-details">
                        <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                        <div class="user-balance">R$ 0,00</div>
                    </div>
                </div>
               <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Sair
                </a>
                </div>
            </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="welcome-message">
                    <h1>Seja Bem-Vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</h1>
                    <p>Confira os bolões em destaque e faça suas apostas</p>
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Pesquisar bolões...">
                    <button><i class="fas fa-search"></i></button>
                </div>
            </div>
            
            <!-- Sports Banner -->
            <div class="sports-banner">
                <div class="banner-content">
                    <h2>Apostas Esportivas</h2>
                    <p>Confira as melhores odds para os principais eventos esportivos do momento. Futebol, basquete, tênis e muito mais!</p>
                    <button class="btn btn-primary">Ver Eventos</button>
                </div>
                <div class="banner-image">
                    <i class="fas fa-running" style="font-size: 80px; opacity: 0.7;"></i>
                </div>
            </div>
            
            <!-- Featured Pools -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-star"></i> Bolões Campeonato de Arrancada
                    </h2>
                    <a href="#" class="view-all">
                        Ver todos <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                
                <div class="cards-grid">
                    
<?php foreach ($boloesAtivos as $bolao): ?>
<div class="card" 
     data-id="<?= $bolao['id']; ?>" 
     data-tipo="<?= $bolao['tipo']; ?>" 
     data-valor="<?= !empty($bolao['valor_minimo_aposta']) ? $bolao['valor_minimo_aposta'] : 0; ?>">

    <div class="card-header">
        <div class="card-title"><?= htmlspecialchars($bolao['titulo']); ?></div>
        <div class="card-badge">
            <?= ($bolao['data_fim'] < date('Y-m-d H:i:s')) ? 'INATIVO' : strtoupper($bolao['status']); ?>
        </div>
    </div>

    <div class="card-body">
        <p class="card-description"><?= htmlspecialchars($bolao['descricao']); ?></p>

        <?php if ($bolao['tipo'] === 'pombo'): ?>
            <input type="text" id="filtro_pombos_<?= $bolao['id']; ?>" placeholder="Buscar pombo..." class="form-control" onkeyup="filtrarPombos(<?= $bolao['id']; ?>)">
            <ul id="lista_pombos_<?= $bolao['id']; ?>">
                <?php if (!empty($bolao['pombos']) && is_array($bolao['pombos'])): ?>
                    <?php foreach ($bolao['pombos'] as $pombo): ?>
                        <li>
                            <span><?= htmlspecialchars($pombo['nome']); ?></span>
                            <button class="btn btn-primary btn-apostar"
                                    data-bolao="<?= $bolao['id']; ?>"
                                    data-participante="<?= htmlspecialchars($pombo['nome']); ?>"
                                    data-valor="<?= $bolao['valor_minimo_aposta'] ?? 0; ?>">
                                Apostar
                            </button>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        <?php else: ?>
            <ul>
                <?php if (!empty($bolao['equipes']) && is_array($bolao['equipes'])): ?>
                    <?php foreach ($bolao['equipes'] as $equipe): ?>
                        <li>
                            <span><?= htmlspecialchars($equipe['nome']); ?></span>
                            <button class="btn btn-primary btn-apostar"
                                    data-bolao="<?= $bolao['id']; ?>"
                                    data-participante="<?= htmlspecialchars($equipe['nome']); ?>"
                                    data-valor="<?= $bolao['valor_minimo_aposta'] ?? 0; ?>">
                                Apostar
                            </button>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        <?php endif; ?>

        <div class="card-details">
            <div class="card-detail-item">
                <span class="detail-label">Prêmio</span>
                <span class="detail-value">R$ <?= number_format($bolao['premio'] ?? 0, 2, ',', '.'); ?></span>
            </div>
            <div class="card-detail-item">
                <span class="detail-label">Apostas</span>
                <span class="detail-value"><?= $bolao['total_apostas'] ?? 0; ?></span>
            </div>
            <div class="card-detail-item">
                <span class="detail-label">Termina em</span>
                <span class="detail-value">
                    <?php
                    $dataFim = strtotime($bolao['data_fim']);
                    $agora = time();
                    $dias = ceil(($dataFim - $agora) / 86400);
                    echo $dias > 0 ? $dias . " dias" : "Encerrado";
                    ?>
                </span>
            </div>
        </div>

       <div class="card-actions">
    <button class="btn btn-primary btn-apostar"
            data-bolao="<?= $bolao['id']; ?>"
            data-valor="<?= $bolao['valor_minimo_aposta'] ?? 0; ?>">
        Apostar Agora
    </button>
    <button class="btn btn-secondary btn-detalhes" 
            data-bolao="<?= $bolao['id']; ?>">
        Detalhes
    </button>
</div>

<!-- Div oculta para exibir os detalhes -->
<div id="detalhes-bolao-<?= $bolao['id']; ?>" class="detalhes-bolao" style="display:none; margin-top:10px;">
    <p>Carregando detalhes...</p>
</div>
    </div>
</div>
<?php endforeach; ?>


<!-- Modal Detalhes das Apostas -->
<div id="modalDetalhes" class="hidden">
    <div class="modal-content">
        <h2 id="modalDetalhesTitulo">📊 Detalhes das Apostas</h2>
        <div id="conteudoDetalhes">
            <!-- Aqui será carregado o conteúdo via AJAX -->
        </div>
        <div class="modal-buttons">
            <button type="button" class="btn-secondary" onclick="fecharModalDetalhes()">
                <i class="fas fa-times"></i> Fechar
            </button>
        </div>
    </div>
</div>

<!-- Modal Apostar -->
<div id="modalAposta" class="hidden">
    <div class="modal-content" id="modalConteudo">
        <h2 id="modalTitulo">Apostar em: Título do Bolão</h2>
        <form id="formAposta">
            <input type="hidden" name="bolao_id" id="bolaoId">
            <div id="opcoesAposta"></div>

            <!-- Valor fixo da aposta -->
            <label for="valorAposta">Valor da aposta</label>
            <input type="number" name="valor" id="valorAposta" readonly>

            <div class="modal-buttons">
                <button type="submit" class="btn-primary">Confirmar Aposta</button>
                <button type="button" class="btn-secondary" onclick="fecharModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>


<style>
.hidden { display: none !important; }

#modalAposta {
    position: fixed;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
}
</style>

<style>
.hidden { display: none; }
#modalAposta {
    position: fixed;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
}
</style>




                    
                    

                </div>
            </div>
            
            <!-- Live Pools -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-bolt"></i> Bolões Campeonato de Pombo
                    </h2>
                    <a href="#" class="view-all">
                        Ver todos <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                
                <div class="cards-grid">
                    <!-- Live Pool 1 -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">1° Campeonato Sergipano de Pombo Correio</div>
                            <div class="card-badge">Em espera</div>
                        </div>
                        <div class="card-body">
                            <p class="card-description">Preparem-se para um evento histórico, repleto de tradição, velocidade e muita paixão! Neste primeiro Campeonato Sergipano de Pombo-Correio, criamos o palco ideal para as raças mais habilidosas exibirem seu dom de voar, orientando-se apenas pelo instinto e pela força das asas.</p>
                            <div class="card-details">
                                <div class="card-detail-item">
                                    <span class="detail-label">Prêmio</span>
                                    <span class="detail-value">R$ 2.500</span>
                                </div>
                                <div class="card-detail-item">
                                    <span class="detail-label">Apostas</span>
                                    <span class="detail-value">128</span>
                                </div>
                                <div class="card-detail-item">
                                    <span class="detail-label">Termina em</span>
                                    <span class="detail-value">3h</span>
                                </div>
                            </div>
                            <div class="card-actions">
                                <button class="btn btn-primary">Apostar Agora</button>
                                <button class="btn btn-secondary">Detalhes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ending Soon Pools -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-clock"></i> Terminando em Breve
                    </h2>
                    <a href="#" class="view-all">
                        Ver todos <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                
                <div class="cards-grid">
                    
                </div>
            </div>
            
            <!-- Finalized Pools -->
<div class="dashboard-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-archive"></i> Bolões Finalizados
        </h2>
        <a href="#" class="view-all">
            Ver todos <i class="fas fa-chevron-right"></i>
        </a>
    </div>
    
    <div class="cards-grid">
        <?php if (!empty($boloesFinalizados)): ?>
            <?php foreach ($boloesFinalizados as $bolao): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><?php echo htmlspecialchars($bolao['titulo']); ?></div>
                        <div class="card-badge">FINALIZADO</div>
                    </div>
                    <div class="card-body">
                        <p class="card-description"><?php echo htmlspecialchars($bolao['descricao']); ?></p>
                        <div class="card-details">
                            <div class="card-detail-item">
                                <span class="detail-label">Prêmio</span>
                                <span class="detail-value">R$ <?php echo number_format($bolao['premio'], 2, ',', '.'); ?></span>
                            </div>
                            <div class="card-detail-item">
                                <span class="detail-label">Apostas</span>
                                <span class="detail-value"><?php echo $bolao['total_apostas'] ?? 0; ?></span>
                            </div>
                            <div class="card-detail-item">
                                <span class="detail-label">Encerrado em</span>
                                <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($bolao['data_fim'])); ?></span>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-secondary">Ver Detalhes</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500">Nenhum bolão finalizado ainda.</p>
        <?php endif; ?>
    </div>
</div>

        </div>
    </div>

    <script>
        // Navegação entre menus
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                // Aqui você adicionaria a lógica para carregar o conteúdo correspondente
                const sectionName = this.querySelector('span').textContent;
                console.log(`Carregando: ${sectionName}`);
            });
        });
        
        // Interação com os botões de apostas
document.querySelectorAll('.btn-primary').forEach(button => {
    button.addEventListener('click', function() {
        const card = this.closest('.card');
        const bolaoId = card.getAttribute('data-id'); // precisa estar no HTML
        const title = card.querySelector('.card-title').textContent;

        document.getElementById('modalTitulo').textContent = `Apostar em: ${title}`;
        document.getElementById('modalAposta').classList.remove('hidden');

        // Buscar participantes/equipes do bolão
        fetch(`get_participantes.php?boolao_id=${bolaoId}`)
            .then(res => res.json())
            .then(data => {
                // Verifica se houve erro
                if (data.success === false) {
                    alert("Erro ao carregar participantes: " + data.error);
                    return;
                }

                const container = document.getElementById('opcoesAposta');
                container.innerHTML = '';
                data.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'team-option';
                    div.textContent = p.nome; // só o nome, sem botões extras
                    container.appendChild(div);
                });
            })
            .catch(err => {
                alert("Erro de conexão: " + err.message);
            });
    });
});

    // Seleção ao clicar
    div.addEventListener('click', () => {
        // Remove seleção anterior
        document.querySelectorAll('.team-option').forEach(opt => opt.classList.remove('selected'));
        div.classList.add('selected');

        // Atualiza input escondido no formulário
        if (!document.getElementById('participanteId')) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.id = 'participanteId';
            input.name = 'participante_id';
            document.getElementById('formAposta').appendChild(input);
        }
        document.getElementById('participanteId').value = p.id;
    });

    container.appendChild(div);
});
            });
    });
});

// Fechar modal
function fecharModal() {
    document.getElementById('modalAposta').classList.add('hidden');
}
        
        // Simulação de notificação
        setTimeout(() => {
            // Esta é apenas uma simulação - em um sistema real viria do WebSocket
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.bottom = '20px';
            notification.style.right = '20px';
            notification.style.background = 'var(--accent)';
            notification.style.color = 'white';
            notification.style.padding = '15px 20px';
            notification.style.borderRadius = '8px';
            notification.style.zIndex = '1000';
            notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            notification.innerHTML = '<strong>Novo bolão disponível!</strong> Confira o bolão da final da NBA.';
            
            document.body.appendChild(notification);
            
            // Remover após 5 segundos
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s ease';
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }, 3000);
    </script>
    
    
    






<script>
// ---------------------- Abrir modal ----------------------
document.querySelectorAll('.btn-apostar').forEach(button => {
    button.addEventListener('click', function() {
        const card = this.closest('.card');
        const bolaoId = card.getAttribute('data-id');
        const bolaoTipo = card.getAttribute('data-tipo');
        const title = card.querySelector('.card-title').textContent;

        document.getElementById('modalTitulo').textContent = `Apostar em: ${title}`;
        document.getElementById('bolaoId').value = bolaoId;
        document.getElementById('valorAposta').value = card.getAttribute('data-valor') || 0;
        document.getElementById('modalAposta').classList.remove('hidden');

        // Popular equipes/pombos
        fetch(`get_participantes.php?boolao_id=${bolaoId}&tipo=${bolaoTipo}`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('opcoesAposta');
                container.innerHTML = '';
                if(data.length === 0){
                    container.innerHTML = `<p class="text-gray-500">Nenhum participante disponível.</p>`;
                    return;
                }
                data.forEach(p => {
                    const div = document.createElement('div');
                    div.classList.add('team-option');
                    div.innerHTML = `
                        <div class="team-info">
                            <div class="team-name">${p.nome}</div>
                        </div>
                        <input type="radio" name="participante_id" value="${p.id}" class="hidden">
                    `;
                    div.addEventListener('click', function(){
                        document.querySelectorAll('.team-option').forEach(t => t.classList.remove('selected'));
                        div.classList.add('selected');
                        div.querySelector('input[name="participante_id"]').checked = true;
                    });
                    container.appendChild(div);
                });
            });
    });
});

// ---------------------- Fechar modal ----------------------
function fecharModal() {
    document.getElementById('modalAposta').classList.add('hidden');
}

// ---------------------- Submissão da aposta ----------------------
document.getElementById('formAposta').addEventListener('submit', async function(e){
    e.preventDefault();

    const bolaoId = document.getElementById('bolaoId').value;
    const participanteIdInput = document.querySelector('input[name="participante_id"]:checked');
    if(!participanteIdInput){
        alert('Selecione uma equipe para apostar!');
        return;
    }
    const participanteId = participanteIdInput.value;
    const valor = document.getElementById('valorAposta').value;
    
    // Se não tiver user_id na sessão, use um valor padrão para teste
    const userId = <?php echo isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : '1'; ?>;

    // Redirecionar para página de pagamento PIX
    window.location.href = `pagamento_pix.php?bolao_id=${bolaoId}&participante_id=${participanteId}&equipe_id=${participanteId}&valor=${valor}&user_id=${userId}`;
});

function copiarPIX(codigo){
    navigator.clipboard.writeText(codigo).then(() => {
        alert('PIX copiado para área de transferência!');
    });
}

function fecharModal(){
    document.getElementById('modalAposta').classList.add('hidden');
}

// ---------------------- Atualizar status via webhook ----------------------
function atualizarStatusPagamento(paymentId, status){
    const statusEl = document.getElementById('statusPagamento');
    if(statusEl){
        statusEl.textContent = `Status do pagamento: ${status}`;
    }
}
</script>

<?php 
// Verificar se veio do pagamento PIX com sucesso - ESTE CÓDIGO FICA NO PHP, ANTES DO HTML
if (isset($_GET['aposta_sucesso']) && $_GET['aposta_sucesso'] === 'true'): ?>
<script>
    setTimeout(function() {
        alert('🎉 Aposta registrada com sucesso! Boa sorte!');
        // Remover parâmetro da URL
        if (window.history.replaceState) {
            var cleanUrl = window.location.origin + window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }, 800);
</script>
<?php endif; ?>


<script>
// ---------------------- Modal Detalhes ----------------------
document.querySelectorAll('.btn-detalhes').forEach(btn => {
    btn.addEventListener('click', async function() {
        const bolaoId = this.getAttribute('data-bolao');
        const card = this.closest('.card');
        const tituloBolao = card.querySelector('.card-title').textContent;
        
        abrirModalDetalhes(bolaoId, tituloBolao);
    });
});

async function abrirModalDetalhes(bolaoId, tituloBolao) {
    document.getElementById('modalDetalhesTitulo').textContent = `📊 ${tituloBolao} - Apostas`;
    document.getElementById('modalDetalhes').classList.remove('hidden');
    
    const conteudoDiv = document.getElementById('conteudoDetalhes');
    conteudoDiv.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div class="loading-spinner"></div>
            <p>Carregando apostas...</p>
        </div>
    `;

    try {
        const response = await fetch(`carregar_detalhes.php?bolao_id=${bolaoId}`);
        const html = await response.text();
        conteudoDiv.innerHTML = html;
        
        // Adiciona classes CSS à tabela para estilização
        const tabela = conteudoDiv.querySelector('table');
        if (tabela) {
            tabela.className = 'apostas-table';
            
            // Estiliza os status
            tabela.querySelectorAll('td').forEach(td => {
                if (td.textContent.includes('aprovada')) {
                    td.className = 'status-aprovada';
                } else if (td.textContent.includes('pendente')) {
                    td.className = 'status-pendente';
                }
            });
        }
    } catch (error) {
        conteudoDiv.innerHTML = `
            <div style="text-align: center; color: #ff6b6b; padding: 40px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px;"></i>
                <p>Erro ao carregar detalhes das apostas.</p>
                <p>Tente novamente mais tarde.</p>
            </div>
        `;
    }
}

function fecharModalDetalhes() {
    document.getElementById('modalDetalhes').classList.add('hidden');
}

// Fechar modal ao clicar fora
document.getElementById('modalDetalhes').addEventListener('click', function(e) {
    if (e.target.id === 'modalDetalhes') {
        fecharModalDetalhes();
    }
});
</script>

</body>



</html>