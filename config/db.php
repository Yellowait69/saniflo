<?php
// config/db.php - Version Alwaysdata
$host = 'mysql-saniflo-demo.alwaysdata.net'; // REMPLACEZ par l'hôte indiqué dans votre panel MySQL
$db   = 'saniflo-demo_saniflo_db';       // Le nom exact de la base créée
$user = 'saniflo-demo_saniflo_user';     // Votre utilisateur MySQL
$pass = 'Maxpanpan02'; // Le mot de passe choisi
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

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

// --- CORRECTION : ON RETOURNE L'OBJET PDO ---
return $pdo;
?>