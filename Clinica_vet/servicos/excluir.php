<?php
// =============================================================
//  VetSys – Excluir Serviço
//  Arquivo : servicos/excluir.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$id_servico = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_servico) {
    setFlash('erro', 'Ação inválida.');
    header('Location: index.php');
    exit;
}

try {
    $pdo = getConexao();
    
    // Busca a descrição do serviço para a mensagem de sucesso
    $stmtSelect = $pdo->prepare('SELECT descricao FROM servico WHERE id_servico = :id');
    $stmtSelect->execute([':id' => $id_servico]);
    $descricao = $stmtSelect->fetchColumn();

    if ($descricao) {
        $stmtDelete = $pdo->prepare('DELETE FROM servico WHERE id_servico = :id');
        $stmtDelete->execute([':id' => $id_servico]);

        setFlash('sucesso', "O serviço <strong>" . htmlspecialchars($descricao) . "</strong> foi removido do catálogo.");
    } else {
        setFlash('erro', 'Serviço não encontrado.');
    }

} catch (PDOException $e) {
    // Tratamento específico para restrição de chave estrangeira (RESTRICT)
    if ($e->getCode() == 23000) {
        setFlash('erro', '<strong>Não é possível excluir este serviço.</strong> Ele já está vinculado a consultas registradas no histórico financeiro.');
    } else {
        setFlash('erro', 'Erro inesperado ao tentar excluir o serviço.');
    }
}

// Redireciona de volta para a lista
header('Location: index.php');
exit;