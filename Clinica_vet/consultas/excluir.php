<?php
// =============================================================
//  VetSys – Excluir Consulta
//  Arquivo : consultas/excluir.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$id_consulta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_consulta) {
    setFlash('erro', 'Ação inválida.');
    header('Location: index.php');
    exit;
}

try {
    $pdo = getConexao();
    
    // Deleta a consulta. Os itens atrelados (serviços) serão apagados 
    // automaticamente pelo MySQL graças ao ON DELETE CASCADE.
    $stmtDelete = $pdo->prepare('DELETE FROM consulta WHERE id_consulta = :id');
    $stmtDelete->execute([':id' => $id_consulta]);

    setFlash('sucesso', "A consulta #{$id_consulta} foi cancelada e removida do sistema.");

} catch (PDOException $e) {
    setFlash('erro', 'Não foi possível excluir a consulta. Ocorreu um erro interno.');
}

header('Location: index.php');
exit;