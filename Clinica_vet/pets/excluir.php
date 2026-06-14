<?php
// =============================================================
//  VetSys – Excluir Pet
//  Arquivo : pets/excluir.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$id_pet = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_pet) {
    setFlash('erro', 'Ação inválida.');
    header('Location: index.php');
    exit;
}

try {
    $pdo = getConexao();
    
    // Busca o nome do pet antes de apagar, para a mensagem flash
    $stmtSelect = $pdo->prepare('SELECT nome_pet FROM pet WHERE id_pet = :id');
    $stmtSelect->execute([':id' => $id_pet]);
    $nome = $stmtSelect->fetchColumn();

    if ($nome) {
        $stmtDelete = $pdo->prepare('DELETE FROM pet WHERE id_pet = :id');
        $stmtDelete->execute([':id' => $id_pet]);

        setFlash('sucesso', "O pet <strong>" . htmlspecialchars($nome) . "</strong> foi removido do sistema.");
    } else {
        setFlash('erro', 'Pet não encontrado.');
    }

} catch (PDOException $e) {
    // Código 23000 também abrange falhas de Foreign Key Restraint
    if ($e->getCode() == 23000) {
        setFlash('erro', '<strong>Não é possível excluir este pet.</strong> Ele possui consultas registradas no histórico clínico.');
    } else {
        setFlash('erro', 'Erro inesperado ao tentar excluir o pet.');
    }
}

// Redireciona de volta para a lista
header('Location: index.php');
exit;