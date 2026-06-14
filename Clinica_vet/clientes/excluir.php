<?php
// =============================================================
//  VetSys – Excluir Cliente
//  Arquivo : clientes/excluir.php
// =============================================================
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/sessao.php';

exigePerfil(['Gerente', 'Recepcionista']);

$id_cliente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_cliente) {
    setFlash('erro', 'Ação inválida.');
    header('Location: index.php');
    exit;
}

try {
    $pdo = getConexao();
    
    // Opcional: Buscar o nome do cliente antes de excluir para uma mensagem mais amigável
    $stmtSelect = $pdo->prepare('SELECT nome_cliente FROM cliente WHERE id_cliente = :id');
    $stmtSelect->execute([':id' => $id_cliente]);
    $nome = $stmtSelect->fetchColumn();

    if ($nome) {
        // Exclui o cliente. 
        // IMPORTANTE: Como a sua tabela `pet` foi criada com `ON DELETE CASCADE`,
        // o banco de dados apagará automaticamente todos os pets associados a este cliente.
        $stmtDelete = $pdo->prepare('DELETE FROM cliente WHERE id_cliente = :id');
        $stmtDelete->execute([':id' => $id_cliente]);

        setFlash('sucesso', "O cliente <strong>" . htmlspecialchars($nome) . "</strong> e seus pets associados foram removidos.");
    } else {
        setFlash('erro', 'Cliente não encontrado.');
    }

} catch (PDOException $e) {
    // Caso haja alguma restrição não prevista (ex: restrição na tabela de contas a pagar, se você criar no futuro)
    setFlash('erro', 'Não foi possível excluir o cliente. Verifique se ele possui dependências no sistema.');
}

// Redireciona de volta para a lista
header('Location: index.php');
exit;