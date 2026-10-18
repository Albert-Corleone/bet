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

// Verificar se é admin
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Variáveis para mensagens
$mensagem = '';
$tipo_mensagem = '';

// Função para formatar telefone (segura contra redeclaração)
if (!function_exists('formatarTelefone')) {
    function formatarTelefone($telefone) {
        if (empty($telefone)) return 'Sem Número';
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($telefone) === 11) return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
        return $telefone;
    }
}

// Função para validar telefone (segura contra redeclaração)
if (!function_exists('validarTelefone')) {
    function validarTelefone($telefone) {
        if (empty($telefone)) return null;
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($telefone) === 10 || strlen($telefone) === 11) return $telefone;
        return false;
    }
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['adicionar_usuario'])) {
            // Adicionar novo usuário
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $senha = $_POST['senha'];
            $tipo = $_POST['tipo'];
            $status = 'ativo';
            $telefone = trim($_POST['telefone']);
            $pix_tipo = $_POST['pix_tipo'] ?? null;
            $pix_chave = trim($_POST['pix_chave']) ?: null;
            
            // Validar dados
            if (empty($nome) || empty($email) || empty($senha)) {
                throw new Exception('Todos os campos são obrigatórios!');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido!');
            }

            if (strlen($senha) < 6) {
                throw new Exception('A senha deve ter pelo menos 6 caracteres!');
            }
            
            // Validar telefone
            $telefone_validado = validarTelefone($telefone);
    if (!$telefone_validado && !empty($telefone)) {
        throw new Exception('Telefone inválido! Use o formato: 11999998888');
    }

            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                throw new Exception('Este email já está cadastrado!');
            }

            // Criptografar senha
            $senha_criptografada = password_hash($senha, PASSWORD_DEFAULT);

            // Inserir no banco
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, telefone, senha, tipo, ativo, pix_tipo, pix_chave) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nome, $email, $telefone_validado, $senha_criptografada, $tipo, 1, $pix_tipo, $pix_chave]);


            $mensagem = 'Usuário adicionado com sucesso!';
            $tipo_mensagem = 'sucesso';

        } elseif (isset($_POST['editar_usuario'])) {
            // Editar usuário existente
            $id = $_POST['id'];
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $tipo = $_POST['tipo'];
            $status = $_POST['status'] === 'ativo' ? 1 : 0;
            $telefone = trim($_POST['telefone']);
            $pix_tipo = $_POST['pix_tipo'] ?? null;
            $pix_chave = trim($_POST['pix_chave']) ?: null;
            
            // Validar dados
            if (empty($nome) || empty($email)) {
                throw new Exception('Todos os campos são obrigatórios!');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido!');
            }

            // Verificar si email já existe (excluindo o próprio usuário)
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            
            if ($stmt->fetch()) {
                throw new Exception('Este email já está cadastrado!');
            }
            $telefone_validado = validarTelefone($telefone);
            if (!$telefone_validado && !empty($telefone)) {
                throw new Exception('Telefone inválido! Use o formato: 11999998888');
            }

            // Atualizar no banco
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, tipo = ?, ativo = ?, pix_tipo = ?, pix_chave = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $telefone_validado, $tipo, $status, $pix_tipo, $pix_chave, $id]);

            $mensagem = 'Usuário atualizado com sucesso!';
            $tipo_mensagem = 'sucesso';

        } elseif (isset($_POST['excluir_usuario'])) {
            // Excluir usuário
            $id = $_POST['id'];

            // Verificar se é o último admin
            if ($_POST['tipo'] === 'admin') {
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'admin' AND ativo = 1");
                $stmt->execute();
                $total_admins = $stmt->fetch()['total'];

                if ($total_admins <= 1) {
                    throw new Exception('Não é possível excluir o último administrador!');
                }
            }

            // Excluir do banco
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);

            $mensagem = 'Usuário excluído com sucesso!';
            $tipo_mensagem = 'sucesso';
        }

    } catch (Exception $e) {
        $mensagem = $e->getMessage();
        $tipo_mensagem = 'erro';
    }
}

