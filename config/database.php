<?php
// Conexão PDO com o MySQL (XAMPP local padrão).
// Se você definiu senha para o usuário root, ajuste $password.

$host = 'localhost';
$dbname = 'vendeai_db';
$user = 'root';
$password = ''; // Padrão comum no XAMPP local

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Não foi possível conectar ao banco de dados.');
}
