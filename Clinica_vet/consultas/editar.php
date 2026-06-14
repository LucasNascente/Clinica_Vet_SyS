<?php
// =============================================================
//  VetSys – Editar Consulta
//  Arquivo : consultas/editar.php
//  ⭐ VERSÃO COM VALIDAÇÃO DE DATAS (Não permite passado)
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$erro = '';
$pdo = getConexao();

// ── 1. Verifica o ID da URL ───────────────────────────────────
$id_consulta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_consulta) {
    setFlash('erro', 'ID de consulta inválido.');
    header('Location: index.php');
    exit;
}

// ── 2. Busca a consulta atual ─────────────────────────────────
$stmtConsulta = $pdo->prepare('SELECT * FROM consulta WHERE id_consulta = :id LIMIT 1');
$stmtConsulta->execute([':id' => $id_consulta]);
$consulta = $stmtConsulta->fetch();

if (!$consulta) {
    setFlash('erro', 'Consulta não encontrada.');
    header('Location: index.php');
    exit;
}

// ── 3. Busca os serviços já atrelados a esta consulta ─────────
$stmtServicosAtuais = $pdo->prepare('SELECT id_servico FROM itens_consulta WHERE id_consulta = :id');
$stmtServicosAtuais->execute([':id' => $id_consulta]);
// Transforma o resultado num array simples apenas com os IDs [1, 3, 5...]
$servicosSelecionadosAtuais = $stmtServicosAtuais->fetchAll(PDO::FETCH_COLUMN);

// ── 4. Busca os dados para preencher as opções do formulário ──
$pets  = $pdo->query("SELECT p.id_pet, p.nome_pet, c.nome_cliente FROM pet p JOIN cliente c ON p.id_cliente = c.id_cliente ORDER BY p.nome_pet ASC")->fetchAll();
$vets  = $pdo->query("SELECT id_veterinario, nome_vet FROM veterinario ORDER BY nome_vet ASC")->fetchAll();
$servs = $pdo->query("SELECT id_servico, descricao, valor_padrao FROM servico ORDER BY descricao ASC")->fetchAll();

