<?php
// =============================================================
//  VetSys – Listagem e Busca Avançada de Consultas
//  Arquivo : consultas/index.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

// Apenas Gerente e Recepcionista acessam o agendamento geral
exigePerfil(['Gerente', 'Recepcionista']);

$pdo = getConexao();

// ── 1. Construção Dinâmica da Query de Busca Avançada ─────────
$query = '
    SELECT 
        c.id_consulta, 
        c.data_hora, 
        p.nome_pet, 
        cli.nome_cliente AS tutor, 
        v.nome_vet,
        (SELECT COALESCE(SUM(valor_cobrado), 0) FROM itens_consulta WHERE id_consulta = c.id_consulta) AS valor_total
    FROM consulta c
    JOIN pet p ON c.id_pet = p.id_pet
    JOIN cliente cli ON p.id_cliente = cli.id_cliente
    JOIN veterinario v ON c.id_veterinario = v.id_veterinario
';

$condicoes = [];
$parametros = [];

// Filtro 1: Texto (Nome do Pet ou do Tutor)
$busca_texto = $_GET['busca'] ?? '';
if ($busca_texto !== '') {
    $condicoes[] = '(p.nome_pet LIKE :busca_pet OR cli.nome_cliente LIKE :busca_tutor)';
    $parametros[':busca_pet']   = "%{$busca_texto}%";
    $parametros[':busca_tutor'] = "%{$busca_texto}%";
}

// Filtro 2: Relacional (Veterinário Específico)
$filtro_vet = $_GET['id_veterinario'] ?? '';
if ($filtro_vet !== '') {
    $condicoes[] = 'c.id_veterinario = :id_vet';
    $parametros[':id_vet'] = $filtro_vet;
}

// Filtro 3: Data (Data exata da Consulta)
$filtro_data = $_GET['data_consulta'] ?? '';
if ($filtro_data !== '') {
    $condicoes[] = 'DATE(c.data_hora) = :data';
    $parametros[':data'] = $filtro_data;
}

// Aplica as condições ao SQL apenas se o utilizador tiver preenchido algum filtro
if (count($condicoes) > 0) {
    $query .= ' WHERE ' . implode(' AND ', $condicoes);
}

$query .= ' ORDER BY c.data_hora DESC';

// Executa a query final
$stmt = $pdo->prepare($query);
$stmt->execute($parametros);
$consultas = $stmt->fetchAll();

// ── 2. Busca lista de veterinários para preencher o <select> ──
$vets = $pdo->query("SELECT id_veterinario, nome_vet FROM veterinario ORDER BY nome_vet ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #9b59b6; color: white; padding: 1rem; margin-bottom: 2rem; }
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
        <i class="bi bi-calendar2-check-fill me-1"></i> Agendamentos e Consultas
    </div>
</div>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-secondary">Histórico de Consultas</h2>
        <a href="novo.php" class="btn text-white fw-semibold" style="background-color: #8e44ad;">
            <i class="bi bi-plus-lg me-1"></i> Agendar Consulta
        </a>
    </div>

    <?php $sucesso = getFlash('sucesso'); if ($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $sucesso ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="busca" class="form-label fw-semibold text-secondary small">Buscar por Pet ou Tutor</label>
                    <input type="text" class="form-control" id="busca" name="busca" placeholder="Ex: Rex ou Lucas..." value="<?= htmlspecialchars($busca_texto) ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="id_veterinario" class="form-label fw-semibold text-secondary small">Filtrar por Veterinário</label>
                    <select class="form-select" id="id_veterinario" name="id_veterinario">
                        <option value="">Todos os Médicos</option>
                        <?php foreach ($vets as $v): ?>
                            <option value="<?= $v['id_veterinario'] ?>" <?= ($filtro_vet == $v['id_veterinario']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['nome_vet']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="data_consulta" class="form-label fw-semibold text-secondary small">Data do Atendimento</label>
                    <input type="date" class="form-control" id="data_consulta" name="data_consulta" value="<?= htmlspecialchars($filtro_data) ?>">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i> Filtrar</button>
                    <?php if ($busca_texto || $filtro_vet || $filtro_data): ?>
                        <a href="index.php" class="btn btn-outline-danger" title="Limpar Filtros"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Data e Hora</th>
                            <th>Paciente (Tutor)</th>
                            <th>Veterinário(a)</th>
                            <th>Valor Total</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultas)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Nenhuma consulta encontrada com estes filtros.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($consultas as $c): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark">
                                    <i class="bi bi-clock me-1 text-muted"></i>
                                    <?= date('d/m/Y \à\s H:i', strtotime($c['data_hora'])) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($c['nome_pet']) ?> 
                                    <small class="text-muted d-block"><?= htmlspecialchars($c['tutor']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($c['nome_vet']) ?></td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        R$ <?= number_format($c['valor_total'], 2, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="detalhes.php?id=<?= $c['id_consulta'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver Detalhes">
                                        <i class="bi bi-search"></i>
                                    </a>
                                    <a href="editar.php?id=<?= $c['id_consulta'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="excluir.php?id=<?= $c['id_consulta'] ?>" class="btn btn-sm btn-outline-danger" title="Cancelar Agendamento" onclick="return confirm('Tem certeza que deseja cancelar e apagar esta consulta do sistema?');">
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