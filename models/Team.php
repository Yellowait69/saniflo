<?php
class Team {
    public static function getAll($pdo) {
        $stmt = $pdo->query("SELECT * FROM team");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}