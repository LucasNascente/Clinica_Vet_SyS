<?php
// =============================================================
//  VetSys – Editar Veterinário
//  Arquivo : veterinarios/editar.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$erro = '';
$pdo = getConexao();

// ── 1. Verifica o ID do Veterinário na URL ────────────────────
$id_veterinario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_veterinario) {
    setFlash('erro', 'ID de profissional inválido.');
    header('Location: index.php');
    exit;
}

// ── 2. Busca os dados atuais do veterinário ───────────────────
$stmt = $pdo->prepare('SELECT * FROM veterinario WHERE id_veterinario = :id LIMIT 1');
$stmt->execute([':id' => $id_veterinario]);
$vet = $stmt->fetch();

if (!$vet) {
    setFlash('erro', 'Profissional não encontrado.');
    header('Location: index.php');
    exit;
}

// ── 3. Busca contas de e-mail livres OU que já pertençam a ele 
$queryUsuarios = "
    SELECT id_usuario, email 
    FROM usuario 
    WHERE perfil = 'Veterinario' 
      AND (
        id_usuario NOT IN (SELECT id_usuario FROM veterinario WHERE id_usuario IS NOT NULL)
        OR id_usuario = :id_usuario_atual
      )
";
$stmtUsuarios = $pdo->prepare($queryUsuarios);
$stmtUsuarios->execute([':id_usuario_atual' => $vet['id_usuario']]);
$usuariosDisponiveis = $stmtUsuarios->fetchAll();

// ── 4. Processa a atualização via POST ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_vet   = trim($_POST['nome_vet'] ?? '');
    $crmv       = trim($_POST['crmv'] ?? '');
    $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);

    if (empty($nome_vet) || empty($crmv)) {
        $erro = 'O nome e o CRMV são obrigatórios.';
        $vet['nome_vet']   = $nome_vet;
        $vet['crmv']       = $crmv;
        $vet['id_usuario'] = $id_usuario;
    } else {
        try {
            $stmtUpdate = $pdo->prepare('
                UPDATE veterinario 
                SET nome_vet = :nome_vet, crmv = :crmv, id_usuario = :id_usuario 
                WHERE id_veterinario = :id_vet
            ');
            
            $stmtUpdate->execute([
                ':nome_vet'   => $nome_vet,
                ':crmv'       => $crmv,
                ':id_usuario' => $id_usuario ?: null,
                ':id_vet'     => $id_veterinario
            ]);

            setFlash('sucesso', 'Cadastro do profissional atualizado com sucesso!');
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = 'Já existe outro profissional cadastrado com este CRMV ou Conta de Acesso.';
            } else {
                $erro = 'Erro ao atualizar dados no banco de dados.';
            }
            $vet['nome_vet']   = $nome_vet;
            $vet['crmv']       = $crmv;
            $vet['id_usuario'] = $id_usuario;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Veterinário – VetSys</title>
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
        <i class="bi bi-pencil-square me-1"></i> Edição de Profissional
    </div>
</div>

<div class="container" style="max-width: 600px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-4 text-secondary">Editar dados do Médico</h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="editar.php?id=<?= $id_veterinario ?>">
                
                <div class="mb-3">
                    <label for="nome_vet" class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_vet" name="nome_vet" 
                           value="<?= htmlspecialchars($vet['nome_vet']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="crmv" class="form-label fw-semibold">Registro CRMV <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="crmv" name="crmv" 
                           value="<?= htmlspecialchars($vet['crmv']) ?>" required>
                </div>

                <div class="mb-4">
                    <label for="id_usuario" class="form-label fw-semibold">Vincular Conta de Acesso (Login)</label>
                    <select class="form-select" id="id_usuario" name="id_usuario">
                        <option value="">-- Sem conta vinculada --</option>
                        <?php foreach ($usuariosDisponiveis as $ud): ?>
                            <option value="<?= $ud['id_usuario'] ?>" <?= ($vet['id_usuario'] == $ud['id_usuario']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ud['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn text-white fw-semibold" style="background-color: #16a085;">
                        <i class="bi bi-arrow-repeat me-1"></i> Atualizar Dados
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>