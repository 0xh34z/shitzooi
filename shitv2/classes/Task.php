<?php
/**
 * Task class - Takenbeheer
 */
class Task {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Maak een nieuwe taak aan
     * @param int $userId
     * @param string $title
     * @param string $description
     * @param string $deadline
     * @param string $priority
     * @param string $status
     * @return bool|int - false bij fout, anders task ID
     */
    public function create(int $userId, string $title, string $description, string $deadline, string $priority = 'normaal', string $status = 'te_doen'): bool|int {
        // Validatie
        if (empty($title) || empty($deadline)) {
            return false;
        }

        // Deadline moet in de toekomst liggen (per US3)
        try {
            $deadlineTime = strtotime($deadline);
            if ($deadlineTime === false || $deadlineTime < strtotime('today')) {
                return false; // Reject past or today deadline
            }
        } catch (Exception $e) {
            return false;
        }

        // Valideer priority en status
        $validPriorities = ['laag', 'normaal', 'hoog'];
        $validStatuses = ['te_doen', 'bezig', 'afgerond'];
        
        if (!in_array($priority, $validPriorities)) {
            $priority = 'normaal';
        }
        
        if (!in_array($status, $validStatuses)) {
            $status = 'te_doen';
        }

        // Insert taak
        $stmt = $this->db->prepare("
            INSERT INTO tasks (user_id, title, description, deadline, priority, status) 
            VALUES (:user_id, :title, :description, :deadline, :priority, :status)
        ");

        try {
            $stmt->execute([
                ':user_id' => $userId,
                ':title' => $title,
                ':description' => $description,
                ':deadline' => $deadline,
                ':priority' => $priority,
                ':status' => $status
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Haal alle taken op van een gebruiker
     * @param int $userId
     * @param string|null $status - optioneel filter op status
     * @return array
     */
    public function getTasksByUser(int $userId, ?string $status = null): array {
        $sql = "SELECT * FROM tasks WHERE user_id = :user_id";
        
        if ($status !== null) {
            $sql .= " AND status = :status";
        }
        
        $sql .= " ORDER BY deadline ASC, priority DESC";
        
        $stmt = $this->db->prepare($sql);
        $params = [':user_id' => $userId];
        
        if ($status !== null) {
            $params[':status'] = $status;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Haal één taak op
     * @param int $taskId
     * @param int $userId - voor security check
     * @return array|false
     */
    public function getTaskById(int $taskId, int $userId): array|false {
        $stmt = $this->db->prepare("
            SELECT * FROM tasks 
            WHERE id = :id AND user_id = :user_id
        ");
        
        $stmt->execute([
            ':id' => $taskId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetch();
    }

    /**
     * Update een taak
     * @param int $taskId
     * @param int $userId
     * @param array $data - array met te updaten velden
     * @return bool
     */
    public function update(int $taskId, int $userId, array $data): bool {
        // Check of taak bestaat en van gebruiker is
        if (!$this->getTaskById($taskId, $userId)) {
            return false;
        }

        $allowedFields = ['title', 'description', 'deadline', 'priority', 'status'];
        $updateFields = [];
        $params = [':id' => $taskId, ':user_id' => $userId];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE tasks SET " . implode(', ', $updateFields) . " 
                WHERE id = :id AND user_id = :user_id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Verwijder een taak
     * @param int $taskId
     * @param int $userId
     * @return bool
     */
    public function delete(int $taskId, int $userId): bool {
        $stmt = $this->db->prepare("
            DELETE FROM tasks 
            WHERE id = :id AND user_id = :user_id
        ");

        try {
            return $stmt->execute([
                ':id' => $taskId,
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Haal statistieken op voor dashboard
     * @param int $userId
     * @return array
     */
    public function getStats(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'te_doen' THEN 1 ELSE 0 END) as todo,
                SUM(CASE WHEN status = 'bezig' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'afgerond' THEN 1 ELSE 0 END) as completed
            FROM tasks 
            WHERE user_id = :user_id
        ");

        $stmt->execute([':user_id' => $userId]);
        $stats = $stmt->fetch();

        // Bereken percentage afgerond
        $percentageCompleted = 0;
        if ($stats['total'] > 0) {
            $percentageCompleted = round(($stats['completed'] / $stats['total']) * 100);
        }

        return [
            'total' => (int)$stats['total'],
            'todo' => (int)$stats['todo'],
            'in_progress' => (int)$stats['in_progress'],
            'completed' => (int)$stats['completed'],
            'percentage_completed' => $percentageCompleted,
            'openstaand' => (int)$stats['todo'] + (int)$stats['in_progress']
        ];
    }

    /**
     * Haal taken op die binnenkort vervallen
     * @param int $userId
     * @param int $days - aantal dagen vooruit kijken
     * @return array
     */
    public function getUpcomingTasks(int $userId, int $days = 7): array {
        $stmt = $this->db->prepare("
            SELECT * FROM tasks 
            WHERE user_id = :user_id 
            AND status != 'afgerond'
            AND deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY deadline ASC
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':days' => $days
        ]);

        return $stmt->fetchAll();
    }
}
