<?php
/**
 * Appointment class - Afspraken binnen groepen
 */
class Appointment {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Maak een nieuwe afspraak aan
     * @param int $groupId
     * @param int $createdBy
     * @param string $title
     * @param string $appointmentDate
     * @param string $appointmentTime
     * @param string|null $description
     * @param string|null $location
     * @return bool|int - false bij fout, anders appointment ID
     */
    public function create(
        int $groupId, 
        int $createdBy, 
        string $title, 
        string $appointmentDate, 
        string $appointmentTime,
        ?string $description = null, 
        ?string $location = null
    ): bool|int {
        // Validatie
        if (empty($title) || empty($appointmentDate) || empty($appointmentTime)) {
            return false;
        }

        // Insert afspraak
        $stmt = $this->db->prepare("
            INSERT INTO appointments (group_id, created_by, title, description, appointment_date, appointment_time, location) 
            VALUES (:group_id, :created_by, :title, :description, :appointment_date, :appointment_time, :location)
        ");

        try {
            $stmt->execute([
                ':group_id' => $groupId,
                ':created_by' => $createdBy,
                ':title' => $title,
                ':description' => $description,
                ':appointment_date' => $appointmentDate,
                ':appointment_time' => $appointmentTime,
                ':location' => $location
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Haal afspraak op via ID
     * @param int $appointmentId
     * @return array|false
     */
    public function getAppointmentById(int $appointmentId): array|false {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name as creator_name, g.name as group_name
            FROM appointments a
            LEFT JOIN users u ON a.created_by = u.id
            LEFT JOIN `groups` g ON a.group_id = g.id
            WHERE a.id = :id
        ");
        
        $stmt->execute([':id' => $appointmentId]);
        return $stmt->fetch();
    }

    /**
     * Haal alle afspraken van een groep op
     * @param int $groupId
     * @param bool $upcomingOnly - alleen toekomstige afspraken
     * @return array
     */
    public function getAppointmentsByGroup(int $groupId, bool $upcomingOnly = false): array {
        $sql = "
            SELECT a.*, u.name as creator_name,
                   (SELECT COUNT(*) FROM appointment_responses WHERE appointment_id = a.id AND response = 'erbij') as response_yes,
                   (SELECT COUNT(*) FROM appointment_responses WHERE appointment_id = a.id AND response = 'misschien') as response_maybe,
                   (SELECT COUNT(*) FROM appointment_responses WHERE appointment_id = a.id AND response = 'niet') as response_no
            FROM appointments a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.group_id = :group_id
        ";
        
        if ($upcomingOnly) {
            $sql .= " AND a.appointment_date >= CURDATE()";
        }
        
        $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':group_id' => $groupId]);
        return $stmt->fetchAll();
    }

    /**
     * Haal alle afspraken van een gebruiker op (via groepen)
     * @param int $userId
     * @param bool $upcomingOnly
     * @return array
     */
    public function getAppointmentsByUser(int $userId, bool $upcomingOnly = false): array {
        $sql = "
            SELECT a.*, u.name as creator_name, g.name as group_name,
                   (SELECT COUNT(*) FROM appointment_responses WHERE appointment_id = a.id AND response = 'erbij') as response_yes,
                   (SELECT COUNT(*) FROM appointment_responses WHERE appointment_id = a.id AND response = 'misschien') as response_maybe,
                   (SELECT COUNT(*) FROM appointment_responses WHERE appointment_id = a.id AND response = 'niet') as response_no
            FROM appointments a
            INNER JOIN `groups` g ON a.group_id = g.id
            INNER JOIN group_members gm ON g.id = gm.group_id
            LEFT JOIN users u ON a.created_by = u.id
            WHERE gm.user_id = :user_id
        ";
        
        if ($upcomingOnly) {
            $sql .= " AND a.appointment_date >= CURDATE()";
        }
        
        $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Haal eerstvolgende afspraak op voor gebruiker
     * @param int $userId
     * @return array|false
     */
    public function getNextAppointment(int $userId): array|false {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name as creator_name, g.name as group_name
            FROM appointments a
            INNER JOIN `groups` g ON a.group_id = g.id
            INNER JOIN group_members gm ON g.id = gm.group_id
            LEFT JOIN users u ON a.created_by = u.id
            WHERE gm.user_id = :user_id
            AND a.appointment_date >= CURDATE()
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT 1
        ");
        
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Update een afspraak
     * @param int $appointmentId
     * @param int $userId - voor security check (moet maker of groep owner zijn)
     * @param array $data
     * @return bool
     */
    public function update(int $appointmentId, int $userId, array $data): bool {
        // Check of afspraak bestaat
        $appointment = $this->getAppointmentById($appointmentId);
        
        if (!$appointment) {
            return false;
        }

        // Check of gebruiker maker is of owner van groep
        if ($appointment['created_by'] != $userId) {
            // Check of owner van groep
            $groupStmt = $this->db->prepare("
                SELECT owner_id FROM `groups` WHERE id = :group_id
            ");
            $groupStmt->execute([':group_id' => $appointment['group_id']]);
            $group = $groupStmt->fetch();
            
            if (!$group || $group['owner_id'] != $userId) {
                return false;
            }
        }

        $allowedFields = ['title', 'description', 'appointment_date', 'appointment_time', 'location'];
        $updateFields = [];
        $params = [':id' => $appointmentId];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE appointments SET " . implode(', ', $updateFields) . " WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Verwijder een afspraak
     * @param int $appointmentId
     * @param int $userId
     * @return bool
     */
    public function delete(int $appointmentId, int $userId): bool {
        // Check of afspraak bestaat
        $appointment = $this->getAppointmentById($appointmentId);
        
        if (!$appointment) {
            return false;
        }

        // Check of gebruiker maker is of owner van groep
        if ($appointment['created_by'] != $userId) {
            // Check of owner van groep
            $groupStmt = $this->db->prepare("
                SELECT owner_id FROM `groups` WHERE id = :group_id
            ");
            $groupStmt->execute([':group_id' => $appointment['group_id']]);
            $group = $groupStmt->fetch();
            
            if (!$group || $group['owner_id'] != $userId) {
                return false;
            }
        }

        $stmt = $this->db->prepare("
            DELETE FROM appointments WHERE id = :id
        ");

        try {
            return $stmt->execute([':id' => $appointmentId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Haal statistieken op voor een groep
     * @param int $groupId
     * @return array
     */
    public function getGroupStats(int $groupId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN appointment_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming
            FROM appointments 
            WHERE group_id = :group_id
        ");

        $stmt->execute([':group_id' => $groupId]);
        return $stmt->fetch();
    }
}
