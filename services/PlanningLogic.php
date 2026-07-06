<?php
// services/PlanningLogic.php
require_once __DIR__ . '/../vendor/autoload.php';

class PlanningLogic {
    private $service;
    private $calendarId;
    private $pdo;

    // =========================================================
    // CONFIGURATION DES HORAIRES ET BATTEMENTS (MODIFIABLE ICI)
    // =========================================================
    private $slotDuration = 45; // Durée effective de l'intervention (en minutes)
    private $slotBuffer = 15;   // Temps de trajet / battement obligatoire (en minutes)
    private $maxDailyAppts = 6; // Nombre maximum de RDV par jour
    // =========================================================

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
            return ['error' => 'Zone non desservie ou sur devis uniquement (CP > 1980).'];
        }

        $startDate = date('c');
        $endDate = date('c', strtotime("+$weeks weeks"));

        try {
            $allEvents = $this->fetchGoogleEventsRange($startDate, $endDate);
        } catch (Exception $e) {
            return ['error' => 'Erreur communication Google Agenda'];
        }

        $eventsByDay = [];
        foreach ($allEvents as $event) {
            if (empty($event->start->dateTime) && empty($event->start->date)) continue;
            $rawDate = $event->start->dateTime ?? $event->start->date;
            $dayKey = date('Y-m-d', strtotime($rawDate));
            $eventsByDay[$dayKey][] = $event;
        }

        // --- RÉCUPÉRATION DES CRÉNEAUX BLOQUÉS EN BASE DE DONNÉES ---
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
                    'end'   => $startTs + ($this->slotDuration * 60)
                ];
            }
        }

        $results = [];
        $currentDate = new DateTime();

        if ($currentDate->format('N') == 1 && $currentDate->format('H') >= 16) {
            $currentDate->modify('next monday');
        } elseif ($currentDate->format('N') != 1) {
            $currentDate->modify('next monday');
        }

        for ($i = 0; $i < $weeks; $i++) {
            $dateString = $currentDate->format('Y-m-d');
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
                    'slots' => $slots
                ];
            }
            $currentDate->modify('+7 days');
        }

        return ['days' => $results];
    }

    private function calculateSlotsForDay($date, $events, $zoneType, $dbOccupied = []) {
        $totalAppointments = 0;
        $occupied = [];
        $isBrusselsDay = false;
        $tzBrussels = new DateTimeZone('Europe/Brussels');

        $now = new DateTime('now', $tzBrussels);
        $todayTimestamp = $now->getTimestamp();
        $isToday = ($date === $now->format('Y-m-d'));

        // Conversion des durées en secondes
        $durationSec = $this->slotDuration * 60;
        $bufferSec = $this->slotBuffer * 60;
        $stepSec = $durationSec + $bufferSec; // Ex: 45m + 15m = 60m par boucle

        // 1. Ajout des événements Google Agenda
        foreach ($events as $event) {
            $summary = trim(strtoupper((string)$event->getSummary()));
            $location = trim(strtoupper((string)$event->getLocation()));

            if ($summary === 'ZONE BXL' || $summary === 'ZONE BRUXELLES') {
                $isBrusselsDay = true;
                continue;
            }
            if (strpos($summary, 'ANNUL') !== false) continue;

            if ($event->start->dateTime) {
                $totalAppointments++;

                $dtStart = new DateTime($event->start->dateTime);
                $dtStart->setTimezone($tzBrussels);
                $dtEnd = new DateTime($event->end->dateTime);
                $dtEnd->setTimezone($tzBrussels);

                $occupied[] = [
                    'start' => $dtStart->getTimestamp(),
                    'end'   => $dtEnd->getTimestamp()
                ];

                if (preg_match('/\b(\d{4})\b/', $summary . ' ' . $location, $matches)) {
                    $zipCodeFound = (int)$matches[1];
                    if (($zipCodeFound >= 1000 && $zipCodeFound <= 1210) || ($zipCodeFound >= 1500 && $zipCodeFound <= 1970)) {
                        $isBrusselsDay = true;
                    }
                }
            }
        }

        // 2. Ajout des créneaux réservés temporairement (DB)
        foreach ($dbOccupied as $dbOcc) {
            if (date('Y-m-d', $dbOcc['start']) === $date) {
                $occupied[] = $dbOcc;
                $totalAppointments++;
            }
        }

        if ($totalAppointments >= $this->maxDailyAppts) return [];

        if (($zoneType === 'BXL_STD' || $zoneType === 'BXL_RESTRICTED') && !$isBrusselsDay) return [];
        if (($zoneType === 'BW_STD' || $zoneType === 'BW_RESTRICTED') && $isBrusselsDay) return [];

        $validSlots = [];

        // CAS 1 : ZONES RESTREINTES
        if ($zoneType === 'BW_RESTRICTED' || $zoneType === 'BXL_RESTRICTED') {
            $candidates = ['08:00', '15:30'];
            foreach ($candidates as $timeStr) {
                $slotStart = strtotime("$date $timeStr");
                $slotEnd   = $slotStart + $durationSec;

                // Vérification du chevauchement avec prise en compte du battement
                if (!$this->isOverlap($slotStart, $slotEnd, $occupied, $bufferSec)) {
                    if ($isToday && $slotStart <= ($todayTimestamp + 600)) continue;
                    $validSlots[] = $timeStr;
                }
            }
            return $validSlots;
        }

        // CAS 2 : ZONES STANDARDS (Génération Intelligente)
        $startSimulation = strtotime("$date 08:00:00");
        $endOfDay = strtotime("$date 15:45:00");

        // On trie les occupations par heure de fin
        usort($occupied, function($a, $b) { return $a['end'] <=> $b['end']; });

        $potentialStarts = [$startSimulation];
        foreach($occupied as $occ) {
            if ($occ['end'] <= $endOfDay) {
                // Point de départ potentiel = Fin de l'occupation + 15 min de battement
                $potentialStarts[] = $occ['end'] + $bufferSec;
            }
        }

        $potentialStarts = array_unique($potentialStarts);
        sort($potentialStarts);

        foreach ($potentialStarts as $pStart) {
            if ($pStart < $startSimulation) $pStart = $startSimulation;

            // Arrondi propre au quart d'heure (ex: 08:03 devient 08:15) pour garder des horaires lisibles
            $pStart = ceil($pStart / 900) * 900;

            // Génération par pas d'1 heure (45m + 15m)
            for ($pointer = $pStart; $pointer <= $endOfDay; $pointer += $stepSec) {
                $slotStart = $pointer;
                $slotEnd   = $pointer + $durationSec;

                if (!$this->isOverlap($slotStart, $slotEnd, $occupied, $bufferSec)) {
                    if ($isToday && $slotStart <= ($todayTimestamp + 600)) continue;

                    $slotTime = date('H:i', $slotStart);
                    if (!in_array($slotTime, $validSlots)) {
                        $validSlots[] = $slotTime;
                    }
                }
            }
        }

        sort($validSlots);

        // SPÉCIFICITÉ BXL CENTRE : CONSÉCUTIF OBLIGATOIRE
        if ($zoneType === 'BXL_STD') {
            if ($totalAppointments === 0) {
                if (!in_array('08:00', $validSlots)) return [];
                return ['08:00'];
            } else {
                $lastEndTime = 0;
                foreach ($occupied as $occ) {
                    if ($occ['end'] > $lastEndTime) $lastEndTime = $occ['end'];
                }

                // On cible le créneau exact après le dernier RDV + les 15 minutes de trajet
                $targetSlotStart = $lastEndTime + $bufferSec;
                $targetSlotStart = ceil($targetSlotStart / 900) * 900; // Arrondi au quart d'heure
                $targetSlot = date('H:i', $targetSlotStart);

                if (in_array($targetSlot, $validSlots)) {
                    return [$targetSlot];
                } else {
                    return [];
                }
            }
        }

        return $validSlots;
    }

    /**
     * Vérifie si un créneau généré chevauche un événement existant
     * en prenant en compte le battement de 15 minutes de sécurité.
     */
    private function isOverlap($start, $end, $occupied, $buffer = 0) {
        foreach ($occupied as $occ) {
            // Un créneau est invalide s'il commence avant que l'occupation + trajet ne soit fini
            // ET qu'il se termine après le début de l'occupation - trajet
            if ($start < ($occ['end'] + $buffer) && $end > ($occ['start'] - $buffer)) {
                return true;
            }
        }
        return false;
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
        // On utilise la variable de durée dynamiquement
        $endDateTime = date('c', strtotime($startDateTime . ' +' . $this->slotDuration . ' minutes'));
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