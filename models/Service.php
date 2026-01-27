<?php
class Service {
    public static function getAll($pdo) {
        $stmt = $pdo->query("SELECT * FROM services");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}