<?php
/**
 * AppointmentResponse class - Reacties op afspraken
 */
class AppointmentResponse {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Reageer op een afspraak (of update bestaande reactie)
     * @param int $appointmentId
     * @param int $userId
     * @param string $response - 'erbij', 'misschien', of 'niet'
     * @return bool
     */
    public function respond(int $appointmentId, int $userId, string $response): bool {
        // Valideer response
        $validResponses = ['erbij', 'misschien', 'niet'];
        if (!in_array($response, $validResponses)) {
            return false;
        }

        // Check of al een reactie bestaat
        $existingResponse = $this->getResponse($appointmentId, $userId);

        if ($existingResponse) {
            // Update bestaande reactie
            return $this->updateResponse($appointmentId, $userId, $response);
        } else {
            // Nieuwe reactie
            return $this->createResponse($appointmentId, $userId, $response);
        }
    }

    /**
     * Maak een nieuwe reactie aan
     * @param int $appointmentId
     * @param int $userId
     * @param string $response
     * @return bool
     */
    private function createResponse(int $appointmentId, int $userId, string $response): bool {
        $stmt = $this->db->prepare("
            INSERT INTO appointment_responses (appointment_id, user_id, response) 
            VALUES (:appointment_id, :user_id, :response)
        ");

        try {
            return $stmt->execute([
                ':appointment_id' => $appointmentId,
                ':user_id' => $userId,
                ':response' => $response
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update een bestaande reactie
     * @param int $appointmentId
     * @param int $userId
     * @param string $response
     * @return bool
     */
    private function updateResponse(int $appointmentId, int $userId, string $response): bool {
        $stmt = $this->db->prepare("
            UPDATE appointment_responses 
            SET response = :response, responded_at = CURRENT_TIMESTAMP
            WHERE appointment_id = :appointment_id AND user_id = :user_id
        ");

        try {
            return $stmt->execute([
                ':appointment_id' => $appointmentId,
                ':user_id' => $userId,
                ':response' => $response
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Haal reactie op van gebruiker voor een afspraak
     * @param int $appointmentId
     * @param int $userId
     * @return array|false
     */
    public function getResponse(int $appointmentId, int $userId): array|false {
        $stmt = $this->db->prepare("
            SELECT * FROM appointment_responses 
            WHERE appointment_id = :appointment_id AND user_id = :user_id
        ");
        
        $stmt->execute([
            ':appointment_id' => $appointmentId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetch();
    }

    /**
     * Haal alle reacties op voor een afspraak
     * @param int $appointmentId
     * @return array
     */
    public function getResponsesByAppointment(int $appointmentId): array {
        $stmt = $this->db->prepare("
            SELECT ar.*, u.name as user_name, u.email as user_email
            FROM appointment_responses ar
            INNER JOIN users u ON ar.user_id = u.id
            WHERE ar.appointment_id = :appointment_id
            ORDER BY ar.response ASC, u.name ASC
        ");
        
        $stmt->execute([':appointment_id' => $appointmentId]);
        return $stmt->fetchAll();
    }

    /**
     * Verwijder reactie
     * @param int $appointmentId
     * @param int $userId
     * @return bool
     */
    public function deleteResponse(int $appointmentId, int $userId): bool {
        $stmt = $this->db->prepare("
            DELETE FROM appointment_responses 
            WHERE appointment_id = :appointment_id AND user_id = :user_id
        ");

        try {
            return $stmt->execute([
                ':appointment_id' => $appointmentId,
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Haal statistieken op voor een afspraak
     * @param int $appointmentId
     * @return array
     */
    public function getStats(int $appointmentId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN response = 'erbij' THEN 1 ELSE 0 END) as yes_count,
                SUM(CASE WHEN response = 'misschien' THEN 1 ELSE 0 END) as maybe_count,
                SUM(CASE WHEN response = 'niet' THEN 1 ELSE 0 END) as no_count
            FROM appointment_responses 
            WHERE appointment_id = :appointment_id
        ");

        $stmt->execute([':appointment_id' => $appointmentId]);
        return $stmt->fetch();
    }

    /**
     * Haal alle reacties van een gebruiker op
     * @param int $userId
     * @return array
     */
    public function getResponsesByUser(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT ar.*, a.title as appointment_title, a.appointment_date, a.appointment_time,
                   g.name as group_name, g.id as group_id
            FROM appointment_responses ar
            INNER JOIN appointments a ON ar.appointment_id = a.id
            INNER JOIN `groups` g ON a.group_id = g.id
            WHERE ar.user_id = :user_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Haal reacties met details op voor een afspraak
     * @param int $appointmentId
     * @return array - gegroepeerd per response type
     */
    public function getResponsesGrouped(int $appointmentId): array {
        $responses = $this->getResponsesByAppointment($appointmentId);
        
        $grouped = [
            'erbij' => [],
            'misschien' => [],
            'niet' => []
        ];
        
        foreach ($responses as $response) {
            $grouped[$response['response']][] = $response;
        }
        
        return $grouped;
    }

    /**
     * Check of gebruiker al gereageerd heeft
     * @param int $appointmentId
     * @param int $userId
     * @return bool
     */
    public function hasResponded(int $appointmentId, int $userId): bool {
        return $this->getResponse($appointmentId, $userId) !== false;
    }
}
