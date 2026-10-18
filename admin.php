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

// VERIFICAÇÃO SEGURA - sem redirecionamento para a mesma página
$is_admin = (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BetMax</title>
    <style>
        /* Seus estilos atuais do admin.php */
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
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        
        .card-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
        }
        
        .card-value {
            font-size: 2em;
            font-weight: bold;
            color: var(--accent);
        }
        
        .card-link {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 15px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
        }

        /* Estilo para aviso de não-admin */
        .aviso-admin {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid #ffeaa7;
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
                    <div style="font-size: 0.9em; opacity: 0.8;">
                        <?php echo $is_admin ? 'Administrador' : 'Usuário'; ?>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Navigation -->
        <nav class="admin-nav">
            <ul class="nav-menu">
                <li><a href="admin.php" class="active">Dashboard</a></li>
                <li><a href="gerenciar_usuarios.php">Usuários</a></li>
                <li><a href="gerenciar_boloes.php">Bolões</a></li>
                <li><a href="gerenciar_apostas.php">Apostas</a></li>
                <li><a href="relatorios.php">Relatórios</a></li>
                <li><a href="configuracoes.php">Configurações</a></li>
                <li><a href="logout.php" style="color: var(--danger);">Sair</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="admin-main">
            <?php if (!$is_admin): ?>
                <div class="aviso-admin">
                    <strong>Aviso:</strong> Você não tem permissões de administrador. Algumas funcionalidades estarão limitadas.
                </div>
            <?php endif; ?>
            
            <h1 style="text-align: center; color: var(--text-dark);">Dashboard</h1>
            <p style="text-align: center; color: var(--text-light); margin-bottom: 30px;">
                Bem-vindo ao painel de controle BetMax
            </p>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-icon">👥</div>
                    <div class="card-title">Total de Usuários</div>
                    <div class="card-value">152</div>
                    <a href="gerenciar_usuarios.php" class="card-link">Gerenciar</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">🎯</div>
                    <div class="card-title">Bolões Ativos</div>
                    <div class="card-value">8</div>
                    <a href="gerenciar_boloes.php" class="card-link">Ver Todos</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">💰</div>
                    <div class="card-title">Apostas Hoje</div>
                    <div class="card-value">47</div>
                    <a href="gerenciar_apostas.php" class="card-link">Detalhes</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">📊</div>
                    <div class="card-title">Faturamento</div>
                    <div class="card-value">R$ 2.845,00</div>
                    <a href="relatorios.php" class="card-link">Relatórios</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>