<?php
// public/api_slots.php
header('Content-Type: application/json');

// On inclut la logique métier
require_once __DIR__ . '/../services/PlanningLogic.php';

// Récupération des paramètres
$zip = $_GET['zip'] ?? null;
$date = $_GET['date'] ?? null;

// Vérification : Seul le Code Postal est désormais strictement obligatoire au départ
if (!$zip) {
    echo json_encode(['error' => 'Code Postal manquant']);
    exit;
}

try {
    // On instancie le cerveau du calendrier
    $logic = new PlanningLogic();

    if ($date) {
        // Cas 1 : Une date précise est demandée (Vérification d'un jour spécifique)
        // Utile si on garde une logique de validation finale
        $result = $logic->getAvailableSlots($date, $zip);
    } else {
        // Cas 2 (NOUVEAU) : Pas de date fournie => On veut la liste des prochains jours libres
        // C'est ce que votre nouveau script JS appelle via "fetchNextDates(zip)"
        $result = $logic->getNextAvailabilities($zip);
    }

    // On renvoie la réponse au Javascript (JSON)
    echo json_encode($result);

} catch (Exception $e) {
    // En cas d'erreur technique
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>