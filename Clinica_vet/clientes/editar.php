<?php
// =============================================================
//  VetSys – Editar Cliente
//  Arquivo : clientes/editar.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$erro = '';
$pdo = getConexao();

// ── 1. Verifica se o ID foi passado na URL ────────────────────
$id_cliente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_cliente) {
    setFlash('erro', 'ID de cliente inválido.');
    header('Location: index.php');
    exit;
}

// ── 2. Busca os dados atuais do banco para preencher o form ───
$stmt = $pdo->prepare('SELECT * FROM cliente WHERE id_cliente = :id LIMIT 1');
$stmt->execute([':id' => $id_cliente]);
$cliente = $stmt->fetch();

if (!$cliente) {
    setFlash('erro', 'Cliente não encontrado.');
    header('Location: index.php');
    exit;
}

// ── 3. Processa o formulário ao receber um POST ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_cliente = trim($_POST['nome_cliente'] ?? '');
    $cpf          = trim($_POST['cpf'] ?? '');
    $telefone     = trim($_POST['telefone'] ?? '');

    if (empty($nome_cliente)) {
        $erro = 'O campo Nome do Tutor é obrigatório.';
        // Atualiza a variável $cliente para manter o que o usuário digitou em caso de erro
        $cliente['nome_cliente'] = $nome_cliente;
        $cliente['cpf']          = $cpf;
        $cliente['telefone']     = $telefone;
    } else {
        try {
            $stmtUpdate = $pdo->prepare('
                UPDATE cliente 
                SET nome_cliente = :nome, telefone = :telefone, cpf = :cpf 
                WHERE id_cliente = :id
            ');
            
            $stmtUpdate->execute([
                ':nome'     => $nome_cliente,
                ':telefone' => $telefone ?: null,
                ':cpf'      => $cpf ?: null,
                ':id'       => $id_cliente
            ]);

            setFlash('sucesso', 'Dados do cliente atualizados com sucesso!');
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = 'Já existe OUTRO cliente cadastrado com este CPF.';
            } else {
                $erro = 'Erro ao atualizar cliente no banco de dados.';
            }
            $cliente['nome_cliente'] = $nome_cliente;
            $cliente['cpf']          = $cpf;
            $cliente['telefone']     = $telefone;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente – VetSys</title>
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
        <i class="bi bi-pencil-square me-1"></i> Edição de Cliente
    </div>
</div>

<div class="container" style="max-width: 600px;">
    
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-4 text-secondary">Editar: <?= htmlspecialchars($cliente['nome_cliente']) ?></h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="editar.php?id=<?= $id_cliente ?>">
                
                <div class="mb-3">
                    <label for="nome_cliente" class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_cliente" name="nome_cliente" 
                           value="<?= htmlspecialchars($cliente['nome_cliente']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="cpf" class="form-label fw-semibold">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf" 
                           placeholder="000.000.000-00"
                           value="<?= htmlspecialchars($cliente['cpf'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="telefone" class="form-label fw-semibold">Telefone / WhatsApp</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" 
                           placeholder="(00) 00000-0000"
                           value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-repeat me-1"></i> Atualizar Dados
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>