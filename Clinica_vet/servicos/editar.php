<?php
// =============================================================
//  VetSys – Editar Serviço
//  Arquivo : servicos/editar.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

// Apenas Gerente e Recepcionista editam o catálogo financeiro
exigePerfil(['Gerente', 'Recepcionista']);

$erro = '';
$pdo = getConexao();

// ── 1. Verifica o ID do Serviço na URL ────────────────────────
$id_servico = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_servico) {
    setFlash('erro', 'ID de serviço inválido.');
    header('Location: index.php');
    exit;
}

// ── 2. Busca os dados atuais do serviço ───────────────────────
$stmt = $pdo->prepare('SELECT * FROM servico WHERE id_servico = :id LIMIT 1');
$stmt->execute([':id' => $id_servico]);
$servico = $stmt->fetch();

if (!$servico) {
    setFlash('erro', 'Serviço não encontrado.');
    header('Location: index.php');
    exit;
}

// ── 3. Processa a atualização via POST ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = trim($_POST['descricao'] ?? '');
    // Troca vírgula por ponto para o banco de dados
    $valor_padrao = str_replace(',', '.', trim($_POST['valor_padrao'] ?? ''));

    if (empty($descricao) || !is_numeric($valor_padrao)) {
        $erro = 'Preencha a descrição e informe um valor numérico válido.';
        $servico['descricao']    = $descricao;
        $servico['valor_padrao'] = $valor_padrao;
    } else {
        try {
            $stmtUpdate = $pdo->prepare('
                UPDATE servico 
                SET descricao = :descricao, valor_padrao = :valor_padrao 
                WHERE id_servico = :id_servico
            ');
            
            $stmtUpdate->execute([
                ':descricao'    => $descricao,
                ':valor_padrao' => $valor_padrao,
                ':id_servico'   => $id_servico
            ]);

            setFlash('sucesso', 'Serviço atualizado com sucesso!');
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $erro = 'Erro ao atualizar o serviço no banco de dados.';
            $servico['descricao']    = $descricao;
            $servico['valor_padrao'] = $valor_padrao;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Serviço – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #e74c3c; color: white; padding: 1rem; margin-bottom: 2rem; }
        .topbar a { color: white; text-decoration: none; }
    </style>
</head>
<body>

<div class="topbar d-flex justify-content-between align-items-center shadow-sm">
    <div>
        <a href="index.php" class="fw-bold fs-5">
            <i class="bi bi-arrow-left-circle me-2"></i>Voltar para Serviços
        </a>
    </div>
    <div>
        <i class="bi bi-pencil-square me-1"></i> Edição de Serviço
    </div>
</div>

<div class="container" style="max-width: 600px;">
    
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-4 text-secondary">Editar Serviço</h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="editar.php?id=<?= $id_servico ?>">
                
                <div class="mb-3">
                    <label for="descricao" class="form-label fw-semibold">Descrição do Serviço <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="descricao" name="descricao" 
                           value="<?= htmlspecialchars($servico['descricao']) ?>" required>
                </div>

                <div class="mb-4">
                    <label for="valor_padrao" class="form-label fw-semibold">Valor Padrão (R$) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" step="0.01" min="0" class="form-control" id="valor_padrao" name="valor_padrao" 
                               value="<?= htmlspecialchars(number_format((float)$servico['valor_padrao'], 2, '.', '')) ?>" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn btn-danger text-white fw-semibold">
                        <i class="bi bi-arrow-repeat me-1"></i> Atualizar Serviço
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>