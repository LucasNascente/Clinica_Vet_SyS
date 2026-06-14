<?php
// =============================================================
//  VetSys – Relatório Financeiro e Gerencial
//  Arquivo : relatorios/index.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

// Proteção rigorosa: Apenas o Gerente tem acesso a dados financeiros globais
exigePerfil('Gerente');

$pdo = getConexao();

// ── 1. Faturamento Total (Histórico Completo) ─────────────────
$stmtTotal = $pdo->query('SELECT SUM(valor_cobrado) FROM itens_consulta');
$faturamento_total = $stmtTotal->fetchColumn() ?: 0;

// ── 2. Faturamento do Mês Atual ───────────────────────────────
$mes_atual = date('m');
$ano_atual = date('Y');
$stmtMes = $pdo->prepare('
    SELECT SUM(ic.valor_cobrado) 
    FROM itens_consulta ic
    JOIN consulta c ON ic.id_consulta = c.id_consulta
    WHERE MONTH(c.data_hora) = :mes AND YEAR(c.data_hora) = :ano
');
$stmtMes->execute([':mes' => $mes_atual, ':ano' => $ano_atual]);
$faturamento_mes = $stmtMes->fetchColumn() ?: 0;

// ── 3. Desempenho por Veterinário (Ideal para cálculo de comissões) 
$queryDesempenho = '
    SELECT 
        v.nome_vet,
        COUNT(DISTINCT c.id_consulta) as total_consultas,
        SUM(ic.valor_cobrado) as total_gerado
    FROM veterinario v
    LEFT JOIN consulta c ON v.id_veterinario = c.id_veterinario
    LEFT JOIN itens_consulta ic ON c.id_consulta = ic.id_consulta
    GROUP BY v.id_veterinario
    ORDER BY total_gerado DESC
';
$stmtDesempenho = $pdo->query($queryDesempenho);
$desempenho = $stmtDesempenho->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #1a6b3c; color: white; padding: 1rem; margin-bottom: 2rem; }
        .topbar a { color: white; text-decoration: none; }
        .card-kpi { border: none; border-radius: 12px; transition: transform 0.2s; }
        .card-kpi:hover { transform: translateY(-3px); }
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
        <i class="bi bi-bar-chart-fill me-1"></i> Dashboard Gerencial
    </div>
</div>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-secondary">Visão Geral de Faturamento</h2>
        <a href="exportar_pdf.php" target="_blank" class="btn btn-danger fw-semibold shadow-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Exportar para PDF
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card card-kpi bg-success text-white shadow-sm h-100 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-semibold mb-2 opacity-75">Faturamento (<?= date('M/Y') ?>)</h6>
                        <h2 class="mb-0 fw-bold">R$ <?= number_format($faturamento_mes, 2, ',', '.') ?></h2>
                    </div>
                    <i class="bi bi-calendar-check" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-kpi bg-dark text-white shadow-sm h-100 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-semibold mb-2 opacity-75">Total Acumulado (Sempre)</h6>
                        <h2 class="mb-0 fw-bold">R$ <?= number_format($faturamento_total, 2, ',', '.') ?></h2>
                    </div>
                    <i class="bi bi-cash-coin" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white pt-4 pb-3 border-bottom-0">
            <h5 class="mb-0 text-secondary"><i class="bi bi-person-lines-fill me-2"></i>Receita Gerada por Profissional</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Médico(a) Veterinário(a)</th>
                            <th class="text-center">Consultas Realizadas</th>
                            <th class="text-end pe-4">Receita Gerada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($desempenho)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">Nenhum dado financeiro registado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($desempenho as $d): 
                                $gerado = $d['total_gerado'] ?: 0;
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($d['nome_vet']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill fs-6"><?= $d['total_consultas'] ?></span>
                                </td>
                                <td class="text-end pe-4 fw-semibold text-success">
                                    R$ <?= number_format($gerado, 2, ',', '.') ?>
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