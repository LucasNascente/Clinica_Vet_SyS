<?php
// =============================================================
//  VetSys – Helpers de Sessão e Segurança
//  Arquivo : includes/sessao.php
//  Descrição: Funções reutilizáveis para login, logout, guarda
//             de sessão e controle de acesso por perfil.
// =============================================================

// Inicia a sessão de forma segura (chama apenas uma vez)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // expira ao fechar o browser
        'path'     => '/',
        'secure'   => false,      // mude para true em HTTPS (produção)
        'httponly' => true,       // bloqueia acesso via JavaScript
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── Verifica se o utilizador está autenticado ─────────────────
function estaLogado(): bool
{
    return isset($_SESSION['id_usuario'], $_SESSION['perfil']);
}

// ── Exige login; redireciona se não estiver autenticado ───────
function exigeLogin(): void
{
    if (!estaLogado()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

// ── Exige um perfil específico (ou array de perfis) ───────────
// Exemplo de uso: exigePerfil('Gerente')
//                 exigePerfil(['Gerente', 'Recepcionista'])
function exigePerfil(string|array $perfis): void
{
    exigeLogin();

    $perfis = (array) $perfis;

    if (!in_array($_SESSION['perfil'], $perfis, true)) {
        // Acesso negado → volta ao painel com mensagem de erro
        $_SESSION['flash_erro'] = 'Acesso negado. Você não tem permissão para esta página.';
        header('Location: ' . BASE_URL . 'painel.php');
        exit;
    }
}

// ── Retorna o perfil do utilizador logado ─────────────────────
function perfilAtual(): string
{
    return $_SESSION['perfil'] ?? '';
}

// ── Retorna o nome do utilizador logado ───────────────────────
function nomeAtual(): string
{
    return $_SESSION['nome'] ?? 'Utilizador';
}

// ── Grava mensagem flash (exibida uma única vez) ───────────────
function setFlash(string $tipo, string $mensagem): void
{
    $_SESSION['flash_' . $tipo] = $mensagem;
}

// ── Lê e apaga mensagem flash ─────────────────────────────────
function getFlash(string $tipo): string
{
    $msg = $_SESSION['flash_' . $tipo] ?? '';
    unset($_SESSION['flash_' . $tipo]);
    return $msg;
}

// ── Constante base da URL (ajuste conforme seu ambiente) ──────
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Clinica_vet/');
}
