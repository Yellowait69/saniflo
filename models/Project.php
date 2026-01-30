<?php
class Project {
    /**
     * Récupère tous les projets pour le portfolio.
     * Triés par date de fin (du plus récent au plus ancien).
     */
    public static function getAll($pdo) {
        // SELECT * va récupérer l'ID, le titre, la ville, la description ET l'image_url
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY date_completion DESC, id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}