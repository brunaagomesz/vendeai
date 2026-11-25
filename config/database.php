<?php
// Conexão PDO com o MySQL (XAMPP local padrão).
// Para usar outra senha/usuário SEM mexer neste arquivo, crie config/database.local.php
// (ele é ignorado pelo Git) redefinindo $host, $dbname, $user e/ou $password.

$host = '127.0.0.1';
$dbname = 'vendeai_db';
$user = 'root';
$password = ''; // Padrão comum no XAMPP local

if (is_file(__DIR__ . '/database.local.php')) {
    require __DIR__ . '/database.local.php';
}

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
    // Mesmo fuso do PHP (ver includes/functions.php), para NOW()/CURRENT_TIMESTAMP baterem.
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);

    // Mensagem que ajuda a resolver, sem mostrar detalhes internos.
    $codigo = (int) ($e->errorInfo[1] ?? $e->getCode());
    switch ($codigo) {
        case 1049:
            $dica = "O banco \"$dbname\" não existe. Importe database/schema.sql (e database/seed.sql) no phpMyAdmin.";
            break;
        case 1045:
            $dica = 'Usuário ou senha do MySQL incorretos. Ajuste em config/database.local.php.';
            break;
        case 2002:
            $dica = 'O MySQL não está ligado. Inicie o MySQL no painel do XAMPP.';
            break;
        default:
            $dica = 'Confira se o MySQL está ligado no XAMPP.';
    }
    exit('Não foi possível conectar ao banco de dados. ' . htmlspecialchars($dica, ENT_QUOTES, 'UTF-8'));
}
