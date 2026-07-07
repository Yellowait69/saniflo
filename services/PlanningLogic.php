<?php
// services/PlanningLogic.php
require_once __DIR__ . '/../vendor/autoload.php';

class PlanningLogic {
    private $service;
    private $calendarId;
    private $pdo;

    // Horaires fixes dictés par Jean-François (8 créneaux par lundi)
    private $fixedSlots = [
        '08:00', '09:00', '10:00', '11:00',
        '12:30', '13:30', '14:30', '15:30'
    ];

    public function __construct($pdo = null) {
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/../config/service-account.json');
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $this->service = new Google_Service_Calendar($client);
        $this->calendarId = 'fbkhu75pphmgu9njp7gj43ftjk@group.calendar.google.com';
        $this->pdo = $pdo;
    }

    public function getNextAvailabilities($zip, $weeks = 12) {
        $zip = (int)$zip;
        $zoneType = $this->getZoneType($zip);

        if ($zoneType === 'FORBIDDEN') {
            return ['error' => 'Zone non desservie en ligne ou sur devis uniquement (CP >= 1980). Veuillez nous contacter par téléphone.'];
        }

        $startDate = date('c');
        // On scanne sur 16 semaines (environ 4 mois) car certaines zones n'ont qu'un lundi par mois
        $endDate = date('c', strtotime("+16 weeks"));

        try {
            $allEvents = $this->fetchGoogleEventsRange($startDate, $endDate);
        } catch (Exception $e) {
            return ['error' => 'Erreur de communication avec Google Agenda.'];
        }

        $eventsByDay = [];
        foreach ($allEvents as $event) {
            if (empty($event->start->dateTime) && empty($event->start->date)) continue;
            $rawDate = $event->start->dateTime ?? $event->start->date;
            $dayKey = date('Y-m-d', strtotime($rawDate));
            $eventsByDay[$dayKey][] = $event;
        }

        // --- RÉCUPÉRATION DES CRÉNEAUX BLOQUÉS EN BASE DE DONNÉES (en cours de paiement) ---
        $dbOccupied = [];
        if ($this->pdo) {
            $sql = "SELECT appointment_date 
                    FROM quote_requests 
                    WHERE appointment_date IS NOT NULL 
                    AND status != 'annulé'
                    AND (
                        status IN ('nouveau', 'traité') 
                        OR (status = 'en_attente' AND created_at >= NOW() - INTERVAL 15 MINUTE)
                    )";
            $stmt = $this->pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dt = new DateTime($row['appointment_date'], new DateTimeZone('Europe/Brussels'));
                $startTs = $dt->getTimestamp();
                $dbOccupied[] = [
                    'start' => $startTs,
                    'end'   => $startTs + 3600 // Bloque 1h par intervention
                ];
            }
        }

        $results = [];
        $currentDate = new DateTime();

        // On se place directement sur le prochain Lundi
        if ($currentDate->format('N') != 1) {
            $currentDate->modify('next monday');
        } else if ($currentDate->format('H') >= 16) {
            $currentDate->modify('next monday'); // La journée d'aujourd'hui est finie, on passe au lundi suivant
        }

        // On va vérifier les prochains Lundis (jusqu'à 16 lundis)
        $mondaysToCheck = 16;

