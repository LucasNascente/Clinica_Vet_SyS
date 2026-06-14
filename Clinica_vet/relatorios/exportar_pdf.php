<?php
// =============================================================
//  VetSys – Exportar Relatório Financeiro para PDF
//  Arquivo : relatorios/exportar_pdf.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

// Apenas o Gerente pode exportar dados financeiros
exigePerfil('Gerente');

$pdo = getConexao();

// ── 1. Faturamento Total ──────────────────────────────────────
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

// ── 3. Desempenho por Veterinário ─────────────────────────────
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
    <title>Relatorio_Financeiro_<?= date('m_Y') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; color: #000; font-size: 12pt; }
        
        /* Regras exclusivas para a saída em PDF/Impressão */
        @media print {
            @page {
                size: A4;
                margin: 20mm 15mm 20mm 15mm;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
        }

        .header-relatorio {
            border-bottom: 2px solid #1a6b3c;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .bloco-kpi {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa !important;
        }
    </style>
</head>
<body>

<div class="container my-3 no-print d-flex justify-content-between align-items-center bg-light p-3 rounded border">
    <span class="text-muted"><i class="bi bi-info-circle me-1"></i> A janela de gravação em PDF abriu automaticamente. Se fechou sem querer, clique no botão ao lado.</span>
    <div>
        <a href="index.php" class="btn btn-secondary btn-sm me-2">Voltar</a>
        <button onclick="window.print();" class="btn btn-success btn-sm">Abrir Caixa de PDF</button>
    </div>
</div>

<div class="container">
    
    <div class="header-relatorio d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">VetSys – Clínica Veterinária</h1>
            <p class="text-muted small mb-0">Relatório de Desempenho Financeiro Gerencial</p>
        </div>
        <div class="text-end">
            <p class="mb-0 small"><strong>Emitido em:</strong> <?= date('d/m/Y H:i') ?></p>
            <p class="mb-0 small text-muted">Filtro: Mês Atual e Acumulado</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6">
            <div class="bloco-kpi">
                <small class="text-muted text-uppercase d-block mb-1 fw-semibold">Faturamento do Mês Atual</small>
                <h3 class="fw-bold text-success mb-0">R$ <?= number_format($faturamento_mes, 2, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-6">
            <div class="bloco-kpi">
                <small class="text-muted text-uppercase d-block mb-1 fw-semibold">Total Histórico Acumulado</small>
                <h3 class="fw-bold text-dark mb-0">R$ <?= number_format($faturamento_total, 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-secondary mb-3 mt-4">1. Receita Detalhada por Médico Veterinário</h5>
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
            <tr>
                <th>Médico(a) Veterinário(a)</th>
                <th class="text-center" style="width: 250px;">Consultas Realizadas</th>
                <th class="text-end" style="width: 250px;">Total Faturado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($desempenho as $d): ?>
            <tr>
                <td class="fw-bold"><?= htmlspecialchars($d['nome_vet']) ?></td>
                <td class="text-center"><?= $d['total_consultas'] ?></td>
                <td class="text-end fw-bold text-success">R$ <?= number_format($d['total_gerado'] ?: 0, 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-5 pt-4 border-top text-center text-muted small">
        <p>Documento confidencial gerado internamente pelo utilizador: <strong><?= htmlspecialchars($_SESSION['nome']) ?></strong></p>
        <p class="mb-0">VetSys Management System © <?= date('Y') ?></p>
    </div>

</div>

<script>
    window.onload = function() {
        // Dispara o comando de impressão do sistema de forma imediata
        window.print();
    }
</script>
</body>
</html>