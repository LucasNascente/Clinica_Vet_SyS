<?php
// =============================================================
//  VetSys – Listagem de Clientes
//  Arquivo : clientes/index.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

// Apenas Gerente e Recepcionista gerenciam clientes
exigePerfil(['Gerente', 'Recepcionista']);

$pdo = getConexao();

// Busca todos os clientes ordenados por nome
$stmt = $pdo->query('SELECT id_cliente, nome_cliente, telefone, cpf FROM cliente ORDER BY nome_cliente ASC');
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes – VetSys</title>
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
        <a href="../painel.php" class="fw-bold fs-5">
            <i class="bi bi-arrow-left-circle me-2"></i>Voltar ao Painel
        </a>
    </div>
    <div>
        <i class="bi bi-people-fill me-1"></i> Gestão de Clientes
    </div>
</div>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-secondary">Lista de Clientes</h2>
        <a href="novo.php" class="btn btn-success">
            <i class="bi bi-plus-lg me-1"></i> Novo Cliente
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
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
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
                            <th>Nome do Tutor</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Nenhum cliente cadastrado ainda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-secondary">#<?= $c['id_cliente'] ?></td>
                                <td><?= htmlspecialchars($c['nome_cliente']) ?></td>
                                <td><?= htmlspecialchars($c['cpf'] ?: 'Não informado') ?></td>
                                <td><?= htmlspecialchars($c['telefone'] ?: 'Não informado') ?></td>
                                <td class="text-end pe-3">
                                    <a href="editar.php?id=<?= $c['id_cliente'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="excluir.php?id=<?= $c['id_cliente'] ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir <?= htmlspecialchars($c['nome_cliente']) ?>? Essa ação também removerá os pets associados.');">
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