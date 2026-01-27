<?php
class Certification {
    public static function getAll($pdo) {
        $stmt = $pdo->query("SELECT * FROM certifications ORDER BY region ASC, title ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}