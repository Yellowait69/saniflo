<?php
// public/api_slots.php
header('Content-Type: application/json');

// AJOUT : Connexion à la base de données pour vérifier les créneaux en cours de paiement
$pdo = require_once __DIR__ . '/../config/db.php';

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
    // MODIFICATION : On instancie le cerveau du calendrier avec $pdo
    $logic = new PlanningLogic($pdo);

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
    // --- CORRECTION DE SÉCURITÉ ---

    // 1. On enregistre la VRAIE erreur dans les logs secrets du serveur (consultables sur votre panel Alwaysdata)
    error_log("Erreur API Slots : " . $e->getMessage() . " dans " . $e->getFile() . " à la ligne " . $e->getLine());

    // 2. On renvoie un message GÉNÉRIQUE et inoffensif au navigateur du client
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur technique est survenue lors de la recherche des disponibilités. Veuillez réessayer plus tard.'
    ]);
}
?>