<?php
// services/PlanningLogic.php
require_once __DIR__ . '/../vendor/autoload.php';

class PlanningLogic {
    private $service;
    private $calendarId;

    // Horaires fixes
    const SLOTS = ['08:00', '09:00', '10:00', '11:00', '12:30', '13:30', '14:30', '15:30'];

    public function __construct() {
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/../config/service-account.json');
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $this->service = new Google_Service_Calendar($client);
        // ID de votre calendrier
        $this->calendarId = 'fbkhu75pphmgu9njp7gj43ftjk@group.calendar.google.com';
    }

    /**
     * Récupère les disponibilités pour les X prochains lundis
     */
    public function getNextAvailabilities($zip, $weeks = 12) {
        $zip = (int)$zip;
        $zoneType = $this->getZoneType($zip);

        if ($zoneType === 'FORBIDDEN') {
            return ['error' => 'Zone non desservie ou sur devis uniquement.'];
        }

        // 1. Récupérer TOUS les événements des 8 prochaines semaines en UN seul appel (Performance)
        $startDate = date('c'); // Aujourd'hui
        $endDate = date('c', strtotime("+$weeks weeks"));

        try {
            $allEvents = $this->fetchGoogleEventsRange($startDate, $endDate);
        } catch (Exception $e) {
            return ['error' => 'Erreur communication Google Agenda'];
        }

        // 2. Organiser les événements par jour (Y-m-d)
        $eventsByDay = [];
        foreach ($allEvents as $event) {
            if (empty($event->start->dateTime) && empty($event->start->date)) continue;

            // On prend la date de début (soit dateTime, soit date pour journée entière)
            $rawDate = $event->start->dateTime ?? $event->start->date;
            $dayKey = date('Y-m-d', strtotime($rawDate));
            $eventsByDay[$dayKey][] = $event;
        }

        // 3. Scanner chaque lundi à venir
        $results = [];
        $currentDate = new DateTime();
        // Si on est lundi après 16h, on commence la semaine prochaine, sinon aujourd'hui si lundi
        if ($currentDate->format('N') == 1 && $currentDate->format('H') >= 16) {
            $currentDate->modify('next monday');
        } elseif ($currentDate->format('N') != 1) {
            $currentDate->modify('next monday');
        }

        for ($i = 0; $i < $weeks; $i++) {
            $dateString = $currentDate->format('Y-m-d');

            // Récupérer les événements spécifiques à ce jour-là
            $dayEvents = $eventsByDay[$dateString] ?? [];

            // Calculer les slots pour ce jour précis
            $slots = $this->calculateSlotsForDay($dateString, $dayEvents, $zoneType);

            if (!empty($slots)) {
                // Formater la date en français pour l'affichage (ex: Lundi 12 Février)
                $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
                $prettyDate = $formatter->format($currentDate);
                // On garde juste "Lundi XX Mois"
                $prettyDate = ucfirst(explode(' ', $prettyDate)[0] . ' ' . $currentDate->format('d') . ' ' . explode(' ', $prettyDate)[2]);

                $results[] = [
                    'date_iso' => $dateString,
                    'date_pretty' => $prettyDate,
                    'slots' => $slots
                ];
            }

            $currentDate->modify('+7 days'); // Lundi suivant
        }

        return ['days' => $results];
    }

    /**
     * Logique interne pour un jour donné (extraite pour être plus propre)
     */
    private function calculateSlotsForDay($date, $events, $zoneType) {
        $totalAppointments = 0;
        $bookedSlots = [];
        $isBrusselsDay = false;

        foreach ($events as $event) {
            $summary = trim(strtoupper($event->getSummary()));

            if ($summary === 'ZONE BXL' || $summary === 'ZONE BRUXELLES') {
                $isBrusselsDay = true;
                continue;
            }
            if (strpos($summary, 'ANNUL') !== false) continue;

            if ($event->start->dateTime) {
                $totalAppointments++;
                $start = date('H:i', strtotime($event->start->dateTime));
                $bookedSlots[] = $start;
            }
        }

        // --- RÈGLES MÉTIER ---

        // 1. Quota max (6)
        if ($totalAppointments >= 6) return [];

        // 2. Conflits Zone
        // Si Zone BXL demandée mais pas de marqueur (et agenda déjà entamé)
        if (($zoneType === 'BXL_STD' || $zoneType === 'BXL_RESTRICTED') && !$isBrusselsDay && $totalAppointments > 0) {
            return [];
        }
        // Si Zone BW demandée mais marqueur BXL présent
        if (($zoneType === 'BW_STD' || $zoneType === 'BW_RESTRICTED') && $isBrusselsDay) {
            return [];
        }

        // 3. Calcul créneaux
        $available = array_diff(self::SLOTS, $bookedSlots);
        $finalSlots = [];

        foreach ($available as $slot) {
            // Restriction Horaire (Nivelles / Périphérie)
            if ($zoneType === 'BW_RESTRICTED' || $zoneType === 'BXL_RESTRICTED') {
                if ($slot !== '08:00' && $slot !== '15:30') continue;
            }

            // Consécutifs BXL Centre
            if ($zoneType === 'BXL_STD') {
                if ($totalAppointments === 0) {
                    if ($slot !== '08:00') continue;
                } else {
                    $lastBooked = max($bookedSlots);
                    $idx = array_search($lastBooked, self::SLOTS);
                    if ($idx !== false && isset(self::SLOTS[$idx + 1])) {
                        if ($slot !== self::SLOTS[$idx + 1]) continue;
                    } else {
                        continue;
                    }
                }
            }
            $finalSlots[] = $slot;
        }

        return array_values($finalSlots);
    }

    // Méthode publique pour vérifier UN seul slot (utilisée lors de la confirmation POST)
    public function getAvailableSlots($date, $zip) {
        // On réutilise la logique de masse mais pour un seul jour
        // C'est un peu moins performant pour un seul check mais ça évite de dupliquer le code
        $res = $this->getNextAvailabilities($zip, 12); // On cherche large pour être sûr de trouver la date

        if (isset($res['error'])) return $res;

        foreach($res['days'] as $day) {
            if ($day['date_iso'] === $date) {
                return ['slots' => $day['slots']];
            }
        }
        return ['error' => 'Cette date n\'est plus disponible.'];
    }

    private function getZoneType($zip) {
        if ($zip >= 1300 && $zip <= 1390) return 'BW_STD';
        if ($zip >= 1400 && $zip <= 1499) return 'BW_RESTRICTED';
        if ($zip >= 1000 && $zip <= 1210) return 'BXL_STD';
        if ($zip >= 1500 && $zip <= 1970) return 'BXL_RESTRICTED';
        if ($zip >= 1980) return 'FORBIDDEN';
        return 'BW_STD';
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
}
?>