<?php
// db.php
$host = '127.0.0.1'; // On remplace 'localhost' par l'IP pour forcer le TCP/IP
$port = '3307';      // <--- AJOUT DU PORT
$db   = 'saniflo_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// On ajoute ";port=$port" dans le DSN
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>