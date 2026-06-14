<?php
// =============================================================
//  VetSys – Agenda do Veterinário (Minhas Consultas)
//  Arquivo : consultas/minhas.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

// Acesso exclusivo para a equipe médica
exigePerfil('Veterinario');

$pdo = getConexao();
$id_usuario_logado = $_SESSION['id_usuario'];
$erro = '';
$consultas = [];

// ── 1. Descobre quem é o Veterinário logado ───────────────────
$stmtVet = $pdo->prepare('SELECT id_veterinario, nome_vet FROM veterinario WHERE id_usuario = :id LIMIT 1');
$stmtVet->execute([':id' => $id_usuario_logado]);
$vetLogado = $stmtVet->fetch();

if (!$vetLogado) {
    $erro = 'A sua conta de acesso não está vinculada a nenhum perfil médico ativo. Contacte o gerente.';
} else {
    // ── 2. Busca apenas as consultas deste médico ────────────────
    // Trazendo primeiro as consultas a partir de hoje (Agenda futura e atual)
    $query = '
        SELECT 
            c.id_consulta, 
            c.data_hora, 
            c.historico_clinico,
            p.nome_pet, 
            p.especie,
            cli.nome_cliente AS tutor
        FROM consulta c
        JOIN pet p ON c.id_pet = p.id_pet
        JOIN cliente cli ON p.id_cliente = cli.id_cliente
        WHERE c.id_veterinario = :id_vet
        ORDER BY c.data_hora ASC
    ';
    
    $stmtConsultas = $pdo->prepare($query);
    $stmtConsultas->execute([':id_vet' => $vetLogado['id_veterinario']]);
    $consultas = $stmtConsultas->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Agenda – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #9b59b6; color: white; padding: 1rem; margin-bottom: 2rem; }
        .topbar a { color: white; text-decoration: none; }
        
        /* Destaca consultas que já passaram (para diferenciar do que ainda vai acontecer) */
        .linha-passada { opacity: 0.7; background-color: #f8f9fa !important; }
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
        <i class="bi bi-calendar3 me-1"></i> Agenda Médica
    </div>
</div>

<div class="container">
    <div class="mb-4">
        <h2 class="h4 mb-0 text-secondary">Minhas Consultas</h2>
        <?php if ($vetLogado): ?>
            <small class="text-muted">Mostrando a agenda de: <strong><?= htmlspecialchars($vetLogado['nome_vet']) ?></strong></small>
        <?php endif; ?>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-warning border-warning border-opacity-50 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $erro ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Data e Hora</th>
                            <th>Paciente</th>
                            <th>Tutor</th>
                            <th>Status do Prontuário</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultas) && !$erro): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2 text-light"></i>
                                    Nenhuma consulta agendada para si neste momento.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $agora = new DateTime();
                            foreach ($consultas as $c): 
                                $dataConsulta = new DateTime($c['data_hora']);
                                // Se a consulta já passou, aplicamos uma classe CSS para deixá-la mais "apagada"
                                $passou = ($dataConsulta < $agora);
                                $classeLinha = $passou ? 'linha-passada' : '';
                            ?>
                            <tr class="<?= $classeLinha ?>">
                                <td class="ps-3 fw-bold <?= $passou ? 'text-secondary' : 'text-dark' ?>">
                                    <?= $dataConsulta->format('d/m/Y \à\s H:i') ?>
                                    <?php if ($dataConsulta->format('Y-m-d') === $agora->format('Y-m-d')): ?>
                                        <span class="badge bg-danger ms-2">HOJE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($c['nome_pet']) ?></strong> 
                                    <span class="badge bg-secondary rounded-pill ms-1"><?= htmlspecialchars($c['especie']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($c['tutor']) ?></td>
                                <td>
                                    <?php if (!empty($c['historico_clinico'])): ?>
                                        <span class="text-success small fw-semibold"><i class="bi bi-check2-all me-1"></i>Preenchido</span>
                                    <?php else: ?>
                                        <span class="text-warning small fw-semibold"><i class="bi bi-hourglass-split me-1"></i>Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="detalhes.php?id=<?= $c['id_consulta'] ?>" class="btn btn-sm <?= $passou ? 'btn-outline-secondary' : 'btn-primary' ?>" title="Abrir Ficha de Atendimento">
                                        <i class="bi bi-journal-medical me-1"></i> Atender
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