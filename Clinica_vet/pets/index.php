<?php
// =============================================================
//  VetSys – Listagem de Pets
//  Arquivo : pets/index.php
//  ⭐ VERSÃO COM FOTOS DOS PETS (Dog API Integration)
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../includes/dog_api_helper.php';

exigeLogin(); // Todos os perfis podem ver os pets

$pdo = getConexao();
$perfil = perfilAtual();

// Busca os pets e faz um JOIN para pegar o nome do tutor
$query = '
    SELECT p.id_pet, p.nome_pet, p.especie, p.raca, c.nome_cliente 
    FROM pet p
    JOIN cliente c ON p.id_cliente = c.id_cliente
    ORDER BY p.nome_pet ASC
';
$stmt = $pdo->query($query);
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pets – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #e67e22; color: white; padding: 1rem; margin-bottom: 2rem; }
        .topbar a { color: white; text-decoration: none; }
        
        /* Cards de Pet */
        .pet-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .pet-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        
        .pet-image-container {
            position: relative;
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .pet-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .pet-image-placeholder {
            font-size: 3rem;
            color: white;
            opacity: 0.7;
        }
        
        .pet-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .pet-info {
            padding: 1.2rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .pet-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .pet-detail {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 0.3rem;
        }
        
        .pet-tutor {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #ecf0f1;
            font-size: 0.85rem;
            color: #34495e;
        }
        
        .pet-actions {
            padding: 1rem;
            background: #f9f9f9;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .pet-actions .btn {
            flex: 1;
            font-size: 0.85rem;
        }
        
        .list-view .pet-card {
            display: flex;
            flex-direction: row;
            margin-bottom: 1rem;
        }
        
        .list-view .pet-image-container {
            width: 150px;
            min-width: 150px;
            height: 150px;
        }
        
        .list-view .pet-info {
            flex-grow: 1;
        }
        
        .view-toggle {
            display: flex;
            gap: 0.5rem;
        }
        
        .view-toggle .btn {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
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
        <i class="bi bi-bug-fill me-1"></i> Gestão de Pets
    </div>
</div>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-secondary">Lista de Pacientes</h2>
        
        <div class="d-flex gap-2 align-items-center">
            <!-- Toggle entre visualização em cards e lista -->
            <div class="view-toggle" id="viewToggle">
                <button class="btn btn-outline-secondary btn-sm active" data-view="cards" title="Visualizar em cards">
                    <i class="bi bi-grid-3x2-gap"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm" data-view="list" title="Visualizar em lista">
                    <i class="bi bi-list-ul"></i>
                </button>
            </div>
            
            <?php if (in_array($perfil, ['Gerente', 'Recepcionista'])): ?>
                <a href="novo.php" class="btn btn-warning text-white fw-semibold">
                    <i class="bi bi-plus-lg me-1"></i> Novo Pet
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php $sucesso = getFlash('sucesso'); if ($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $sucesso ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- VISUALIZAÇÃO EM CARDS -->
    <div id="cardsView" class="cards-view">
        <?php if (empty($pets)): ?>
            <div class="alert alert-info text-center py-5">
                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                <p class="mt-3 mb-0">Nenhum pet cadastrado ainda.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($pets as $p): 
                    // Busca foto aleatória do cachorro (se houver raça)
                    $url_foto = '';
                    if ($p['especie'] === 'Cachorro' && !empty($p['raca'])) {
                        $url_foto = obterFotoRacaAPI($p['raca']);
                    }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card pet-card">
                        <!-- Imagem do Pet -->
                        <div class="pet-image-container">
                            <?php if ($url_foto): ?>
                                <img src="<?= htmlspecialchars($url_foto) ?>" alt="<?= htmlspecialchars($p['nome_pet']) ?>">
                            <?php else: ?>
                                <div class="pet-image-placeholder">
                                    <?php 
                                    // Emoji por espécie
                                    $emojis = [
                                        'Cachorro' => '🐕',
                                        'Gato' => '🐱',
                                        'Ave' => '🦜',
                                        'Reptil' => '🦎',
                                        'Roedor' => '🐭',
                                        'Outro' => '🐾'
                                    ];
                                    echo $emojis[$p['especie']] ?? '🐾';
                                    ?>
                                </div>
                            <?php endif; ?>
                            <span class="pet-badge"><?= htmlspecialchars($p['especie']) ?></span>
                        </div>
                        
                        <!-- Informações -->
                        <div class="pet-info">
                            <div class="pet-name"><?= htmlspecialchars($p['nome_pet']) ?></div>
                            
                            <?php if ($p['especie'] === 'Cachorro' && !empty($p['raca'])): ?>
                                <div class="pet-detail">
                                    <i class="bi bi-tag me-1"></i>
                                    Raça: <?= htmlspecialchars($p['raca']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="pet-tutor">
                                <i class="bi bi-person-circle me-1"></i>
                                <strong>Tutor:</strong> <?= htmlspecialchars($p['nome_cliente']) ?>
                            </div>
                        </div>
                        
                        <!-- Ações -->
                        <?php if (in_array($perfil, ['Gerente', 'Recepcionista'])): ?>
                            <div class="pet-actions">
                                <a href="editar.php?id=<?= $p['id_pet'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <a href="excluir.php?id=<?= $p['id_pet'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir o pet <?= htmlspecialchars($p['nome_pet']) ?>?');">
                                    <i class="bi bi-trash"></i> Excluir
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="pet-actions">
                                <button class="btn btn-sm btn-light" disabled>
                                    <i class="bi bi-eye"></i> Apenas visualização
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- VISUALIZAÇÃO EM LISTA -->
    <div id="listView" class="list-view" style="display: none;">
        <?php if (empty($pets)): ?>
            <div class="alert alert-info text-center py-5">
                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                <p class="mt-3 mb-0">Nenhum pet cadastrado ainda.</p>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0">
                <?php foreach ($pets as $p): 
                    $url_foto = '';
                    if ($p['especie'] === 'Cachorro' && !empty($p['raca'])) {
                        $url_foto = obterFotoRacaAPI($p['raca']);
                    }
                ?>
                <div class="pet-card mb-0">
                    <!-- Imagem do Pet -->
                    <div class="pet-image-container">
                        <?php if ($url_foto): ?>
                            <img src="<?= htmlspecialchars($url_foto) ?>" alt="<?= htmlspecialchars($p['nome_pet']) ?>">
                        <?php else: ?>
                            <div class="pet-image-placeholder">
                                <?php 
                                $emojis = [
                                    'Cachorro' => '🐕',
                                    'Gato' => '🐱',
                                    'Ave' => '🦜',
                                    'Reptil' => '🦎',
                                    'Roedor' => '🐭',
                                    'Outro' => '🐾'
                                ];
                                echo $emojis[$p['especie']] ?? '🐾';
                                ?>
                            </div>
                        <?php endif; ?>
                        <span class="pet-badge"><?= htmlspecialchars($p['especie']) ?></span>
                    </div>
                    
                    <!-- Informações -->
                    <div class="pet-info">
                        <div>
                            <div class="pet-name"><?= htmlspecialchars($p['nome_pet']) ?></div>
                            
                            <?php if ($p['especie'] === 'Cachorro' && !empty($p['raca'])): ?>
                                <div class="pet-detail">
                                    <i class="bi bi-tag me-1"></i>
                                    Raça: <?= htmlspecialchars($p['raca']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="pet-tutor">
                            <i class="bi bi-person-circle me-1"></i>
                            <strong>Tutor:</strong> <?= htmlspecialchars($p['nome_cliente']) ?>
                        </div>
                    </div>
                    
                    <!-- Ações -->
                    <div class="pet-actions">
                        <?php if (in_array($perfil, ['Gerente', 'Recepcionista'])): ?>
                            <a href="editar.php?id=<?= $p['id_pet'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <a href="excluir.php?id=<?= $p['id_pet'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir o pet <?= htmlspecialchars($p['nome_pet']) ?>?');">
                                <i class="bi bi-trash"></i> Excluir
                            </a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-light" disabled>
                                <i class="bi bi-eye"></i> Apenas visualização
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle entre visualizações
document.querySelectorAll('#viewToggle .btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const view = this.dataset.view;
        
        // Remove active de todos os botões
        document.querySelectorAll('#viewToggle .btn').forEach(b => b.classList.remove('active'));
        
        // Adiciona active ao clicado
        this.classList.add('active');
        
        // Mostra/esconde as visualizações
        if (view === 'cards') {
            document.getElementById('cardsView').style.display = 'block';
            document.getElementById('listView').style.display = 'none';
        } else {
            document.getElementById('cardsView').style.display = 'none';
            document.getElementById('listView').style.display = 'block';
        }
    });
});
</script>
</body>
</html>
