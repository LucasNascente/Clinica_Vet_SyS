<?php
// =============================================================
//  VetSys – Novo Veterinário
//  Arquivo : veterinarios/novo.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$erro = '';
$pdo = getConexao();

// Busca apenas usuários com perfil 'Veterinario' que AINDA NÃO estão na tabela veterinario
$queryUsuariosLivres = "
    SELECT id_usuario, email 
    FROM usuario 
    WHERE perfil = 'Veterinario' 
      AND id_usuario NOT IN (SELECT id_usuario FROM veterinario WHERE id_usuario IS NOT NULL)
";
$stmtUsuarios = $pdo->query($queryUsuariosLivres);
$usuariosLivres = $stmtUsuarios->fetchAll();

$dados = [
    'nome_vet'   => '',
    'crmv'       => '',
    'id_usuario' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome_vet']   = trim($_POST['nome_vet'] ?? '');
    $dados['crmv']       = trim($_POST['crmv'] ?? '');
    $dados['id_usuario'] = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);

    if (empty($dados['nome_vet']) || empty($dados['crmv'])) {
        $erro = 'O nome e o CRMV são obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO veterinario (nome_vet, crmv, id_usuario) 
                VALUES (:nome_vet, :crmv, :id_usuario)
            ');
            
            $stmt->execute([
                ':nome_vet'   => $dados['nome_vet'],
                ':crmv'       => $dados['crmv'],
                ':id_usuario' => $dados['id_usuario'] ?: null // Permite null caso não escolha usuário
            ]);

            setFlash('sucesso', 'Veterinário cadastrado com sucesso!');
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = 'Já existe um veterinário cadastrado com este CRMV ou Usuário.';
            } else {
                $erro = 'Erro ao cadastrar veterinário no banco de dados.';
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
    <title>Novo Veterinário – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .topbar { background: #1abc9c; color: white; padding: 1rem; margin-bottom: 2rem; }
        .topbar a { color: white; text-decoration: none; }
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
        <i class="bi bi-person-plus-fill me-1"></i> Cadastro de Profissional
    </div>
</div>

<div class="container" style="max-width: 600px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-4 text-secondary">Novo Veterinário</h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="novo.php">
                
                <div class="mb-3">
                    <label for="nome_vet" class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_vet" name="nome_vet" 
                           placeholder="Ex: Dra. Amanda Silva"
                           value="<?= htmlspecialchars($dados['nome_vet']) ?>" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="crmv" class="form-label fw-semibold">Registro CRMV <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="crmv" name="crmv" 
                           placeholder="Ex: CRMV-SP 12345"
                           value="<?= htmlspecialchars($dados['crmv']) ?>" required>
                </div>

                <div class="mb-4">
                    <label for="id_usuario" class="form-label fw-semibold">Vincular Conta de Acesso (Login)</label>
                    <select class="form-select" id="id_usuario" name="id_usuario">
                        <option value="">-- Nenhum / Cadastrar login depois --</option>
                        <?php foreach ($usuariosLivres as $ul): ?>
                            <option value="<?= $ul['id_usuario'] ?>" <?= ($dados['id_usuario'] == $ul['id_usuario']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ul['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Mostra apenas contas com perfil "Veterinario" que não estão em uso.</div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn text-white fw-semibold" style="background-color: #16a085;">
                        <i class="bi bi-check-lg me-1"></i> Salvar Profissional
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>