// ── 5. Processa o Formulário via POST ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pet         = filter_input(INPUT_POST, 'id_pet', FILTER_VALIDATE_INT);
    $id_veterinario = filter_input(INPUT_POST, 'id_veterinario', FILTER_VALIDATE_INT);
    $data_hora      = trim($_POST['data_hora'] ?? '');
    
    $novos_servicos = $_POST['servicos'] ?? [];

    // ════════════════════════════════════════════════════════════════
    // ▼▼▼ VALIDAÇÃO DE DATA/HORA - NÃO PERMITE PASSADO ▼▼▼
    // ════════════════════════════════════════════════════════════════
    if (!empty($data_hora)) {
        // Converte string datetime-local para timestamp Unix
        // Formato recebido do input: "2026-06-10T23:30"
        $timestamp_agendado = strtotime($data_hora);
        $timestamp_agora    = time();
        
        // Compara: se a data agendada for anterior a agora, é inválida
        if ($timestamp_agendado < $timestamp_agora) {
            $erro = 'Não é possível agendar uma consulta para uma data ou hora que já passou.';
        }
    }
    // ════════════════════════════════════════════════════════════════
    // ▲▲▲ FIM DA VALIDAÇÃO DE DATA ▲▲▲
    // ════════════════════════════════════════════════════════════════

    // Validação de campos obrigatórios
    if (!$id_pet || !$id_veterinario || empty($data_hora) || empty($novos_servicos)) {
        $erro = 'Preencha todos os campos e selecione pelo menos um serviço.';
        // Mantém as escolhas para não limpar a tela do usuário
        $consulta['id_pet']         = $id_pet;
        $consulta['id_veterinario'] = $id_veterinario;
        $consulta['data_hora']      = $data_hora;
        $servicosSelecionadosAtuais = $novos_servicos;
    } elseif (empty($erro)) {
        // ✅ Só continua se não houver erro anterior (data válida)
        try {
            $pdo->beginTransaction();

            // Atualiza os dados master da consulta
            $stmtUpdate = $pdo->prepare('
                UPDATE consulta 
                SET id_pet = :id_pet, id_veterinario = :id_vet, data_hora = :data_hora 
                WHERE id_consulta = :id_consulta
            ');
            $stmtUpdate->execute([
                ':id_pet'      => $id_pet,
                ':id_vet'      => $id_veterinario,
                ':data_hora'   => $data_hora,
                ':id_consulta' => $id_consulta
            ]);

            // Remove todos os serviços antigos desta consulta
            $stmtLimpaItens = $pdo->prepare('DELETE FROM itens_consulta WHERE id_consulta = :id_consulta');
            $stmtLimpaItens->execute([':id_consulta' => $id_consulta]);

            // Insere os novos serviços selecionados com o preço atualizado
            $stmtPrecoAtual = $pdo->prepare('SELECT valor_padrao FROM servico WHERE id_servico = :id');
            $stmtNovoItem = $pdo->prepare('
                INSERT INTO itens_consulta (id_consulta, id_servico, valor_cobrado) 
                VALUES (:id_consulta, :id_servico, :valor_cobrado)
            ');

            foreach ($novos_servicos as $id_servico) {
                $stmtPrecoAtual->execute([':id' => $id_servico]);
                $preco = $stmtPrecoAtual->fetchColumn();

                $stmtNovoItem->execute([
                    ':id_consulta'   => $id_consulta,
                    ':id_servico'    => $id_servico,
                    ':valor_cobrado' => $preco
                ]);
            }

            $pdo->commit();
            setFlash('sucesso', 'Consulta remarcada/atualizada com sucesso!');
            header('Location: index.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = 'Erro ao atualizar o agendamento no banco de dados.';
        }
    }
}

// ════════════════════════════════════════════════════════════════
// Gera a data/hora MÍNIMA (agora) no formato correto para datetime-local
// Isso bloqueia o calendário do navegador para datas/horas passadas
// ════════════════════════════════════════════════════════════════
$data_minima = date('Y-m-d\TH:i');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Consulta – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #9b59b6; color: white; padding: 1rem; margin-bottom: 2rem; }
        .topbar a { color: white; text-decoration: none; }
        .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
    </style>
</head>
<body>

<div class="topbar d-flex justify-content-between align-items-center shadow-sm">
    <div>
        <a href="index.php" class="fw-bold fs-5">
            <i class="bi bi-arrow-left-circle me-2"></i>Voltar
        </a>
    </div>
    <div>
        <i class="bi bi-pencil-square me-1"></i> Remarcar / Editar
    </div>
</div>

<div class="container" style="max-width: 800px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-4 text-secondary">Editar Consulta #<?= $id_consulta ?></h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="editar.php?id=<?= $id_consulta ?>">
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="id_pet" class="form-label fw-semibold">Paciente (Pet) <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_pet" name="id_pet" required>
                            <option value="">-- Selecione o Pet --</option>
                            <?php foreach ($pets as $p): ?>
                                <option value="<?= $p['id_pet'] ?>" <?= ($consulta['id_pet'] == $p['id_pet']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nome_pet']) ?> (Tutor: <?= htmlspecialchars($p['nome_cliente']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="id_veterinario" class="form-label fw-semibold">Veterinário(a) <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_veterinario" name="id_veterinario" required>
                            <option value="">-- Selecione o Profissional --</option>
                            <?php foreach ($vets as $v): ?>
                                <option value="<?= $v['id_veterinario'] ?>" <?= ($consulta['id_veterinario'] == $v['id_veterinario']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['nome_vet']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="data_hora" class="form-label fw-semibold">
                        Data e Hora do Atendimento <span class="text-danger">*</span>
                        <small class="text-muted d-block">(Apenas datas futuras permitidas)</small>
                    </label>
                    <input 
                        type="datetime-local" 
                        class="form-control" 
                        id="data_hora" 
                        name="data_hora" 
                        value="<?= date('Y-m-d\TH:i', strtotime($consulta['data_hora'])) ?>" 
                        min="<?= $data_minima ?>"
                        required
                    >
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Mínimo: <?= date('d/m/Y H:i', strtotime($data_minima)) ?>
                    </small>
                </div>

                <div class="mb-4 p-3 bg-light rounded border">
                    <label class="form-label fw-semibold d-block border-bottom pb-2 mb-3">Serviços e Procedimentos <span class="text-danger">*</span></label>
                    <div class="checkbox-grid">
                        <?php foreach ($servs as $s): 
                            $checked = in_array($s['id_servico'], $servicosSelecionadosAtuais) ? 'checked' : '';
                        ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="servicos[]" 
                                       value="<?= $s['id_servico'] ?>" 
                                       id="serv_<?= $s['id_servico'] ?>" <?= $checked ?>>
                                <label class="form-check-label" for="serv_<?= $s['id_servico'] ?>">
                                    <?= htmlspecialchars($s['descricao']) ?> <br>
                                    <small class="text-success fw-bold">R$ <?= number_format($s['valor_padrao'], 2, ',', '.') ?></small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn text-white fw-semibold" style="background-color: #8e44ad;">
                        <i class="bi bi-arrow-repeat me-1"></i> Atualizar Consulta
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
