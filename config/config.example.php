<?php
// Exemplo de configuração geral
// Copie este arquivo para config.php e ajuste as configurações

// Configurações gerais
define('SITE_NAME', 'Tempero Secreto');
define('SITE_URL', 'http://localhost/tempero-secreto');

// Configurações de upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Configurações de paginação
define('ITEMS_PER_PAGE', 10);

// Configurações de segurança
define('HASH_COST', 12); // Para password_hash()

// Configurações de e-mail
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'seu_email@gmail.com');
define('MAIL_PASSWORD', 'sua_senha_de_app');
define('MAIL_FROM', 'seu_email@gmail.com');
define('MAIL_FROM_NAME', SITE_NAME);

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir arquivos necessários
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php'; 