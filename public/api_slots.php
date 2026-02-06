<?php
// public/api_slots.php
header('Content-Type: application/json');

// On inclut la logique métier (le fichier qui contient votre classe PlanningLogic)
require_once __DIR__ . '/../services/PlanningLogic.php';

// Récupération des paramètres envoyés par le Javascript (scripts.js)
$date = $_GET['date'] ?? null;
$zip = $_GET['zip'] ?? null;

// Vérification de base
if (!$date || !$zip) {
    echo json_encode(['error' => 'Données manquantes (Date ou Code Postal)']);
    exit;
}

try {
    // On instancie le cerveau du calendrier
    $logic = new PlanningLogic();

    // On demande les créneaux pour cette date et ce code postal
    $result = $logic->getAvailableSlots($date, $zip);

    // On renvoie la réponse au Javascript (JSON)
    echo json_encode($result);

} catch (Exception $e) {
    // En cas d'erreur technique (ex: connexion Google échouée)
    http_response_code(500); // Code erreur serveur
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>