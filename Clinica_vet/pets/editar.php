<?php
// =============================================================
//  VetSys – Editar Pet
//  Arquivo : pets/editar.php
//  ⭐ VERSÃO COM INTEGRAÇÃO DOG API (Preview de Fotos)
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../includes/dog_api_helper.php';

// Apenas Gerente e Recepcionista editam
exigePerfil(['Gerente', 'Recepcionista']);

$erro = '';
$pdo = getConexao();

// ── 1. Verifica o ID do Pet na URL ────────────────────────────
$id_pet = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_pet) {
    setFlash('erro', 'ID de pet inválido.');
    header('Location: index.php');
    exit;
}

// ── 2. Busca os dados atuais do pet ───────────────────────────
$stmt = $pdo->prepare('SELECT * FROM pet WHERE id_pet = :id LIMIT 1');
$stmt->execute([':id' => $id_pet]);
$pet = $stmt->fetch();

if (!$pet) {
    setFlash('erro', 'Pet não encontrado.');
    header('Location: index.php');
    exit;
}

// ── 3. Busca a lista de clientes para o <select> ──────────────
$stmtClientes = $pdo->query('SELECT id_cliente, nome_cliente FROM cliente ORDER BY nome_cliente ASC');
$listaClientes = $stmtClientes->fetchAll();

// ── 4. Carrega raças da Dog API com cache ─────────────────────
$racas_api = obterRacasComCache();

// Se for carregamento AJAX para buscar raças
if (isset($_GET['ajax']) && $_GET['ajax'] === 'racas') {
    header('Content-Type: application/json');
    
    if (empty($racas_api)) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao carregar raças. Tente novamente.'
        ]);
    } else {
        echo json_encode([
            'sucesso' => true,
            'racas' => $racas_api
        ]);
    }
    exit;
}

// Se for carregamento AJAX para buscar foto
if (isset($_GET['ajax']) && $_GET['ajax'] === 'foto') {
    header('Content-Type: application/json');
    
    $raca = trim($_GET['raca'] ?? '');
    
    if (empty($raca)) {
        echo json_encode([
            'sucesso' => false,
            'url' => ''
        ]);
    } else {
        $url = obterFotoRacaAPI($raca);
        echo json_encode([
            'sucesso' => !empty($url),
            'url' => $url
        ]);
    }
    exit;
}

