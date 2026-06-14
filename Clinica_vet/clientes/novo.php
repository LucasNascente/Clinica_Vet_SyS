<?php
// =============================================================
//  VetSys – Novo Cliente
//  Arquivo : clientes/novo.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

// Apenas Gerente e Recepcionista podem cadastrar
exigePerfil(['Gerente', 'Recepcionista']);

$erro = '';
$dados = [
    'nome_cliente' => '',
    'cpf'          => '',
    'telefone'     => ''
];

// ── Processa o formulário ao receber um POST ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome_cliente'] = trim($_POST['nome_cliente'] ?? '');
    $dados['cpf']          = trim($_POST['cpf'] ?? '');
    $dados['telefone']     = trim($_POST['telefone'] ?? '');

    // Validação básica
    if (empty($dados['nome_cliente'])) {
        $erro = 'O campo Nome do Tutor é obrigatório.';
    } else {
        try {
            $pdo = getConexao();
            
            // O id_usuario fica NULL por padrão, pois a recepcionista 
            // está apenas cadastrando a ficha do tutor na clínica.
            $stmt = $pdo->prepare('
                INSERT INTO cliente (nome_cliente, telefone, cpf) 
                VALUES (:nome, :telefone, :cpf)
            ');
            
            $stmt->execute([
                ':nome'     => $dados['nome_cliente'],
                ':telefone' => $dados['telefone'] ?: null, // Salva null se estiver vazio
                ':cpf'      => $dados['cpf'] ?: null       // Salva null se estiver vazio
            ]);

            // Sucesso! Grava a mensagem e redireciona (Padrão Post/Redirect/Get)
            setFlash('sucesso', 'Cliente cadastrado com sucesso!');
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            // Verifica se o erro é de violação de chave única (código 23000 do MySQL)
            if ($e->getCode() == 23000) {
                $erro = 'Já existe um cliente cadastrado com este CPF.';
            } else {
                // Em produção, você logaria o $e->getMessage() e mostraria um erro genérico
                $erro = 'Erro ao cadastrar cliente no banco de dados.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Cliente – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #1a6b3c; color: white; padding: 1rem; margin-bottom: 2rem; }
        .topbar a { color: white; text-decoration: none; }
    </style>
</head>
<body>

<div class="topbar d-flex justify-content-between align-items-center shadow-sm">
    <div>
        <a href="index.php" class="fw-bold fs-5">
            <i class="bi bi-arrow-left-circle me-2"></i>Voltar para Clientes
        </a>
    </div>
    <div>
        <i class="bi bi-person-plus-fill me-1"></i> Cadastro de Cliente
    </div>
</div>

<div class="container" style="max-width: 600px;">
    
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-4 text-secondary">Novo Tutor</h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="novo.php">
                
                <div class="mb-3">
                    <label for="nome_cliente" class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_cliente" name="nome_cliente" 
                           value="<?= htmlspecialchars($dados['nome_cliente']) ?>" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="cpf" class="form-label fw-semibold">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf" 
                           placeholder="000.000.000-00"
                           value="<?= htmlspecialchars($dados['cpf']) ?>">
                    <div class="form-text">Apenas números ou formato padrão. Deve ser único.</div>
                </div>

                <div class="mb-4">
                    <label for="telefone" class="form-label fw-semibold">Telefone / WhatsApp</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" 
                           placeholder="(00) 00000-0000"
                           value="<?= htmlspecialchars($dados['telefone']) ?>">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Salvar Cliente
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>