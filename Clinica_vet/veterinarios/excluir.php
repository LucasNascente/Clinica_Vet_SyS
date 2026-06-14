<?php
// =============================================================
//  VetSys – Excluir Veterinário
//  Arquivo : veterinarios/excluir.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$id_veterinario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_veterinario) {
    setFlash('erro', 'Ação inválida.');
    header('Location: index.php');
    exit;
}

try {
    $pdo = getConexao();
    
    // Busca o nome para a mensagem de confirmação
    $stmtSelect = $pdo->prepare('SELECT nome_vet FROM veterinario WHERE id_veterinario = :id');
    $stmtSelect->execute([':id' => $id_veterinario]);
    $nome = $stmtSelect->fetchColumn();

    if ($nome) {
        $stmtDelete = $pdo->prepare('DELETE FROM veterinario WHERE id_veterinario = :id');
        $stmtDelete->execute([':id' => $id_veterinario]);

        setFlash('sucesso', "O(A) profissional <strong>" . htmlspecialchars($nome) . "</strong> foi removido(a) do sistema.");
    } else {
        setFlash('erro', 'Profissional não encontrado.');
    }

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        setFlash('erro', '<strong>Não é possível remover este profissional.</strong> Ele possui consultas ou agendamentos atrelados ao seu nome.');
    } else {
        setFlash('erro', 'Erro inesperado ao tentar remover o veterinário.');
    }
}

header('Location: index.php');
exit;