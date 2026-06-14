<?php
// =============================================================
//  VetSys – Conexão com o Banco de Dados
//  Arquivo : conexao.php
//  Descrição: Cria a conexão PDO com o MySQL e define constantes
//             globais do sistema.
// =============================================================

// ── Configurações do banco ────────────────────────────────────
define('DB_HOST', '127.0.0.1:3306');
define('DB_NAME', 'clinica');
define('DB_USER', 'root');
define('DB_PASS', 'Lucasnascente8@');
define('DB_CHARSET', 'utf8mb4');

// ── Configurações gerais do sistema ──────────────────────────
define('SISTEMA_NOME', 'VetSys');
define('SISTEMA_VERSAO', '1.0.0');

// ── Função de conexão (retorna instância PDO) ─────────────────
function getConexao(): PDO
{
    static $pdo = null;          // mantém uma única instância (singleton)

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $opcoes = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // lança exceções em erros
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // retorna arrays associativos
            PDO::ATTR_EMULATE_PREPARES   => false,                   // prepared statements reais
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
        } catch (PDOException $e) {
            // Em produção, nunca exponha detalhes do erro ao utilizador
            error_log('[VetSys] Falha na conexão: ' . $e->getMessage());
            die('<p style="font-family:sans-serif;color:#c0392b;padding:20px;">
                    <strong>Erro:</strong> Não foi possível conectar ao banco de dados.
                    Contacte o administrador do sistema.
                 </p>');
        }
    }

    return $pdo;
}