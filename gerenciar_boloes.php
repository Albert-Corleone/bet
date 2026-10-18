<?php
require_once 'config.php';

// VERIFICAÇÃO MANUAL DE LOGIN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Atualiza automaticamente bolões expirados
$agora = date('Y-m-d H:i:s');
$pdo->exec("UPDATE boloes SET status='finalizado' WHERE data_fim < '$agora' AND status='ativo'");

// Processar o formulário de criação de bolão
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_boolao = $_POST['tipo_boolao'] ?? '';

if ($tipo_boolao === 'campeonato_pombos') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $valor_minimo = floatval($_POST['valor_minimo']);
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $equipes = $_POST['equipes'] ?? [];

    // Validação básica
    if (empty($titulo) || empty($descricao) || $valor_minimo <= 0 ||
        empty($data_inicio) || empty($data_fim)) {
        $erro = "Preencha todos os campos corretamente";
    } else {
        try {
            $pdo->beginTransaction();

            // Criar bolão de pombos
            $stmt = $pdo->prepare("INSERT INTO boloes 
                (titulo, descricao, tipo, valor_minimo_aposta, data_inicio, data_fim, status)
                VALUES (?, ?, 'campeonato_pombos', ?, ?, ?, 'ativo')");
            $stmt->execute([$titulo, $descricao, $valor_minimo, $data_inicio, $data_fim]);
            $bolao_id = $pdo->lastInsertId();

            // Inserir equipes manualmente
            foreach ($equipes as $nomeEquipe) {
                $nomeEquipe = trim($nomeEquipe);
                if (!empty($nomeEquipe)) {
                    $stmt = $pdo->prepare("INSERT INTO equipes (boolao_id, nome) VALUES (?, ?)");
                    $stmt->execute([$bolao_id, $nomeEquipe]);
                }
            }

            // CSV (opcional)
            if (isset($_FILES['csv_equipes']) && $_FILES['csv_equipes']['tmp_name']) {
                $arquivo_csv = $_FILES['csv_equipes']['tmp_name'];
                if (($handle = fopen($arquivo_csv, 'r')) !== false) {
                    while (($linha = fgetcsv($handle)) !== false) {
                        $nomeEquipe = trim($linha[0]); // Coluna A = Nome da equipe
                        if (!empty($nomeEquipe)) {
                            $stmt = $pdo->prepare("INSERT INTO equipes (boolao_id, nome) VALUES (?, ?)");
                            $stmt->execute([$bolao_id, $nomeEquipe]);
                        }
                    }
                    fclose($handle);
                }
            }

            $pdo->commit();
            $sucesso = "Campeonato de Pombos criado com sucesso!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = "Erro ao criar bolão: " . $e->getMessage();
        }
    }
}
    
    
    elseif ($tipo_boolao === 'campeonato_arrancada') {
        // Receber dados do formulário
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        $valor_minimo = floatval($_POST['valor_minimo']);
        $data_inicio = $_POST['data_inicio'];
        $data_fim = $_POST['data_fim'];

        $nomes     = $_POST['equipes']['nome'] ?? [];
        $apelidos  = $_POST['equipes']['apelido'] ?? [];
        $veiculos  = $_POST['equipes']['veiculo'] ?? [];

        // Validação
        if (empty($titulo) || empty($descricao) || $valor_minimo <= 0 ||
            empty($data_inicio) || empty($data_fim) || count($nomes) < 2) {
            $erro = "Preencha todos os campos e adicione pelo menos 2 equipes.";
        } else {
            try {
                $pdo->beginTransaction();

                // Criar o bolão
                $stmt = $pdo->prepare("INSERT INTO boloes 
    (titulo, descricao, tipo, valor_minimo_aposta, data_inicio, data_fim, status) 
    VALUES (?, ?, ?, ?, ?, ?, 'ativo')");
$stmt->execute([$titulo, $descricao, $tipo_boolao, $valor_minimo, $data_inicio, $data_fim]);
                $bolao_id = $pdo->lastInsertId();

                // Inserir equipes
                foreach ($nomes as $i => $nomeEquipe) {
                    $nome    = trim($nomeEquipe);
                    $apelido = trim($apelidos[$i] ?? '');
                    $veiculo = trim($veiculos[$i] ?? '');

                    if (!empty($nome)) {
                        $stmt = $pdo->prepare("INSERT INTO equipes 
                            (boolao_id, nome, apelido, veiculo) 
                            VALUES (?, ?, ?, ?)");
                        $stmt->execute([$bolao_id, $nome, $apelido, $veiculo]);
                    }
                }

                $pdo->commit();
                $sucesso = "Campeonato de Arrancada criado com sucesso!";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $erro = "Erro ao criar campeonato: " . $e->getMessage();
            }
        }
    }
    
    
    
}




// Buscar bolões existentes
$stmt = $pdo->prepare("SELECT * FROM boloes ORDER BY created_at DESC");
$stmt->execute();
$boloes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tipos_booles = [
    'campeonato_pombos' => 'Campeonato de Pombos',
    'campeonato_arrancada'=> 'Campeonato de Arrancada',
    // Adicione outros tipos no futuro
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Bolões - BetMax</title>
    <style>
        /* Seus estilos atuais permanecem aqui */
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
        
        /* Alertas */
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        /* Abas */
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .tab {
            padding: 15px 30px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-light);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: var(--accent);
            border-bottom: 3px solid var(--accent);
        }
        
        .tipo-form {
            display: none;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Formulários */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
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
            margin: 10px 0;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        /* Equipes e pombos */
        .equipe-form {
            border: 1px solid #e9ecef;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            background: #f8f9fa;
        }
        
        .equipe-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .equipe-title {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        /* Lista de bolões */
        .boloes-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .bolao-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
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
        
        .bolao-body {
            padding: 20px;
        }
        
        .bolao-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .bolao-info span {
            font-weight: 600;
        }
        
        .bolao-actions {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .hidden {
            display: none;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .admin-main {
                padding: 20px;
            }
            
            .boloes-list {
                grid-template-columns: 1fr;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .nav-menu a {
                padding: 8px 15px;
                font-size: 0.9em;
            }
        }
        
        /* ✅ GARANTIR QUE OS FORMULÁRIOS FICAM VISÍVEIS */
.tipo-form {
    transition: all 0.3s ease;
}

.tipo-form.hidden {
    display: none !important;
}

.tipo-form:not(.hidden) {
    display: block !important;
}

/* ✅ MELHORAR VISUALIZAÇÃO DAS EQUIPES DE ARRANCADA */
.equipe-form {
    border: 2px solid #e9ecef;
    padding: 20px;
    margin: 15px 0;
    border-radius: 10px;
    background: #f8f9fa;
}

.equipe-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.equipe-title {
    font-weight: 600;
    color: #333;
    font-size: 1.1em;
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
                <li><a href="gerenciar_boloes.php" class="active">Bolões</a></li>
                <li><a href="gerenciar_apostas.php">Apostas</a></li>
                <li><a href="relatorios.php">Relatórios</a></li>
                <li><a href="configuracoes.php">Configurações</a></li>
                <li><a href="logout.php" style="color: var(--danger);">Sair</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="admin-main">
            <h1 class="page-title">Gerenciar Bolões</h1>
            <p class="page-subtitle">Controle completo dos bolões da plataforma</p>
            
            <?php if ($erro): ?>
                <div class="alert alert-error"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="alert alert-success"><?php echo $sucesso; ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <div class="tab active" data-tab="listar">Bolões Existentes</div>
                <div class="tab" data-tab="criar">Criar Novo Bolão</div>
            </div>
            
            <div class="tab-content active" id="listar">
                <h2>Bolões Cadastrados</h2>
                
                <?php if (count($boloes) > 0): ?>
                    <div class="boloes-list">
                        <?php foreach ($boloes as $bolao): ?>
                            <div class="bolao-card">
                                <div class="bolao-header">
                                    <h3 class="bolao-title"><?php echo $bolao['titulo']; ?></h3>
                                    <div>Tipo: <?php echo ucfirst(str_replace('_', ' ', $bolao['tipo'])); ?></div>
                                </div>
                                <div class="bolao-body">
                                    <div class="bolao-info">
                                        <div>Valor mínimo:</div>
                                        <div>R$ <?php echo number_format($bolao['valor_minimo_aposta'], 2, ',', '.'); ?></div>
                                    </div>
                                    <div class="bolao-info">
                                        <div>Início:</div>
                                        <div><?php echo date('d/m/Y H:i', strtotime($bolao['data_inicio'])); ?></div>
                                    </div>
                                    <div class="bolao-info">
                                        <div>Término:</div>
                                        <div><?php echo date('d/m/Y H:i', strtotime($bolao['data_fim'])); ?></div>
                                    </div>
                                    <div class="bolao-info">
                                        <div>Status:</div>
                                        <div><?php echo ucfirst($bolao['status']); ?></div>
                                    </div>
                                </div>
                                <div class="bolao-actions">
                                    <button class="btn btn-primary">Editar</button>
                                    <button class="btn btn-danger">Excluir</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Nenhum bolão cadastrado ainda. Crie o primeiro bolão usando a aba "Criar Novo Bolão".
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="criar">
                <div class="form-container">
                    <h2>Criar Novo Bolão</h2>
                    
                    <form id="formCriarBoolao" method="POST">
                        <div class="form-group">
                            <label for="tipo_boolao">Tipo de Bolão:</label>
                            <select id="tipo_boolao" name="tipo_boolao" class="form-control" required>
                                <option value="">Selecione o tipo</option>
                                <?php foreach ($tipos_booles as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Formulário para Campeonato de Pombos -->
                        <div id="campeonato_pombos_form" class="tipo-form hidden">
    <h3>Campeonato de Pombos</h3>

    <div class="form-group">
        <label>Título do Campeonato:</label>
        <input type="text" name="titulo" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Descrição do Campeonato:</label>
        <textarea name="descricao" class="form-control" rows="4" required></textarea>
    </div>

    <div class="form-group">
        <label>Valor Mínimo por Aposta (R$):</label>
        <input type="number" name="valor_minimo" class="form-control" step="0.01" min="0" required>
    </div>

    <div class="form-group">
        <label>Data Prevista para Início:</label>
        <input type="datetime-local" name="data_inicio" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Data Prevista para Fim:</label>
        <input type="datetime-local" name="data_fim" class="form-control" required>
    </div>

    <h3>Equipes Participantes</h3>
    <div id="equipes-container"></div>
    <button type="button" id="btnAddEquipe" class="btn btn-secondary">Adicionar Equipe</button>

    <h4>Ou importar via CSV</h4>
    <div class="form-group">
        <input type="file" name="csv_equipes" class="form-control" accept=".csv">
        <small>Coluna A = Nome da equipe, cada linha = uma equipe</small>
    </div>

    <div class="form-group" style="margin-top:20px;">
        <button type="submit" class="btn btn-primary">Criar Campeonato</button>
    </div>
</div>


                        <!-- Formulário para Campeonato de Arrancada -->
<div id="campeonato_arrancada_form" class="tipo-form hidden">
    <h3>Campeonato de Arrancada</h3>
    
    <div class="form-group">
        <label for="titulo_arrancada">Título do Campeonato:</label>
        <input type="text" id="titulo_arrancada" name="titulo" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="descricao_arrancada">Descrição do Campeonato:</label>
        <textarea id="descricao_arrancada" name="descricao" rows="4" class="form-control" required></textarea>
    </div>

    <div class="form-group">
        <label for="valor_minimo_arrancada">Valor Mínimo por Aposta (R$):</label>
        <input type="number" id="valor_minimo_arrancada" name="valor_minimo" step="0.01" min="0" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="data_inicio_arrancada">Data Prevista para Início:</label>
        <input type="datetime-local" id="data_inicio_arrancada" name="data_inicio" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="data_fim_arrancada">Data Prevista para Fim:</label>
        <input type="datetime-local" id="data_fim_arrancada" name="data_fim" class="form-control" required>
    </div>

    <h3>Equipes Participantes</h3>
    <div id="equipes-arrancada-container">
        <!-- Equipes adicionadas dinamicamente via JS -->
    </div>

    <button type="button" id="btnAddEquipeArrancada" class="btn btn-secondary">Adicionar Equipe</button>

    <div class="form-group" style="margin-top: 30px;">
        <button type="submit" class="btn btn-primary">Criar Campeonato</button>
    </div>
</div>


<script>
    // Mostrar o formulário correto baseado no tipo selecionado
    document.getElementById('tipo_boolao').addEventListener('change', function() {
        // Esconder todos os formulários e remover required
        document.querySelectorAll('.tipo-form').forEach(form => {
            form.classList.add('hidden');
            form.querySelectorAll('input, textarea, select').forEach(inp => inp.required = false);
        });

        // Mostrar o formulário selecionado e ativar required
        const selectedForm = this.value === 'campeonato_pombos' ? document.getElementById('formPombos') :
                             this.value === 'campeonato_arrancada' ? document.getElementById('formArrancada') : null;
        if (selectedForm) {
            selectedForm.classList.remove('hidden');
            selectedForm.querySelectorAll('input, textarea, select').forEach(inp => inp.required = true);
        }
    });
</script>

                        <!-- Adicione outros formulários para outros tipos de bolão aqui -->
                    </form>
                </div>
            </div>
        </main>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Script carregado!');
    
    // Sistema de abas
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(this.getAttribute('data-tab')).classList.add('active');
        });
    });

    // Mostrar formulário correto
    document.getElementById('tipo_boolao').addEventListener('change', function() {
        console.log('Tipo selecionado:', this.value);
        document.querySelectorAll('.tipo-form').forEach(form => form.classList.add('hidden'));
        const selectedForm = document.getElementById(this.value + '_form');
        if (selectedForm) {
            selectedForm.classList.remove('hidden');
            console.log('Mostrando formulário:', this.value);
        }
    });

    // Pombos - Adicionar equipe
    let equipeCount = 0;
document.getElementById('btnAddEquipe').addEventListener('click', function() {
    equipeCount++;
    const div = document.createElement('div');
    div.className = 'equipe-form';
    div.innerHTML = `
        <div class="equipe-header">
            <div class="equipe-title">Equipe ${equipeCount}</div>
            <button type="button" class="btn btn-danger btn-remove-equipe">Remover</button>
        </div>
        <div class="form-group">
            <label>Nome da Equipe:</label>
            <input type="text" name="equipes[]" class="form-control" required>
        </div>
    `;
    document.getElementById('equipes-container').appendChild(div);
    div.querySelector('.btn-remove-equipe').addEventListener('click', function() {
        div.remove();
    });
});


    // Arrancada - Adicionar equipe
    let equipeArrancadaCount = 0;
    document.getElementById('btnAddEquipeArrancada').addEventListener('click', function() {
        equipeArrancadaCount++;
        const div = document.createElement('div');
        div.className = 'equipe-form';
        div.innerHTML = `
            <div class="equipe-header">
                <div class="equipe-title">Equipe ${equipeArrancadaCount}</div>
                <button type="button" class="btn btn-danger btn-remove-equipe">Remover</button>
            </div>
            <div class="form-group">
                <label>Nome da Equipe:</label>
                <input type="text" name="equipes[nome][]" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Apelido:</label>
                <input type="text" name="equipes[apelido][]" class="form-control">
            </div>
            <div class="form-group">
                <label>Veículo:</label>
                <input type="text" name="equipes[veiculo][]" class="form-control">
            </div>
        `;
        document.getElementById('equipes-arrancada-container').appendChild(div);
        div.querySelector('.btn-remove-equipe').addEventListener('click', function() {
            div.remove();
        });
    });

    // Debug do formulário
    document.getElementById('formCriarBoolao').addEventListener('submit', function(e) {
        console.log('🎯 FORMULÁRIO ENVIADO!');
        console.log('Tipo:', document.getElementById('tipo_boolao').value);
    });
});
</script>
    
    
    
</body>
</html>