<?php
class Project {
    /**
     * Récupère tous les projets pour le portfolio avec leurs catégories et types d'intervention.
     * Triés par date de fin (du plus récent au plus ancien).
     */
    public static function getAll($pdo) {
        $sql = "SELECT p.*, 
                       pc.name AS category_name, 
                       pc.slug AS category_slug, 
                       it.name AS type_name,
                       it.slug AS type_slug
                FROM projects p
                LEFT JOIN project_categories pc ON p.category_id = pc.id
                LEFT JOIN intervention_types it ON p.type_intervention_id = it.id
                ORDER BY p.date_completion DESC, p.id DESC";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>