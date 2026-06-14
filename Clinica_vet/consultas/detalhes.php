<?php
// =============================================================
//  VetSys – Detalhes da Consulta e Prontuário
//  Arquivo : consultas/detalhes.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigeLogin();

$pdo = getConexao();
$perfil = perfilAtual();
$erro = '';
$sucesso = '';

// ── 1. Verifica o ID da Consulta ──────────────────────────────
$id_consulta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_consulta) {
    setFlash('erro', 'ID de consulta inválido.');
    header('Location: index.php');
    exit;
}

// ── 2. Processa a atualização do Histórico Clínico (POST) ─────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($perfil, ['Gerente', 'Veterinario'])) {
    $historico_clinico = trim($_POST['historico_clinico'] ?? '');

    try {
        $stmtUpdate = $pdo->prepare('UPDATE consulta SET historico_clinico = :historico WHERE id_consulta = :id');
        $stmtUpdate->execute([
            ':historico' => $historico_clinico,
            ':id'        => $id_consulta
        ]);
        $sucesso = 'Histórico clínico atualizado com sucesso!';
    } catch (PDOException $e) {
        $erro = 'Erro ao guardar as notas clínicas no banco de dados.';
    }
}

// ── 3. Busca os dados master da Consulta ──────────────────────
$queryMaster = '
    SELECT 
        c.id_consulta, c.data_hora, c.historico_clinico,
        p.nome_pet, p.especie,
        cli.nome_cliente, cli.telefone, cli.cpf,
        v.nome_vet, v.crmv
    FROM consulta c
    JOIN pet p ON c.id_pet = p.id_pet
    JOIN cliente cli ON p.id_cliente = cli.id_cliente
    JOIN veterinario v ON c.id_veterinario = v.id_veterinario
    WHERE c.id_consulta = :id LIMIT 1
';
$stmtMaster = $pdo->prepare($queryMaster);
$stmtMaster->execute([':id' => $id_consulta]);
$consulta = $stmtMaster->fetch();

if (!$consulta) {
    setFlash('erro', 'Consulta não encontrada.');
    header('Location: index.php');
    exit;
}

// ── 4. Busca os serviços que foram realizados nesta consulta ──
$queryItens = '
    SELECT ic.valor_cobrado, s.descricao 
    FROM itens_consulta ic
    JOIN servico s ON ic.id_servico = s.id_servico
    WHERE ic.id_consulta = :id
';
$stmtItens = $pdo->prepare($queryItens);
$stmtItens->execute([':id' => $id_consulta]);
$itens = $stmtItens->fetchAll();

$valor_total = 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta #<?= $consulta['id_consulta'] ?> – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #9b59b6; color: white; padding: 1rem; margin-bottom: 2rem; }
        .topbar a { color: white; text-decoration: none; }
        .card-leitura { background: #fff; border-radius: 12px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
    </style>
</head>
<body>

<div class="topbar d-flex justify-content-between align-items-center shadow-sm">
    <div>
        <a href="index.php" class="fw-bold fs-5">
            <i class="bi bi-arrow-left-circle me-2"></i>Voltar para Consultas
        </a>
    </div>
    <div>
        <i class="bi bi-file-earmark-medical me-1"></i> Ficha de Atendimento #<?= $consulta['id_consulta'] ?>
    </div>
</div>

<div class="container">

    <?php if ($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $sucesso ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $erro ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-leitura p-3 mb-3">
                <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">
                    <i class="bi bi-bug me-1 text-warning"></i> Paciente & Tutor
                </h6>
                <p class="mb-1"><strong>Pet:</strong> <?= htmlspecialchars($consulta['nome_pet']) ?></p>
                <p class="mb-1"><strong>Espécie:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($consulta['especie']) ?></span></p>
                <p class="mb-0 mt-3"><strong>Tutor:</strong> <?= htmlspecialchars($consulta['nome_cliente']) ?></p>
                <p class="mb-1"><small class="text-muted">CPF: <?= htmlspecialchars($consulta['cpf'] ?? 'Não informado') ?></small></p>
                <p class="mb-0"><small class="text-muted">Tel: <?= htmlspecialchars($consulta['telefone'] ?? 'Não informado') ?></small></p>
            </div>

            <div class="card card-leitura p-3 mb-3">
                <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">
                    <i class="bi bi-person-badge text-info me-1"></i> Equipe Médica
                </h6>
                <p class="mb-1"><strong>Veterinário:</strong> <?= htmlspecialchars($consulta['nome_vet']) ?></p>
                <p class="mb-0"><small class="text-muted">Inscrição: <?= htmlspecialchars($consulta['crmv']) ?></small></p>
            </div>

            <div class="card card-leitura p-3">
                <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">
                    <i class="bi bi-currency-dollar text-success me-1"></i> Serviços Lançados
                </h6>
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($itens as $item): $valor_total += $item['valor_cobrado']; ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                            <div><small><?= htmlspecialchars($item['descricao']) ?></small></div>
                            <span class="text-dark fw-semibold">R$ <?= number_format($item['valor_cobrado'], 2, ',', '.') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <span class="fw-bold text-secondary">Total da Ficha:</span>
                    <span class="fs-5 fw-bold text-success">R$ <?= number_format($valor_total, 2, ',', '.') ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-leitura p-4 h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="mb-0 text-secondary"><i class="bi bi-journal-medical me-2"></i>Evolução e Histórico Clínico</h5>
                    <span class="badge bg-dark rounded-pill">
                        <i class="bi bi-calendar-event me-1"></i> <?= date('d/m/Y H:i', strtotime($consulta['data_hora'])) ?>
                    </span>
                </div>

                <?php if (in_array($perfil, ['Gerente', 'Veterinario'])): ?>
                    <form method="POST" action="detalhes.php?id=<?= $id_consulta ?>" class="flex-grow-1 d-flex flex-column">
                        <div class="mb-3 flex-grow-1">
                            <label for="historico_clinico" class="form-label text-muted">Digite abaixo as observações, sintomas, diagnósticos e medicamentos:</label>
                            <textarea class="form-control h-100" id="historico_clinico" name="historico_clinico" 
                                      style="min-height: 250px; resize: vertical;" 
                                      placeholder="Ex: Animal deu entrada com febre. Prescrevi anti-inflamatório..."><?= htmlspecialchars($consulta['historico_clinico'] ?? '') ?></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save me-1"></i> Salvar Notas Clínicas
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="p-3 bg-light rounded border flex-grow-1" style="min-height: 250px; white-space: pre-wrap;"><p class="text-dark"><?= $consulta['historico_clinico'] ? htmlspecialchars($consulta['historico_clinico']) : '<em>Nenhuma nota clínica registada para esta consulta até ao momento.</em>' ?></p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>