        for ($i = 0; $i < $mondaysToCheck; $i++) {
            $dateString = $currentDate->format('Y-m-d');

            // --- LOGIQUE DE RÉPARTITION DES MOIS (Règles de Jean-François) ---
            // Le "weekOfMonth" va de 1 à 5 selon le jour du mois
            $dayOfMonth = (int)$currentDate->format('d');
            $weekOfMonth = ceil($dayOfMonth / 7);

            // 4e Lundi du mois = Bruxelles
            $isBxlMonday = ($weekOfMonth == 4);
            // Les autres Lundis (1er, 2e, 3e, et éventuellement 5e) = Brabant Wallon
            $isBwMonday = !$isBxlMonday;

            $validDayForZone = false;
            if (($zoneType === 'BXL_STD' || $zoneType === 'BXL_RESTRICTED') && $isBxlMonday) {
                $validDayForZone = true;
            } elseif (($zoneType === 'BW_STD' || $zoneType === 'BW_RESTRICTED') && $isBwMonday) {
                $validDayForZone = true;
            }

            if ($validDayForZone) {
                $dayEvents = $eventsByDay[$dateString] ?? [];
                $slots = $this->calculateSlotsForDay($dateString, $dayEvents, $zoneType, $dbOccupied);

                if (!empty($slots)) {
                    $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
                    $prettyDate = $formatter->format($currentDate);
                    $parts = explode(' ', $prettyDate);
                    $prettyDate = ucfirst($parts[0] . ' ' . $currentDate->format('d') . ' ' . $parts[2]);

                    $results[] = [
                        'date_iso' => $dateString,
                        'date_pretty' => $prettyDate,
                        'slots' => array_values($slots)
                    ];
                }
            }

            // On saute de 7 jours pour aller au Lundi suivant
            $currentDate->modify('+7 days');

            // On s'arrête si on a trouvé au moins 5 dates disponibles à proposer au client
            if (count($results) >= 5) {
                break;
            }
        }

