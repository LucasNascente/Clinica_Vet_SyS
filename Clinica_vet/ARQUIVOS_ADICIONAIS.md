# .gitignore - Arquivos a não versionar

# Configuração sensível
config.php
.env
.env.local
database.php

# Logs
*.log
logs/
error.log

# Cache
cache/
*.cache
temp/

# Sessions
sessions/
tmp/

# Dependências
vendor/
node_modules/
composer.lock

# IDE
.vscode/
.idea/
*.swp
*.swo
*~
.DS_Store

# Upload temporário
uploads/temp/
uploads/*.tmp

# Bancos de dados locais
*.sqlite
*.db
*.sqlite3

# Arquivos do SO
Thumbs.db
.DS_Store
.AppleDouble
.LSOverride

# Backup
*.bak
*.backup
*~.php

# Compilados
*.class
*.o
*.pyc

---

# config.php - Template de Configuração
<?php
/**
 * Arquivo de Configuração do Sistema de Clínica Veterinária
 * 
 * IMPORTANTE: Este arquivo contém informações sensíveis.
 * NUNCA commite este arquivo no repositório com dados reais!
 * Use um arquivo .env ou um config.local.php para dados sensíveis.
 * 
 * @package Clinica_Vet
 * @version 1.0
 */

// ============================================
// AMBIENTE
// ============================================
define('ENVIRONMENT', 'development'); // development, staging, production
define('DEBUG_MODE', true); // false em produção


// ============================================
// BANCO DE DADOS
// ============================================

// Configuração para Desenvolvimento
if (ENVIRONMENT == 'development') {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', 'password');
    define('DB_NAME', 'clinica_vet');
    define('DB_PORT', 3306);
    define('DB_CHARSET', 'utf8mb4');
}

// Configuração para Produção
else if (ENVIRONMENT == 'production') {
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_USER', $_ENV['DB_USER'] ?? '');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    define('DB_NAME', $_ENV['DB_NAME'] ?? '');
    define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);
    define('DB_CHARSET', 'utf8mb4');
}


// ============================================
// APLICAÇÃO
// ============================================

define('APP_NAME', 'Sistema de Clínica Veterinária');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/Clinica_Vet_SyS');
define('APP_TIMEZONE', 'America/Sao_Paulo');

// Caminhos
define('BASE_PATH', dirname(__FILE__) . '/..');
define('VIEW_PATH', BASE_PATH . '/views/');
define('CONTROLLER_PATH', BASE_PATH . '/controllers/');
define('MODEL_PATH', BASE_PATH . '/models/');
define('ASSETS_PATH', APP_URL . '/assets/');
define('LOGS_PATH', BASE_PATH . '/logs/');


// ============================================
// SEGURANÇA
// ============================================

// Chave para CSRF tokens (gere uma string aleatória forte)
define('CSRF_KEY', 'sua_chave_csrf_muito_segura_aqui');

// Tempo de sessão (em segundos)
define('SESSION_TIMEOUT', 1800); // 30 minutos

// Número máximo de tentativas de login
define('MAX_LOGIN_ATTEMPTS', 5);

// Tempo de bloqueio após máximo de tentativas (em segundos)
define('LOCKOUT_TIME', 900); // 15 minutos

// Salt para hash de senhas (use password_hash() em vez disso)
// define('PASSWORD_SALT', 'sua_salt_aqui');


// ============================================
// EMAIL
// ============================================

define('MAIL_ENABLED', false); // true para ativar
define('MAIL_HOST', 'smtp.seu-email.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'seu-email@exemplo.com');
define('MAIL_PASS', 'sua-senha-email');
define('MAIL_FROM', 'noreply@clinicavet.com');
define('MAIL_FROM_NAME', 'Clínica Veterinária');


// ============================================
// CONFIGURAÇÕES GERAIS
// ============================================

// Idioma padrão
define('DEFAULT_LANGUAGE', 'pt_BR');

// Formato de data
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');

// Items por página em paginação
define('ITEMS_PER_PAGE', 10);

// Upload
define('MAX_UPLOAD_SIZE', 5242880); // 5MB em bytes
define('ALLOWED_UPLOAD_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);


// ============================================
// LOGS
// ============================================

define('LOG_ERRORS', true);
define('LOG_QUERIES', ENVIRONMENT == 'development');
define('LOG_LEVEL', 'info'); // debug, info, warning, error


// ============================================
// FUNCIONALIDADES
// ============================================

// Recursos habilitados
define('FEATURE_NOTIFICATIONS', true);
define('FEATURE_SMS', false);
define('FEATURE_EXPORT', true);
define('FEATURE_REPORTS', true);


// ============================================
// CACHE
// ============================================

define('CACHE_ENABLED', ENVIRONMENT != 'development');
define('CACHE_TTL', 3600); // Time to live em segundos


// ============================================
// PAPÉIS DE USUÁRIO
// ============================================

define('ROLE_ADMIN', 'admin');
define('ROLE_DOCTOR', 'doctor');
define('ROLE_RECEPTIONIST', 'receptionist');

$ROLES_PERMISSIONS = [
    'admin' => [
        'dashboard' => true,
        'users' => true,
        'reports' => true,
        'settings' => true,
        'appointments' => true,
        'patients' => true,
    ],
    'doctor' => [
        'dashboard' => true,
        'appointments' => true,
        'patients' => true,
        'consultations' => true,
        'reports' => false,
        'users' => false,
    ],
    'receptionist' => [
        'dashboard' => false,
        'appointments' => true,
        'patients' => true,
        'consultations' => false,
        'reports' => false,
        'users' => false,
    ],
];


// ============================================
// INICIALIZAÇÃO
// ============================================

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . 'error.log');
}

// Session configuration
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
session_set_cookie_params([
    'lifetime' => SESSION_TIMEOUT,
    'path' => '/',
    'domain' => $_SERVER['SERVER_NAME'] ?? 'localhost',
    'secure' => (ENVIRONMENT === 'production'), // true apenas em HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
