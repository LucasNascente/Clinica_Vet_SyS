<?php
// =============================================================
//  VetSys – Novo Pet
//  Arquivo : pets/novo.php
//  ⭐ VERSÃO COM INTEGRAÇÃO DOG API (Raças e Fotos)
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../includes/dog_api_helper.php';

// Apenas Gerente e Recepcionista cadastram
exigePerfil(['Gerente', 'Recepcionista']);

$erro = '';
$pdo = getConexao();

// Busca todos os clientes para preencher o campo <select>
$stmtClientes = $pdo->query('SELECT id_cliente, nome_cliente FROM cliente ORDER BY nome_cliente ASC');
$listaClientes = $stmtClientes->fetchAll();

// Se não houver clientes cadastrados, não tem como cadastrar pet
if (empty($listaClientes)) {
    setFlash('erro', 'Você precisa cadastrar pelo menos um cliente antes de cadastrar um pet.');
    header('Location: ../clientes/novo.php');
    exit;
}

// Carrega as raças da Dog API com cache
$racas_api = obterRacasComCache();

$dados = [
    'id_cliente' => '',
    'nome_pet'   => '',
    'especie'    => '',
    'raca'       => ''
];

$url_foto_preview = '';

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

// Processa o formulário via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['id_cliente'] = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
    $dados['nome_pet']   = trim($_POST['nome_pet'] ?? '');
    $dados['especie']    = trim($_POST['especie'] ?? '');
    $dados['raca']       = trim($_POST['raca'] ?? '');

    if (!$dados['id_cliente'] || empty($dados['nome_pet']) || empty($dados['especie'])) {
        $erro = 'Tutor, Nome e Espécie são obrigatórios.';
    } else {
        // Validação: Se é Cachorro, a raça é obrigatória
        if ($dados['especie'] === 'Cachorro' && empty($dados['raca'])) {
            $erro = 'Para Cães, selecione uma raça.';
        } else {
            // Se é Cachorro, valida se a raça existe na API
            if ($dados['especie'] === 'Cachorro') {
                if (!validarRacaAPI($dados['raca'], $racas_api)) {
                    $erro = 'Raça de cachorro inválida.';
                }
            }
            
            if (empty($erro)) {
                try {
                    $stmt = $pdo->prepare('
                        INSERT INTO pet (id_cliente, nome_pet, especie, raca) 
                        VALUES (:id_cliente, :nome_pet, :especie, :raca)
                    ');
                    
                    $stmt->execute([
                        ':id_cliente' => $dados['id_cliente'],
                        ':nome_pet'   => $dados['nome_pet'],
                        ':especie'    => $dados['especie'],
                        ':raca'       => $dados['raca']
                    ]);

                    setFlash('sucesso', 'Pet cadastrado com sucesso!');
                    header('Location: index.php');
                    exit;

                } catch (PDOException $e) {
                    error_log('[Pet Novo] Erro DB: ' . $e->getMessage());
                    
                    // Se a coluna 'raca' não existe, tenta sem ela (compatibilidade)
                    if (strpos($e->getMessage(), 'raca') !== false) {
                        $stmt = $pdo->prepare('
                            INSERT INTO pet (id_cliente, nome_pet, especie) 
                            VALUES (:id_cliente, :nome_pet, :especie)
                        ');
                        
                        $stmt->execute([
                            ':id_cliente' => $dados['id_cliente'],
                            ':nome_pet'   => $dados['nome_pet'],
                            ':especie'    => $dados['especie']
                        ]);

                        setFlash('sucesso', 'Pet cadastrado com sucesso!');
                        header('Location: index.php');
                        exit;
                    } else {
                        $erro = 'Erro ao cadastrar pet no banco de dados.';
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
    <title>Novo Pet – VetSys</title>
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
        <i class="bi bi-plus-circle me-1"></i> Cadastro de Paciente
    </div>
</div>

<div class="container" style="max-width: 600px;">
    
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-4 text-secondary">
                <i class="bi bi-dog me-2"></i>Novo Pet
            </h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="novo.php">
                
                <div class="mb-3">
                    <label for="id_cliente" class="form-label fw-semibold">Tutor (Dono) <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_cliente" name="id_cliente" required>
                        <option value="">-- Selecione o Tutor --</option>
                        <?php foreach ($listaClientes as $cli): ?>
                            <option value="<?= $cli['id_cliente'] ?>" <?= ($dados['id_cliente'] == $cli['id_cliente']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cli['nome_cliente']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="nome_pet" class="form-label fw-semibold">Nome do Pet <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_pet" name="nome_pet" 
                           value="<?= htmlspecialchars($dados['nome_pet']) ?>" required
                           placeholder="Ex: Rex, Mia, Pichon">
                </div>

                <div class="mb-4">
                    <label for="especie" class="form-label fw-semibold">Espécie <span class="text-danger">*</span></label>
                    <select class="form-select" id="especie" name="especie" required>
                        <option value="">-- Selecione --</option>
                        <option value="Cachorro" <?= ($dados['especie'] === 'Cachorro') ? 'selected' : '' ?>>
                            <i class="bi bi-dog"></i> Cachorro
                        </option>
                        <option value="Gato" <?= ($dados['especie'] === 'Gato') ? 'selected' : '' ?>>
                            <i class="bi bi-cat"></i> Gato
                        </option>
                        <option value="Ave" <?= ($dados['especie'] === 'Ave') ? 'selected' : '' ?>>
                            <i class="bi bi-bird"></i> Ave
                        </option>
                        <option value="Reptil" <?= ($dados['especie'] === 'Reptil') ? 'selected' : '' ?>>
                            🦎 Réptil
                        </option>
                        <option value="Roedor" <?= ($dados['especie'] === 'Roedor') ? 'selected' : '' ?>>
                            🐭 Roedor
                        </option>
                        <option value="Outro" <?= ($dados['especie'] === 'Outro') ? 'selected' : '' ?>>
                            ❓ Outro
                        </option>
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
                        <i class="bi bi-check-lg me-1"></i> Salvar Pet
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ════════════════════════════════════════════════════════════════
// JavaScript: Dog API Integration
// ════════════════════════════════════════════════════════════════

const especieSelect = document.getElementById('especie');
const racaGroup = document.getElementById('racaGroup');
const racaSelect = document.getElementById('raca');
const fotoPreview = document.getElementById('fotoPreview');
const previewLoading = document.getElementById('previewLoading');

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
    
    fetch('novo.php?ajax=racas')
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
                    racaSelect.appendChild(option);
                }
                
                labelSmall.innerHTML = '';
                racaSelect.disabled = false;
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
    
    fetch('novo.php?ajax=foto&raca=' + encodeURIComponent(raca))
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

// Se já tinha "Cachorro" selecionado no carregamento da página
if (especieSelect.value === 'Cachorro') {
    racaGroup.classList.add('visible');
    if (racaSelect.options.length <= 1) {
        carregarRacas();
    }
}
</script>
</body>
</html>
