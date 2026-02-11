<?php
// services/PlanningLogic.php
require_once __DIR__ . '/../vendor/autoload.php';

class PlanningLogic {
    private $service;
    private $calendarId;

    public function __construct() {
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/../config/service-account.json');
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $this->service = new Google_Service_Calendar($client);
        $this->calendarId = 'fbkhu75pphmgu9njp7gj43ftjk@group.calendar.google.com';
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

            $slots = $this->calculateSlotsForDay($dateString, $dayEvents, $zoneType);

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

    private function calculateSlotsForDay($date, $events, $zoneType) {
        $totalAppointments = 0;
        $occupied = [];
        $isBrusselsDay = false;
        $tzBrussels = new DateTimeZone('Europe/Brussels');

        $now = new DateTime('now', $tzBrussels);
        $todayTimestamp = $now->getTimestamp();
        $isToday = ($date === $now->format('Y-m-d'));

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
                    'end'   => $dtEnd->getTimestamp(),
                    'end_str' => $dtEnd->format('H:i')
                ];

                $zipCodeFound = null;
                if (preg_match('/\b(\d{4})\b/', $summary . ' ' . $location, $matches)) {
                    $zipCodeFound = (int)$matches[1];
                }

                if ($zipCodeFound) {
                    if (($zipCodeFound >= 1000 && $zipCodeFound <= 1210) || ($zipCodeFound >= 1500 && $zipCodeFound <= 1970)) {
                        $isBrusselsDay = true;
                    }
                }
            }
        }

        if ($totalAppointments >= 6) return [];

        if (($zoneType === 'BXL_STD' || $zoneType === 'BXL_RESTRICTED') && !$isBrusselsDay) {
            return [];
        }
        if (($zoneType === 'BW_STD' || $zoneType === 'BW_RESTRICTED') && $isBrusselsDay) {
            return [];
        }

        $validSlots = [];

        // CAS 1 : ZONES RESTREINTES (1400... / 1500...)
        if ($zoneType === 'BW_RESTRICTED' || $zoneType === 'BXL_RESTRICTED') {
            $candidates = ['08:00', '15:30'];
            foreach ($candidates as $timeStr) {
                $slotStart = strtotime("$date $timeStr");
                $slotEnd   = $slotStart + (45 * 60);

                if (!$this->isOverlap($slotStart, $slotEnd, $occupied)) {
                    if ($isToday && $slotStart <= ($todayTimestamp + 600)) continue;
                    $validSlots[] = $timeStr;
                }
            }
            return $validSlots;
        }

        // CAS 2 : ZONES STANDARDS (BW Centre & BXL Centre)
        // DÉTECTION DU POINT DE DÉPART INTELLIGENT

        // Par défaut, on commence à 08:00
        $startSimulation = strtotime("$date 08:00:00");
        $endOfDay = strtotime("$date 15:45:00");

        // Mais s'il y a déjà des RDV (ex: un rdv à 07h45 qui finit à 08h30),
        // on veut pouvoir se caler juste après.
        // On trie les occupations par heure de fin
        usort($occupied, function($a, $b) {
            return $a['end'] <=> $b['end'];
        });

        // On crée une liste de points de départ possibles :
        // 1. Le début de journée standard (08:00)
        // 2. La fin de chaque rendez-vous existant (pour coller le suivant)
        $potentialStarts = [$startSimulation];
        foreach($occupied as $occ) {
            // Si le rdv finit avant la fin de journée, c'est un point de départ potentiel
            if ($occ['end'] <= $endOfDay) {
                $potentialStarts[] = $occ['end'];
            }
        }

        // On élimine les doublons et on trie
        $potentialStarts = array_unique($potentialStarts);
        sort($potentialStarts);

        // Pour chaque point de départ potentiel, on lance une simulation de grille
        // par pas de 45 min jusqu'à la fin de la journée.
        foreach ($potentialStarts as $pStart) {
            // On s'assure qu'on ne commence pas avant 08:00 (sauf si c'est pour coller un rdv existant ?)
            // Ici, si un RDV finit à 08h30, on accepte de commencer à 08h30.
            // Si le point de départ est < 08h00 (ex: un rdv finissant à 7h30), on le remonte à 8h00.
            if ($pStart < $startSimulation) $pStart = $startSimulation;

            // Boucle de génération à partir de ce point
            for ($pointer = $pStart; $pointer <= $endOfDay; $pointer += (45 * 60)) {
                $slotStart = $pointer;
                $slotEnd   = $pointer + (45 * 60);

                // Vérification chevauchement
                if (!$this->isOverlap($slotStart, $slotEnd, $occupied)) {
                    // Vérif heure passée
                    if ($isToday && $slotStart <= ($todayTimestamp + 600)) continue;

                    // On formate l'heure
                    $slotTime = date('H:i', $slotStart);

                    // On évite les doublons dans la liste finale
                    if (!in_array($slotTime, $validSlots)) {
                        $validSlots[] = $slotTime;
                    }
                } else {
                    // Si on touche un obstacle (un autre rdv), cette série s'arrête là pour ce "fil".
                    // On laisse la boucle externe trouver le prochain point de départ (la fin de l'obstacle).
                }
            }
        }

        // Tri final des créneaux
        sort($validSlots);


        // SPÉCIFICITÉ BXL CENTRE : CONSÉCUTIF OBLIGATOIRE
        if ($zoneType === 'BXL_STD') {
            if ($totalAppointments === 0) {
                // S'il n'y a personne, on force 08:00
                if (!in_array('08:00', $validSlots)) return [];
                return ['08:00'];
            } else {
                // On cherche le créneau collé au dernier RDV
                $lastEndTime = 0;
                foreach ($occupied as $occ) {
                    if ($occ['end'] > $lastEndTime) $lastEndTime = $occ['end'];
                }

                $targetSlot = date('H:i', $lastEndTime);

                // Si le créneau collé existe dans nos dispos calculées, on le renvoie
                if (in_array($targetSlot, $validSlots)) {
                    return [$targetSlot];
                } else {
                    return [];
                }
            }
        }

        return $validSlots;
    }

    private function isOverlap($start, $end, $occupied) {
        foreach ($occupied as $occ) {
            if ($start < $occ['end'] && $end > $occ['start']) {
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
        $endDateTime = date('c', strtotime($startDateTime . ' +45 minutes'));
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
}
?>