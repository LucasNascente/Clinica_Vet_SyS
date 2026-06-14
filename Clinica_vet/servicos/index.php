<?php
// =============================================================
//  VetSys – Listagem de Serviços
//  Arquivo : servicos/index.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

// Apenas Gerente e Recepcionista acessam
exigePerfil(['Gerente', 'Recepcionista']);

$pdo = getConexao();

// Busca todos os serviços ordenados alfabeticamente
$stmt = $pdo->query('SELECT id_servico, descricao, valor_padrao FROM servico ORDER BY descricao ASC');
$servicos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços – VetSys</title>
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
        <a href="../painel.php" class="fw-bold fs-5">
            <i class="bi bi-arrow-left-circle me-2"></i>Voltar ao Painel
        </a>
    </div>
    <div>
        <i class="bi bi-clipboard2-pulse-fill me-1"></i> Catálogo de Serviços
    </div>
</div>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-secondary">Serviços Oferecidos</h2>
        <a href="novo.php" class="btn btn-danger">
            <i class="bi bi-plus-lg me-1"></i> Novo Serviço
        </a>
    </div>

    <?php $sucesso = getFlash('sucesso'); if ($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $sucesso ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php $erro = getFlash('erro'); if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $erro ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Descrição do Serviço</th>
                            <th>Valor Padrão (R$)</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($servicos)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Nenhum serviço cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($servicos as $s): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-secondary">#<?= $s['id_servico'] ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($s['descricao']) ?></td>
                                <td>
                                    <span class="text-success fw-semibold">
                                        R$ <?= number_format($s['valor_padrao'], 2, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="editar.php?id=<?= $s['id_servico'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="excluir.php?id=<?= $s['id_servico'] ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir o serviço: <?= htmlspecialchars($s['descricao']) ?>?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>