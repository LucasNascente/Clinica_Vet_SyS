<?php
// =============================================================
//  VetSys – Nova Consulta
//  Arquivo : consultas/novo.php
//  ⭐ VERSÃO COM VALIDAÇÃO DE DATAS (Não permite passado)
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$erro = '';
$pdo = getConexao();

// Busca dados para preencher os campos do formulário
$pets   = $pdo->query("SELECT p.id_pet, p.nome_pet, c.nome_cliente FROM pet p JOIN cliente c ON p.id_cliente = c.id_cliente ORDER BY p.nome_pet ASC")->fetchAll();
$vets   = $pdo->query("SELECT id_veterinario, nome_vet FROM veterinario ORDER BY nome_vet ASC")->fetchAll();
$servs  = $pdo->query("SELECT id_servico, descricao, valor_padrao FROM servico ORDER BY descricao ASC")->fetchAll();

// Valida se o sistema tem a base necessária
if (empty($pets) || empty($vets) || empty($servs)) {
    setFlash('erro', 'Atenção: É necessário cadastrar pelo menos um Pet, um Veterinário e um Serviço antes de agendar uma consulta.');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pet         = filter_input(INPUT_POST, 'id_pet', FILTER_VALIDATE_INT);
    $id_veterinario = filter_input(INPUT_POST, 'id_veterinario', FILTER_VALIDATE_INT);
    $data_hora      = trim($_POST['data_hora'] ?? '');
    
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
    
    // Pega o array de serviços marcados nos checkboxes
    $servicos_selecionados = $_POST['servicos'] ?? [];

    // Validação de campos obrigatórios
    // Nota: Se houver erro de data, ele já foi setado acima
    if (!$id_pet || !$id_veterinario || empty($data_hora) || empty($servicos_selecionados)) {
        $erro = 'Preencha todos os campos e selecione pelo menos um serviço.';
    } elseif (empty($erro)) {
        // ✅ Só continua se não houver erro anterior (data válida ou vazia)
        try {
            // Inicia a Transação (Garante que tudo seja salvo junto, ou nada é salvo)
            $pdo->beginTransaction();

            // 1. Cria a consulta principal
            $stmtConsulta = $pdo->prepare('
                INSERT INTO consulta (id_pet, id_veterinario, data_hora) 
                VALUES (:id_pet, :id_vet, :data_hora)
            ');
            $stmtConsulta->execute([
                ':id_pet'    => $id_pet,
                ':id_vet'    => $id_veterinario,
                ':data_hora' => $data_hora
            ]);
            
            // Pega o ID que acabou de ser gerado
            $id_consulta_gerado = $pdo->lastInsertId();

            // 2. Insere os serviços escolhidos na tabela itens_consulta
            $stmtPrecoAtual = $pdo->prepare('SELECT valor_padrao FROM servico WHERE id_servico = :id');
            $stmtItem = $pdo->prepare('
                INSERT INTO itens_consulta (id_consulta, id_servico, valor_cobrado) 
                VALUES (:id_consulta, :id_servico, :valor_cobrado)
            ');

            foreach ($servicos_selecionados as $id_servico) {
                // Descobre o preço que o serviço custa HOJE
                $stmtPrecoAtual->execute([':id' => $id_servico]);
                $preco_hoje = $stmtPrecoAtual->fetchColumn();

                // Grava o item
                $stmtItem->execute([
                    ':id_consulta'   => $id_consulta_gerado,
                    ':id_servico'    => $id_servico,
                    ':valor_cobrado' => $preco_hoje
                ]);
            }

            // Tudo deu certo? Confirma a transação
            $pdo->commit();

            setFlash('sucesso', 'Consulta agendada com sucesso!');
            header('Location: index.php');
            exit;

        } catch (Exception $e) {
            // Deu algum erro? Desfaz tudo que foi feito no banco
            $pdo->rollBack();
            $erro = 'Erro ao processar o agendamento. Tente novamente.';
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
    <title>Agendar Consulta – VetSys</title>
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
        <i class="bi bi-calendar-plus me-1"></i> Agendamento
    </div>
</div>

<div class="container" style="max-width: 800px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-4 text-secondary">Nova Consulta</h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="novo.php">
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="id_pet" class="form-label fw-semibold">Paciente (Pet) <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_pet" name="id_pet" required>
                            <option value="">-- Selecione o Pet --</option>
                            <?php foreach ($pets as $p): ?>
                                <option value="<?= $p['id_pet'] ?>" <?= (isset($_POST['id_pet']) && $_POST['id_pet'] == $p['id_pet']) ? 'selected' : '' ?>>
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
                                <option value="<?= $v['id_veterinario'] ?>" <?= (isset($_POST['id_veterinario']) && $_POST['id_veterinario'] == $v['id_veterinario']) ? 'selected' : '' ?>>
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
                        value="<?= htmlspecialchars($_POST['data_hora'] ?? '') ?>" 
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
                        <?php foreach ($servs as $s): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="servicos[]" 
                                       value="<?= $s['id_servico'] ?>" 
                                       id="serv_<?= $s['id_servico'] ?>">
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
                        <i class="bi bi-check-lg me-1"></i> Agendar Consulta
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>