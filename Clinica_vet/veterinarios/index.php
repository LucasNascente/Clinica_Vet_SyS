<?php
// =============================================================
//  VetSys – Listagem de Veterinários
//  Arquivo : veterinarios/index.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

// Apenas Gerente e Recepcionista gerenciam a equipe
exigePerfil(['Gerente', 'Recepcionista']);

$pdo = getConexao();

// Busca os veterinários e faz um LEFT JOIN para pegar o email de login, caso exista
$query = '
    SELECT v.id_veterinario, v.nome_vet, v.crmv, u.email 
    FROM veterinario v
    LEFT JOIN usuario u ON v.id_usuario = u.id_usuario
    ORDER BY v.nome_vet ASC
';
$stmt = $pdo->query($query);
$veterinarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinários – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #1abc9c; color: white; padding: 1rem; margin-bottom: 2rem; }
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
        <i class="bi bi-person-badge-fill me-1"></i> Equipe Médica
    </div>
</div>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-secondary">Veterinários Cadastrados</h2>
        <a href="novo.php" class="btn text-white fw-semibold" style="background-color: #16a085;">
            <i class="bi bi-plus-lg me-1"></i> Novo Veterinário
        </a>
    </div>

    <?php $sucesso = getFlash('sucesso'); if ($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $sucesso ?>
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
                            <th>Nome do(a) Doutor(a)</th>
                            <th>CRMV</th>
                            <th>Conta de Acesso (Login)</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($veterinarios)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Nenhum veterinário cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($veterinarios as $v): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-secondary">#<?= $v['id_veterinario'] ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($v['nome_vet']) ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($v['crmv']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($v['email']): ?>
                                        <i class="bi bi-envelope-check text-success me-1"></i> <?= htmlspecialchars($v['email']) ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic"><i class="bi bi-envelope-dash me-1"></i> Sem login vinculado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="editar.php?id=<?= $v['id_veterinario'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="excluir.php?id=<?= $v['id_veterinario'] ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja remover este profissional?');">
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