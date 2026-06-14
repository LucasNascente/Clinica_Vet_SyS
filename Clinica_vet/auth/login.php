<?php
// =============================================================
//  VetSys – Login
//  Arquivo : auth/login.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

// Se já estiver logado, vai direto ao painel
if (estaLogado()) {
    header('Location: ' . BASE_URL . 'painel.php');
    exit;
}

$erro = '';

// ── Processa o formulário ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Validação básica
    if (empty($email) || empty($senha)) {
        $erro = 'Preencha o e-mail e a senha.';
    } else {
        $pdo  = getConexao();
        $stmt = $pdo->prepare('SELECT id_usuario, email, senha, perfil FROM usuario WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {

            // Regenera o ID da sessão para evitar session fixation
            session_regenerate_id(true);

            // Determina o nome a exibir conforme o perfil
            $nome = $email; // fallback
            if ($usuario['perfil'] === 'Veterinario') {
                $stmt2 = $pdo->prepare('SELECT nome_vet FROM veterinario WHERE id_usuario = :id LIMIT 1');
                $stmt2->execute([':id' => $usuario['id_usuario']]);
                $vet  = $stmt2->fetch();
                $nome = $vet ? $vet['nome_vet'] : $email;
            } elseif ($usuario['perfil'] === 'Cliente') {
                $stmt2 = $pdo->prepare('SELECT nome_cliente FROM cliente WHERE id_usuario = :id LIMIT 1');
                $stmt2->execute([':id' => $usuario['id_usuario']]);
                $cli  = $stmt2->fetch();
                $nome = $cli ? $cli['nome_cliente'] : $email;
            }

            // Grava dados na sessão
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['email']      = $usuario['email'];
            $_SESSION['perfil']     = $usuario['perfil'];
            $_SESSION['nome']       = $nome;

            header('Location: ' . BASE_URL . 'painel.php');
            exit;

        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – VetSys</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a6b3c 0%, #2ecc71 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-login {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,.2);
            width: 100%;
            max-width: 420px;
        }
        .logo-area {
            background: #1a6b3c;
            border-radius: 16px 16px 0 0;
            padding: 2rem;
            text-align: center;
            color: #fff;
        }
        .logo-area i { font-size: 3rem; }
        .logo-area h1 { font-size: 1.8rem; font-weight: 700; margin: .5rem 0 .2rem; }
        .logo-area small { opacity: .8; }
        .btn-entrar {
            background: #1a6b3c;
            border: none;
            padding: .75rem;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: .5px;
        }
        .btn-entrar:hover { background: #145530; }
    </style>
</head>
<body>
<div class="card-login card">

    <!-- Cabeçalho com logo -->
    <div class="logo-area">
        <i class="bi bi-heart-pulse-fill"></i>
        <h1>VetSys</h1>
        <small>Sistema de Gestão Veterinária</small>
    </div>

    <!-- Formulário -->
    <div class="card-body p-4">
        <h5 class="text-center text-muted mb-4">Acesse sua conta</h5>

        <?php if ($erro): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <?php $flash = getFlash('sucesso'); if ($flash): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <!-- E-mail -->
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">E-mail</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="seu@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                        autofocus
                    >
                </div>
            </div>

            <!-- Senha -->
            <div class="mb-4">
                <label for="senha" class="form-label fw-semibold">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        class="form-control"
                        placeholder="••••••••"
                        required
                    >
                    <button class="btn btn-outline-secondary" type="button" id="toggleSenha" title="Mostrar/ocultar senha">
                        <i class="bi bi-eye" id="iconeSenha"></i>
                    </button>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-entrar btn-success text-white">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Botão mostrar/ocultar senha
    document.getElementById('toggleSenha').addEventListener('click', function () {
        const input  = document.getElementById('senha');
        const icone  = document.getElementById('iconeSenha');
        const visivel = input.type === 'password';
        input.type   = visivel ? 'text' : 'password';
        icone.className = visivel ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
</script>
</body>
</html>
