<?php
// =============================================================
//  VetSys – Novo Serviço
//  Arquivo : servicos/novo.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$erro = '';
$dados = [
    'descricao'    => '',
    'valor_padrao' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['descricao']    = trim($_POST['descricao'] ?? '');
    // Troca vírgula por ponto para garantir compatibilidade com o MySQL
    $dados['valor_padrao'] = str_replace(',', '.', trim($_POST['valor_padrao'] ?? ''));

    if (empty($dados['descricao']) || !is_numeric($dados['valor_padrao'])) {
        $erro = 'Preencha a descrição e informe um valor numérico válido.';
    } else {
        try {
            $pdo = getConexao();
            $stmt = $pdo->prepare('
                INSERT INTO servico (descricao, valor_padrao) 
                VALUES (:descricao, :valor)
            ');
            
            $stmt->execute([
                ':descricao' => $dados['descricao'],
                ':valor'     => $dados['valor_padrao']
            ]);

            setFlash('sucesso', 'Serviço cadastrado com sucesso!');
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar o serviço no banco de dados.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Serviço – VetSys</title>
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
        <i class="bi bi-plus-circle me-1"></i> Cadastro de Serviço
    </div>
</div>

<div class="container" style="max-width: 600px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-4 text-secondary">Novo Serviço</h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="novo.php">
                
                <div class="mb-3">
                    <label for="descricao" class="form-label fw-semibold">Descrição do Serviço <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="descricao" name="descricao" 
                           placeholder="Ex: Hemograma Completo"
                           value="<?= htmlspecialchars($dados['descricao']) ?>" required autofocus>
                </div>

                <div class="mb-4">
                    <label for="valor_padrao" class="form-label fw-semibold">Valor Padrão (R$) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" step="0.01" min="0" class="form-control" id="valor_padrao" name="valor_padrao" 
                               placeholder="0.00"
                               value="<?= htmlspecialchars($dados['valor_padrao']) ?>" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn btn-danger text-white fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Salvar Serviço
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>