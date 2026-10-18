<?php
require_once 'config.php';

// config.php já tenta iniciar sessão, mas garantimos também
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já está logado, redireciona conforme tipo (mantendo compatibilidade)
if (isset($_SESSION['usuario_id'])) {
    if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: interface.php');
    }
    exit;
}

$erro = ''; // usado mais abaixo no HTML

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Por favor, preencha email e senha.';
    } else {
        // --- Fallback compatível com seu comportamento atual (admin hardcoded) ---
        $hardcoded_admin_email = 'diretorexecutivo@corleonetec.com.br';
        $hardcoded_admin_password = 'password';
        if ($email === $hardcoded_admin_email && $senha === $hardcoded_admin_password) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = 1;
            $_SESSION['user_name'] = 'Diretor Executivo';
            $_SESSION['usuario_nome'] = 'Diretor Executivo';
            $_SESSION['usuario_tipo'] = 'admin';
            header('Location: admin.php');
            exit;
        }

        // --- Autenticação via tabela `usuarios` (mantendo compatibilidade com senhas em texto quando necessário) ---
        try {
            $stmt = $pdo->prepare('SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $senha_db = $user['senha'];
                $password_ok = false;

                // Se parece um hash (prefixo comum), usa password_verify
                if (is_string($senha_db) && (
                    strpos($senha_db, '$2y$') === 0 ||
                    strpos($senha_db, '$2a$') === 0 ||
                    strpos($senha_db, '$argon2') === 0
                )) {
                    if (password_verify($senha, $senha_db)) {
                        $password_ok = true;
                    }
                } else {
                    // fallback: aceita senha em texto puro (compatibilidade) MAS loga aviso
                    if ($senha_db === $senha) {
                        $password_ok = true;
                        error_log("Aviso: usuário {$user['email']} tem senha em texto claro. Recomenda-se migrar para password_hash().");
                    } else {
                        // por precaução, tenta password_verify mesmo se não tiver prefixo padrão
                        if (password_verify($senha, $senha_db)) {
                            $password_ok = true;
                        }
                    }
                }

                if ($password_ok) {
                    session_regenerate_id(true);
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nome'];
                    $_SESSION['usuario_nome'] = $user['nome'];
                    $_SESSION['usuario_tipo'] = $user['tipo'] ?? 'user';

                    if ($_SESSION['usuario_tipo'] === 'admin') {
                        header('Location: admin.php');
                    } else {
                        header('Location: interface.php');
                    }
                    exit;
                } else {
                    $erro = 'Email ou senha incorretos.';
                }
            } else {
                $erro = 'Email ou senha incorretos.';
            }
        } catch (Exception $e) {
            error_log('Erro de login: ' . $e->getMessage());
            $erro = 'Erro ao processar login. Tente novamente mais tarde.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetMax - Sua Plataforma de Apostas Esportivas</title>
    <style>
        /* Seu CSS permanece EXATAMENTE igual */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            display: flex;
            width: 90%;
            max-width: 1200px;
            height: 600px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .marketing-section {
            flex: 1.5;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1560272564-c83b66b1ad12?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ff6b00;
            margin-bottom: 20px;
            text-align: center;
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        
        .marketing-content h2 {
            font-size: 36px;
            margin-bottom: 20px;
            color: #ff6b00;
        }
        
        .benefits {
            list-style-type: none;
            margin: 30px 0;
        }
        
        .benefits li {
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
        }
        
        .benefits li:before {
            content: "✓";
            color: #4CAF50;
            font-weight: bold;
            margin-right: 10px;
            font-size: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #ff6b00;
            outline: none;
        }
        
        .login-btn {
            background: linear-gradient(135deg, #ff6b00, #ff3c00);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 0, 0.4);
        }
        
        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        
        .links a {
            color: #ff6b00;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .links a:hover {
            color: #ff3c00;
            text-decoration: underline;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .divider:before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #ddd;
        }
        
        .divider:after {
            content: "";
            position: absolute;
            right: 0;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #ddd;
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f5f5f5;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
        }
        
        .erro {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #ffcdd2;
        }
        
        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                height: auto;
            }
            
            .marketing-section, .login-section {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="marketing-section">
            <div class="marketing-content">
                <h2>Bem-vindo à BetMax</h2>
                <p>Sua plataforma completa de apostas esportivas!</p>
                
                <ul class="benefits">
                    <li>Bolões com prêmios milionários</li>
                    <li>Suporte especializado 24/7</li>
                    <li>Odds competitivas em todos os esportes</li>
                    <li>Plataforma segura e certificada</li>
                    <li>Promoções exclusivas todos os dias</li>
                </ul>
                
                <p>Junte-se a milhares de apostadores que já lucram conosco!</p>
            </div>
        </div>
        
        <div class="login-section">
            <div class="logo">BETMAX</div>
            <h1>Faça seu login</h1>
            
            <?php if (isset($erro)): ?>
            <div class="erro">
                <?php echo $erro; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso'): ?>
            <div class="mensagem sucesso">
            Cadastro realizado com sucesso! Faça login para continuar.
            </div>
            <?php endif; ?>
            
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Digite seu email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="senha" placeholder="Digite sua senha" required>
                </div>
                
                <button type="submit" class="login-btn">Entrar</button>
                
                <div class="links">
                <a href="#">Esqueci a senha</a>
                <a href="cadastro.php">Cadastrar</a>
                </div>
            </form>
            
            <div class="divider">ou entre com</div>
            
            <div class="social-login">
                <div class="social-btn">G</div>
                <div class="social-btn">f</div>
                <div class="social-btn">A</div>
            </div>
        </div>
    </div>

    <script>
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if(!email || !password) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos.');
            }
        });
        
        // Preencher automaticamente para teste (remova depois)
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').value = 'diretorexecutivo@corleonetec.com.br';
            document.getElementById('password').value = 'password';
        });
    </script>
</body>
</html>