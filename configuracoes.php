<?php
require_once 'config.php';
requireAdmin(); // Só use esta linha para páginas que precisam de admin

// Verificar se é admin
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Configuracoes - BetMax</title>
    <style>
        /* Mesmo estilo do admin.php - copie o CSS completo do gerenciar_boloes.php */
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
            text-align: center;
        }
        
        .page-title {
            color: var(--text-dark);
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        
        .page-subtitle {
            color: var(--text-light);
            font-size: 1.2em;
            margin-bottom: 40px;
        }
        
        .coming-soon {
            background: white;
            padding: 60px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .coming-soon-icon {
            font-size: 4em;
            margin-bottom: 20px;
            color: var(--accent);
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
                <li><a href="gerenciar_usuarios.php">Usuários</a></li>
                <li><a href="gerenciar_boloes.php">Bolões</a></li>
                <li><a href="gerenciar_apostas.php">Apostas</a></li>
                <li><a href="relatorios.php">Relatórios</a></li>
                <li><a href="configuracoes.php" class="active">Configurações</a></li>
                <li><a href="logout.php" style="color: var(--danger);">Sair</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="admin-main">
            <h1 class="page-title">Gerenciar Configuracoes</h1>
            <p class="page-subtitle">Controle completo das apostas da plataforma</p>
            
            <div class="coming-soon">
                <div class="coming-soon-icon">💰</div>
                <h2>Em Desenvolvimento</h2>
                <p>Esta funcionalidade está sendo desenvolvida e estará disponível em breve.</p>
                <p>Enquanto isso, você pode gerenciar os usuários do sistema.</p>
            </div>
        </main>
    </div>
</body>
</html>