// ── 5. Processa a atualização via POST ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
    $nome_pet   = trim($_POST['nome_pet'] ?? '');
    $especie    = trim($_POST['especie'] ?? '');
    $raca       = trim($_POST['raca'] ?? '');

    if (!$id_cliente || empty($nome_pet) || empty($especie)) {
        $erro = 'Tutor, Nome e Espécie são obrigatórios.';
        // Mantém os dados digitados na tela em caso de erro
        $pet['id_cliente'] = $id_cliente;
        $pet['nome_pet']   = $nome_pet;
        $pet['especie']    = $especie;
        $pet['raca']       = $raca;
    } else {
        // Validação: Se é Cachorro, a raça é obrigatória
        if ($especie === 'Cachorro' && empty($raca)) {
            $erro = 'Para Cães, selecione uma raça.';
            $pet['id_cliente'] = $id_cliente;
            $pet['nome_pet']   = $nome_pet;
            $pet['especie']    = $especie;
            $pet['raca']       = $raca;
        } else {
            // Se é Cachorro, valida se a raça existe na API
            if ($especie === 'Cachorro') {
                if (!validarRacaAPI($raca, $racas_api)) {
                    $erro = 'Raça de cachorro inválida.';
                    $pet['id_cliente'] = $id_cliente;
                    $pet['nome_pet']   = $nome_pet;
                    $pet['especie']    = $especie;
                    $pet['raca']       = $raca;
                }
            }
            
            if (empty($erro)) {
                try {
                    $stmtUpdate = $pdo->prepare('
                        UPDATE pet 
                        SET id_cliente = :id_cliente, nome_pet = :nome_pet, especie = :especie, raca = :raca 
                        WHERE id_pet = :id_pet
                    ');
                    
                    $stmtUpdate->execute([
                        ':id_cliente' => $id_cliente,
                        ':nome_pet'   => $nome_pet,
                        ':especie'    => $especie,
                        ':raca'       => $raca,
                        ':id_pet'     => $id_pet
                    ]);

                    setFlash('sucesso', 'Dados do pet atualizados com sucesso!');
                    header('Location: index.php');
                    exit;

                } catch (PDOException $e) {
                    error_log('[Pet Editar] Erro DB: ' . $e->getMessage());
                    
                    // Se a coluna 'raca' não existe, tenta sem ela (compatibilidade)
                    if (strpos($e->getMessage(), 'raca') !== false) {
                        $stmtUpdate = $pdo->prepare('
                            UPDATE pet 
                            SET id_cliente = :id_cliente, nome_pet = :nome_pet, especie = :especie 
                            WHERE id_pet = :id_pet
                        ');
                        
                        $stmtUpdate->execute([
                            ':id_cliente' => $id_cliente,
                            ':nome_pet'   => $nome_pet,
                            ':especie'    => $especie,
                            ':id_pet'     => $id_pet
                        ]);

                        setFlash('sucesso', 'Dados do pet atualizados com sucesso!');
                        header('Location: index.php');
                        exit;
                    } else {
                        $erro = 'Erro ao atualizar pet no banco de dados.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pet – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #e67e22; color: white; padding: 1rem; margin-bottom: 2rem; }
        .topbar a { color: white; text-decoration: none; }
        
        .preview-foto {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-top: 1rem;
            display: none;
        }
        
        .preview-foto.visible {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
        
        .raca-select-group {
            display: none;
        }
        
        .raca-select-group.visible {
            display: block;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="topbar d-flex justify-content-between align-items-center shadow-sm">
    <div>
        <a href="index.php" class="fw-bold fs-5">
            <i class="bi bi-arrow-left-circle me-2"></i>Voltar para Pets
        </a>
    </div>
    <div>
        <i class="bi bi-pencil-square me-1"></i> Edição de Paciente
    </div>
</div>

<div class="container" style="max-width: 600px;">
    
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-4 text-secondary">
                <i class="bi bi-dog me-2"></i>Editar: <?= htmlspecialchars($pet['nome_pet']) ?>
            </h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="editar.php?id=<?= $id_pet ?>">
                
                <div class="mb-3">
                    <label for="id_cliente" class="form-label fw-semibold">Tutor (Dono) <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_cliente" name="id_cliente" required>
                        <option value="">-- Selecione o Tutor --</option>
                        <?php foreach ($listaClientes as $cli): ?>
                            <option value="<?= $cli['id_cliente'] ?>" <?= ($pet['id_cliente'] == $cli['id_cliente']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cli['nome_cliente']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="nome_pet" class="form-label fw-semibold">Nome do Pet <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_pet" name="nome_pet" 
                           value="<?= htmlspecialchars($pet['nome_pet']) ?>" required>
                </div>

                <div class="mb-4">
                    <label for="especie" class="form-label fw-semibold">Espécie <span class="text-danger">*</span></label>
                    <select class="form-select" id="especie" name="especie" required>
                        <option value="">-- Selecione --</option>
                        <?php 
                        $especies = ['Cachorro', 'Gato', 'Ave', 'Reptil', 'Roedor', 'Outro'];
                        foreach ($especies as $esp) {
                            $selected = ($pet['especie'] === $esp) ? 'selected' : '';
                            echo "<option value=\"{$esp}\" {$selected}>{$esp}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- ▼▼▼ SELECT DE RAÇA DINÂMICO (Aparece só para Cachorro) ▼▼▼ -->
                <div class="mb-4 raca-select-group" id="racaGroup">
                    <label for="raca" class="form-label fw-semibold">
                        Raça 
                        <span class="text-danger">*</span>
                        <small class="text-muted">(carregando...)</small>
                    </label>
                    <select class="form-select" id="raca" name="raca">
                        <option value="">-- Selecione a Raça --</option>
                    </select>
                    
                    <!-- Preview de foto da raça -->
                    <div id="previewContainer">
                        <img id="fotoPreview" class="preview-foto" alt="Preview da raça">
                        <div id="previewLoading" class="mt-2" style="display: none;">
                            <small class="text-muted">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Carregando foto...
                            </small>
                        </div>
                    </div>
                </div>
                <!-- ▲▲▲ FIM DO SELECT DE RAÇA ▲▲▲ -->

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn btn-warning text-white fw-semibold">
                        <i class="bi bi-arrow-repeat me-1"></i> Atualizar Pet
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ════════════════════════════════════════════════════════════════
// JavaScript: Dog API Integration (Editar)
// ════════════════════════════════════════════════════════════════

const especieSelect = document.getElementById('especie');
const racaGroup = document.getElementById('racaGroup');
const racaSelect = document.getElementById('raca');
const fotoPreview = document.getElementById('fotoPreview');
const previewLoading = document.getElementById('previewLoading');

const racaAtual = '<?= htmlspecialchars($pet['raca'] ?? '') ?>';

// Quando a espécie muda
especieSelect.addEventListener('change', function() {
    if (this.value === 'Cachorro') {
        // Mostra o grupo de raças
        racaGroup.classList.add('visible');
        racaSelect.setAttribute('required', 'required');
        
        // Se raças ainda não foram carregadas
        if (racaSelect.options.length <= 1) {
            carregarRacas();
        }
    } else {
        // Esconde o grupo de raças
        racaGroup.classList.remove('visible');
        racaSelect.removeAttribute('required');
        fotoPreview.classList.remove('visible');
        fotoPreview.src = '';
    }
});

// Carrega as raças via AJAX
function carregarRacas() {
    const labelSmall = racaGroup.querySelector('small');
    labelSmall.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Carregando...';
    racaSelect.disabled = true;
    
    fetch('editar.php?id=<?= $id_pet ?>&ajax=racas')
        .then(response => response.json())
        .then(data => {
            if (data.sucesso && data.racas) {
                // Limpa opções antigas
                racaSelect.innerHTML = '<option value="">-- Selecione a Raça --</option>';
                
                // Adiciona as raças
                for (const [chave, nome] of Object.entries(data.racas)) {
                    const option = document.createElement('option');
                    option.value = chave;
                    option.textContent = nome;
                    
                    // Seleciona a raça atual
                    if (chave === racaAtual) {
                        option.selected = true;
                    }
                    
                    racaSelect.appendChild(option);
                }
                
                labelSmall.innerHTML = '';
                racaSelect.disabled = false;
                
                // Carrega a foto se houver raça selecionada
                if (racaAtual) {
                    carregarFotoPreview(racaAtual);
                }
            } else {
                labelSmall.innerHTML = '<span class="text-danger">Erro ao carregar raças</span>';
                racaSelect.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            labelSmall.innerHTML = '<span class="text-danger">Erro de conexão</span>';
            racaSelect.disabled = false;
        });
}

// Quando a raça muda, carrega foto preview
racaSelect.addEventListener('change', function() {
    if (this.value) {
        carregarFotoPreview(this.value);
    } else {
        fotoPreview.classList.remove('visible');
        fotoPreview.src = '';
    }
});

// Carrega foto da raça via AJAX
function carregarFotoPreview(raca) {
    previewLoading.style.display = 'block';
    fotoPreview.classList.remove('visible');
    
    fetch('editar.php?id=<?= $id_pet ?>&ajax=foto&raca=' + encodeURIComponent(raca))
        .then(response => response.json())
        .then(data => {
            previewLoading.style.display = 'none';
            
            if (data.sucesso && data.url) {
                fotoPreview.src = data.url;
                fotoPreview.classList.add('visible');
            } else {
                fotoPreview.classList.remove('visible');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            previewLoading.style.display = 'none';
        });
}

// Se já era "Cachorro" no carregamento da página
if (especieSelect.value === 'Cachorro') {
    racaGroup.classList.add('visible');
    if (racaSelect.options.length <= 1) {
        carregarRacas();
    }
}
</script>
</body>
</html>
