<?php
// =============================================================
//  VetSys – Logout
//  Arquivo : auth/logout.php
// =============================================================
require_once __DIR__ . '/../includes/sessao.php';

// Apaga todos os dados da sessão
$_SESSION = [];

// Apaga o cookie de sessão
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Redireciona para o login com mensagem de confirmação
session_start();
$_SESSION['flash_sucesso'] = 'Você saiu com segurança. Até logo!';

header('Location: ' . BASE_URL . 'auth/login.php');
exit;