// Buscar usuários do banco de dados
try {
    $stmt = $pdo->query("
    SELECT id, nome, email, telefone, tipo, ativo, data_criacao, pix_chave, pix_tipo
    FROM usuarios 
    ORDER BY data_criacao DESC
");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = 'Erro ao carregar usuários: ' . $e->getMessage();
    $tipo_mensagem = 'erro';
    $usuarios = [];
}
// Buscar usuário para edição (se solicitado)
$usuario_edicao = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$_GET['editar']]);
        $usuario_edicao = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensagem = 'Erro ao carregar usuário: ' . $e->getMessage();
        $tipo_mensagem = 'erro';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - BetMax</title>
    <style>
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
            --border: #e0e0e0;
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
            border-bottom: 1px solid var(--border);
        }
        
        .nav-menu {
            display: flex;
            gap: 20px;
            list-style: none;
            flex-wrap: wrap;
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
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .page-title {
            color: var(--text-dark);
            font-size: 2em;
        }
        
        /* Mensagens */
        .mensagem {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .mensagem.sucesso {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensagem.erro {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Filtros e Busca */
        .filtros {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filtro-group {
            display: flex;
            flex-direction: column;
        }
        
        .filtro-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        
        .filtro-group select,
        .filtro-group input {
            padding: 10px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
        }
        
        .filtro-group select:focus,
        .filtro-group input:focus {
            border-color: var(--accent);
            outline: none;
        }
        
        .acoes-filtros {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        /* Tabela de Usuários */
        .tabela-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        /* Badges */
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .badge.ativo {
            background: #d4edda;
            color: #155724;
        }
        
        .badge.inativo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge.admin {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge.comum {
            background: #e2e3e5;
            color: #383d41;
        }
        
        /* Ações */
        .acoes {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .btn.btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            color: white;
        }
        
        .btn.btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn.btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn.btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn.btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--accent);
            outline: none;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .nav-menu {
                justify-content: center;
            }
            
            .admin-main {
                padding: 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            
            th, td {
                padding: 10px;
            }
            
            .acoes {
                flex-direction: column;
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
                <div class="user-avatar">DE</div>
                <div>
                    <div>Diretor Executivo</div>
                    <div style="font-size: 0.9em; opacity: 0.8;">Administrador</div>
                </div>
            </div>
        </header>
        
        <!-- Navigation -->
        <nav class="admin-nav">
            <ul class="nav-menu">
                <li><a href="admin.php">Dashboard</a></li>
                <li><a href="gerenciar_usuarios.php" class="active">Usuários</a></li>
                <li><a href="gerenciar_boloes.php">Bolões</a></li>
                <li><a href="gerenciar_apostas.php">Apostas</a></li>
                <li><a href="relatorios.php">Relatórios</a></li>
                <li><a href="configuracoes.php">Configurações</a></li>
                <li><a href="logout.php" style="color: var(--danger);">Sair</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Gerenciar Usuários</h1>
                <button class="btn btn-primary" onclick="abrirModal('modalAdicionarUsuario')">
                    ➕ Adicionar Usuário
                </button>
            </div>

            <?php if ($mensagem): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="filtros">
                <div class="filtros-grid">
                    <div class="filtro-group">
                        <label for="filtro_status">Status</label>
                        <select id="filtro_status">
                            <option value="">Todos</option>
                            <option value="Ativo">Ativo</option>
                            <option value="Inativo">Inativo</option>
                        </select>
                    </div>
                    
                    <div class="filtro-group">
                        <label for="filtro_tipo">Tipo</label>
                        <select id="filtro_tipo">
                            <option value="">Todos</option>
                            <option value="admin">Administrador</option>
                            <option value="comum">Usuário Comum</option>
                        </select>
                    </div>
                    
                    <div class="filtro-group">
                        <label for="filtro_busca">Buscar</label>
                        <input type="text" id="filtro_busca" placeholder="Nome ou email...">
                    </div>
                </div>
                
                <div class="acoes-filtros">
                    <button class="btn btn-secondary" onclick="aplicarFiltros()">Aplicar Filtros</button>
                    <button class="btn btn-secondary" onclick="limparFiltros()">Limpar</button>
                </div>
            </div>

            <!-- Tabela de Usuários -->
            <div class="tabela-container">
                <?php if (empty($usuarios)): ?>
                <div style="padding: 40px; text-align: center; color: var(--text-light);">
                    <h3>Nenhum usuário cadastrado</h3>
                    <p>Clique em "Adicionar Usuário" para começar.</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Data Cadastro</th>
                        <th>PIX</th>
                        <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php foreach ($usuarios as $usuario): ?>
    <tr>
        <td><?php echo htmlspecialchars($usuario['id']); ?></td>
        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
        <td><?php echo htmlspecialchars(!empty($usuario['telefone']) ? formatarTelefone($usuario['telefone']) : 'Sem Número'); ?></td>
        <td>
            <span class="badge <?php echo htmlspecialchars($usuario['tipo']); ?>">
                <?php echo htmlspecialchars($usuario['tipo']); ?>
            </span>
        </td>
        <td>
            <span class="badge <?php echo $usuario['ativo'] ? 'ativo' : 'inativo'; ?>">
                <?php echo $usuario['ativo'] ? 'ativo' : 'inativo'; ?>
            </span>
        </td>
        <td><?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></td>

        <!-- Coluna PIX -->
        <td>
            <?php if (!empty($usuario['pix_chave'])): ?>
                <strong><?php echo htmlspecialchars($usuario['pix_tipo']); ?>:</strong> 
                <?php echo htmlspecialchars($usuario['pix_chave']); ?>
            <?php else: ?>
                <em>Não cadastrado</em>
            <?php endif; ?>
        </td>

        <!-- Coluna Ações -->
        <td>
            <div class="acoes">
                <button class="btn btn-warning" onclick="editarUsuario(<?php echo $usuario['id']; ?>)">
                    ✏️
                </button>
                <button class="btn btn-danger" onclick="confirmarExclusao(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>', '<?php echo $usuario['tipo']; ?>')">
                    🗑️
                </button>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>

                </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Adicionar Usuário -->
    <div id="modalAdicionarUsuario" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Adicionar Novo Usuário</h2>
                <button class="modal-close" onclick="fecharModal('modalAdicionarUsuario')">×</button>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                    <div class="form-group">
    <label for="pix_tipo">Tipo de Chave PIX</label>
    <select id="pix_tipo" name="pix_tipo">
        <option value="">-- Nenhum --</option>
        <option value="CPF">CPF</option>
        <option value="CNPJ">CNPJ</option>
        <option value="Email">Email</option>
        <option value="Telefone">Telefone</option>
        <option value="Aleatória">Aleatória</option>
    </select>
</div>

<div class="form-group">
    <label for="pix_chave">Chave PIX</label>
    <input type="text" id="pix_chave" name="pix_chave" placeholder="Digite a chave PIX">
</div>

                <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" placeholder="11999998888" 
           pattern="[0-9]{11}" title="DDD + 9 dígitos (ex: 11999998888)">
                </div>

                <div class="form-group">
                <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="tipo">Tipo de Usuário</label>
                    <select id="tipo" name="tipo" required>
                        <option value="comum">Usuário Comum</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal('modalAdicionarUsuario')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" name="adicionar_usuario">
                        ➕ Adicionar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Usuário -->
    <div id="modalEditarUsuario" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Usuário</h2>
                <button class="modal-close" onclick="fecharModal('modalEditarUsuario')">×</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_nome">Nome Completo</label>
                    <input type="text" id="edit_nome" name="nome" required>
                </div>
                
                <div class="form-group">
    <label for="edit_pix_tipo">Tipo de Chave PIX</label>
    <select id="edit_pix_tipo" name="pix_tipo">
        <option value="">-- Nenhum --</option>
        <option value="CPF">CPF</option>
        <option value="CNPJ">CNPJ</option>
        <option value="Email">Email</option>
        <option value="Telefone">Telefone</option>
        <option value="Aleatória">Aleatória</option>
    </select>
</div>

<div class="form-group">
    <label for="edit_pix_chave">Chave PIX</label>
    <input type="text" id="edit_pix_chave" name="pix_chave">
</div>
                
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                    </div>

                <div class="form-group">
                    <label for="edit_telefone">Telefone</label>
                    <input type="tel" id="edit_telefone" name="telefone" placeholder="11999998888"
           pattern="[0-9]{11}" title="DDD + 9 dígitos (ex: 11999998888)">
                </div>
                
                <div class="form-group">
                    <label for="edit_tipo">Tipo de Usuário</label>
                    <select id="edit_tipo" name="tipo" required>
                        <option value="comum">Usuário Comum</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required>
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal('modalEditarUsuario')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" name="editar_usuario">
                        💾 Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmar Exclusão -->
    <div id="modalConfirmarExclusao" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirmar Exclusão</h2>
                <button class="modal-close" onclick="fecharModal('modalConfirmarExclusao')">×</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="id" id="delete_id">
                <input type="hidden" name="tipo" id="delete_tipo">
                
                <p style="margin-bottom: 20px;">Tem certeza que deseja excluir o usuário <strong id="delete_nome"></strong>?</p>
                <p style="color: var(--danger); font-weight: 600;">Esta ação não pode ser desfeita!</p>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal('modalConfirmarExclusao')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger" name="excluir_usuario">
                        🗑️ Confirmar Exclusão
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funções para modais
        function abrirModal(id) {
            document.getElementById(id).style.display = 'flex';
        }
        
        function fecharModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        
        function editarUsuario(id) {
            // Redirecionar para a página de edição
            window.location.href = 'gerenciar_usuarios.php?editar=' + id;
        }
        
        function confirmarExclusao(id, nome, tipo) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nome').textContent = nome;
            document.getElementById('delete_tipo').value = tipo;
            abrirModal('modalConfirmarExclusao');
        }
        
        // Fechar modal clicando fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        

// Filtros - Versão Corrigida para Status
function aplicarFiltros() {
    const statusFiltro = document.getElementById('filtro_status').value;
    const tipoFiltro = document.getElementById('filtro_tipo').value;
    const buscaFiltro = document.getElementById('filtro_busca').value.toLowerCase();
    
    const linhas = document.querySelectorAll('tbody tr');
    
    linhas.forEach(linha => {
        let mostrar = true;
        const cells = linha.querySelectorAll('td');
        
        // Obter valores reais das células
        const statusCelula = cells[4].querySelector('.badge').textContent.trim();
        const tipoCelula = cells[3].querySelector('.badge').textContent.trim();
        const nomeCelula = cells[1].textContent.toLowerCase();
        const emailCelula = cells[2].textContent.toLowerCase();
        
        // DEBUG: Verificar valores
        console.log('Filtro Status:', statusFiltro, 'Celula Status:', statusCelula);
        console.log('Filtro Tipo:', tipoFiltro, 'Celula Tipo:', tipoCelula);
        
        // Filtro por status - correção para minúsculo
        if (statusFiltro) {
            // Converter ambos para minúsculo para comparação
            if (statusCelula.toLowerCase() !== statusFiltro.toLowerCase()) {
                mostrar = false;
            }
        }
        
        // Filtro por tipo (já está funcionando)
        if (tipoFiltro) {
            let tipoComparacao = tipoCelula;
            if (tipoCelula === 'Administrador') tipoComparacao = 'admin';
            if (tipoCelula === 'Usuário Comum') tipoComparacao = 'comum';
            
            if (tipoComparacao !== tipoFiltro) {
                mostrar = false;
            }
        }
        
        // Filtro por busca
        if (buscaFiltro) {
            const textoCompleto = nomeCelula + ' ' + emailCelula;
            if (!textoCompleto.includes(buscaFiltro)) {
                mostrar = false;
            }
        }
        
        linha.style.display = mostrar ? '' : 'none';
    });
}
        
function limparFiltros() {
    document.getElementById('filtro_status').value = '';
    document.getElementById('filtro_tipo').value = '';
    document.getElementById('filtro_busca').value = '';
    
    // Aplicar filtros vazios para mostrar todos
    aplicarFiltros();
}
        
        // Auto-abrir modal de edição se houver parâmetro na URL
        <?php if (isset($_GET['editar']) && $usuario_edicao): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Preencher formulário de edição
            document.getElementById('edit_id').value = '<?php echo $usuario_edicao['id']; ?>';
            document.getElementById('edit_nome').value = '<?php echo htmlspecialchars($usuario_edicao['nome']); ?>';
            document.getElementById('edit_email').value = '<?php echo htmlspecialchars($usuario_edicao['email']); ?>';
            document.getElementById('edit_tipo').value = '<?php echo $usuario_edicao['tipo']; ?>';
            document.getElementById('edit_status').value = '<?php echo $usuario_edicao['ativo'] ? 'ativo' : 'inativo'; ?>';
            document.getElementById('edit_telefone').value = '<?php echo $usuario_edicao['telefone'] ?? ''; ?>';
            document.getElementById('edit_pix_tipo').value = '<?php echo $usuario_edicao['pix_tipo'] ?? ''; ?>';
            document.getElementById('edit_pix_chave').value = '<?php echo $usuario_edicao['pix_chave'] ?? ''; ?>';

            // Abrir modal
            abrirModal('modalEditarUsuario');
        });
        <?php endif; ?>
        
        // Adicionar event listeners para filtrar automaticamente
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtro_status').addEventListener('change', aplicarFiltros);
    document.getElementById('filtro_tipo').addEventListener('change', aplicarFiltros);
    document.getElementById('filtro_busca').addEventListener('input', aplicarFiltros);
    
    // Aplicar filtros iniciais se houver valores
    const hasFilters = document.getElementById('filtro_status').value || 
                      document.getElementById('filtro_tipo').value || 
                      document.getElementById('filtro_busca').value;
    
    if (hasFilters) {
        aplicarFiltros();
    }
});
        
    </script>
</body>
</html>