        return ['days' => $results];
    }

    private function calculateSlotsForDay($date, $events, $zoneType, $dbOccupied = []) {
        $occupied = [];
        $tzBrussels = new DateTimeZone('Europe/Brussels');

        // Récupération des occupations Google Agenda
        foreach ($events as $event) {
            if ($event->start->dateTime) {
                $dtStart = new DateTime($event->start->dateTime);
                $dtStart->setTimezone($tzBrussels);
                $dtEnd = new DateTime($event->end->dateTime);
                $dtEnd->setTimezone($tzBrussels);
                $occupied[] = [
                    'start' => $dtStart->getTimestamp(),
                    'end'   => $dtEnd->getTimestamp()
                ];
            }
        }

        // Récupération des occupations Base de données
        foreach ($dbOccupied as $dbOcc) {
            if (date('Y-m-d', $dbOcc['start']) === $date) {
                $occupied[] = $dbOcc;
            }
        }

        $availableSlots = [];
        $nowTimestamp = time();

        // On vérifie chacun des 8 horaires fixes pour voir s'il est libre
        foreach ($this->fixedSlots as $slotTime) {
            $slotStartTs = strtotime("$date $slotTime");
            $slotEndTs = $slotStartTs + 3600; // Bloque le créneau complet (1h)

            if ($slotStartTs < $nowTimestamp) {
                continue; // Le créneau est déjà passé
            }

            $isOccupied = false;
            foreach ($occupied as $occ) {
                // S'il y a un chevauchement avec un événement existant
                if ($slotStartTs < $occ['end'] && $slotEndTs > $occ['start']) {
                    $isOccupied = true;
                    break;
                }
            }

            if (!$isOccupied) {
                $availableSlots[] = $slotTime;
            }
        }

        // ==============================================================
        // SÉCURITÉ TÉLÉPHONE : Toujours garder 2 places libres par lundi
        // S'il reste 2 créneaux ou moins (sur les 8 initiaux), on bloque.
        // ==============================================================
        if (count($availableSlots) <= 2) {
            return [];
        }

        // --- APPLICATION DES RÈGLES EXCEL DE JEAN-FRANÇOIS ---
        $finalSlots = [];

        // Zones extrêmes (1400-1499 & 1500-1970) : Uniquement 08:00 ou 15:30
        if ($zoneType === 'BW_RESTRICTED' || $zoneType === 'BXL_RESTRICTED') {
            foreach (['08:00', '15:30'] as $cand) {
                if (in_array($cand, $availableSlots)) {
                    $finalSlots[] = $cand;
                }
            }
            return $finalSlots;
        }

        // Zone Brabant Wallon Normale (1300-1390) : Choix libre sur tout ce qui reste
        if ($zoneType === 'BW_STD') {
            return $availableSlots;
        }

        // Zone Bruxelles Normale (1000-1210) : RDV obligatoirement consécutifs
        if ($zoneType === 'BXL_STD') {
            if (!empty($availableSlots)) {
                // On renvoie un tableau contenant UNIQUEMENT la première heure libre trouvée.
                // Le client n'a pas le choix de l'heure, ce qui garantit que les RDV se collent.
                return [$availableSlots[0]];
            }
        }

        return [];
    }

    public function getAvailableSlots($date, $zip) {
        $res = $this->getNextAvailabilities($zip, 12);
        if (isset($res['error'])) return $res;

        foreach($res['days'] as $day) {
            if ($day['date_iso'] === $date) {
                return ['slots' => $day['slots']];
            }
        }
        return ['error' => 'Cette date n\'est plus disponible.'];
    }

    private function getZoneType($zip) {
        if ($zip >= 1000 && $zip <= 1210) return 'BXL_STD';
        if ($zip >= 1500 && $zip <= 1970) return 'BXL_RESTRICTED';
        if ($zip >= 1300 && $zip <= 1390) return 'BW_STD';
        if ($zip >= 1400 && $zip <= 1499) return 'BW_RESTRICTED';
        if ($zip >= 1980) return 'FORBIDDEN';
        return 'FORBIDDEN';
    }

    private function fetchGoogleEventsRange($start, $end) {
        $optParams = [
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => $start,
            'timeMax' => $end,
        ];
        return $this->service->events->listEvents($this->calendarId, $optParams)->getItems();
    }

    public function addEvent($data) {
        $startDateTime = $data['date'] . 'T' . $data['time'] . ':00';
        // Le créneau bloque désormais 1h complète dans l'agenda de Jean-François (45m entretien + 15m route)
        $endDateTime = date('c', strtotime($startDateTime . ' +1 hour'));
        $startDateTime = date('c', strtotime($startDateTime));

        $event = new Google_Service_Calendar_Event([
            'summary' => $data['summary'],
            'location' => $data['location'],
            'description' => $data['description'],
            'start' => ['dateTime' => $startDateTime, 'timeZone' => 'Europe/Brussels'],
            'end' => ['dateTime' => $endDateTime, 'timeZone' => 'Europe/Brussels'],
        ]);
        $this->service->events->insert($this->calendarId, $event);
    }

    // ==============================================================================
    // MÉTHODES POUR LA GESTION (ANNULATION ET REPROGRAMMATION)
    // ==============================================================================

    public function findEventId($date, $time, $clientName) {
        $startDateTime = $date . 'T' . $time . ':00';
        $tzBrussels = new DateTimeZone('Europe/Brussels');

        $optParams = [
            'timeMin' => date('c', strtotime($startDateTime) - 3600),
            'timeMax' => date('c', strtotime($startDateTime) + 3600),
            'singleEvents' => true,
        ];

        try {
            $events = $this->service->events->listEvents($this->calendarId, $optParams)->getItems();
            foreach ($events as $event) {
                if (empty($event->start->dateTime)) continue;

                $dt = new DateTime($event->start->dateTime);
                $dt->setTimezone($tzBrussels);

                if ($dt->format('Y-m-d H:i') === "$date $time") {
                    $summary = strtolower((string)$event->getSummary());
                    $desc = strtolower((string)$event->getDescription());
                    $nameSearch = strtolower($clientName);

                    if (strpos($summary, $nameSearch) !== false || strpos($desc, $nameSearch) !== false) {
                        return $event->getId();
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erreur recherche event : " . $e->getMessage());
        }
        return null;
    }

    public function deleteEvent($eventId) {
        try {
            $this->service->events->delete($this->calendarId, $eventId);
            return true;
        } catch (Exception $e) {
            error_log("Erreur suppression event : " . $e->getMessage());
            return false;
        }
    }
}
?>