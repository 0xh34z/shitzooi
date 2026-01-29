<?php
/**
 * DashboardHelper class - Helper voor dashboard statistieken
 */
class DashboardHelper {
    private PDO $db;
    private int $userId;

    public function __construct(PDO $db, int $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }

    /**
     * Haal alle dashboard data op
     * @return array
     */
    public function getDashboardData(): array {
        return [
            'tasks' => $this->getTaskStats(),
            'groups' => $this->getGroupStats(),
            'appointments' => $this->getAppointmentStats(),
            'upcoming_tasks' => $this->getUpcomingTasks(5),
            'next_appointment' => $this->getNextAppointment()
        ];
    }

    /**
     * Haal taak statistieken op
     * @return array
     */
    private function getTaskStats(): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'te_doen' THEN 1 ELSE 0 END) as todo,
                SUM(CASE WHEN status = 'bezig' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'afgerond' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status != 'afgerond' THEN 1 ELSE 0 END) as openstaand
            FROM tasks 
            WHERE user_id = :user_id
        ");

        $stmt->execute([':user_id' => $this->userId]);
        $stats = $stmt->fetch();

        $percentageCompleted = 0;
        if ($stats['total'] > 0) {
            $percentageCompleted = round(($stats['completed'] / $stats['total']) * 100);
        }

        return [
            'total' => (int)$stats['total'],
            'todo' => (int)$stats['todo'],
            'in_progress' => (int)$stats['in_progress'],
            'completed' => (int)$stats['completed'],
            'openstaand' => (int)$stats['openstaand'],
            'percentage_completed' => $percentageCompleted
        ];
    }

    /**
     * Haal groep statistieken op
     * @return array
     */
    private function getGroupStats(): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT gm.group_id) as total_groups,
                SUM(CASE WHEN g.owner_id = :user_id1 THEN 1 ELSE 0 END) as owned_groups
            FROM group_members gm
            INNER JOIN `groups` g ON gm.group_id = g.id
            WHERE gm.user_id = :user_id2
        ");

        $stmt->execute([':user_id1' => $this->userId, ':user_id2' => $this->userId]);
        $stats = $stmt->fetch();

        return [
            'total' => (int)$stats['total_groups'],
            'owned' => (int)$stats['owned_groups'],
            'member_of' => (int)$stats['total_groups'] - (int)$stats['owned_groups']
        ];
    }

    /**
     * Haal afspraak statistieken op
     * @return array
     */
    private function getAppointmentStats(): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN a.appointment_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming,
                SUM(CASE WHEN a.appointment_date < CURDATE() THEN 1 ELSE 0 END) as past
            FROM appointments a
            INNER JOIN `groups` g ON a.group_id = g.id
            INNER JOIN group_members gm ON g.id = gm.group_id
            WHERE gm.user_id = :user_id
        ");

        $stmt->execute([':user_id' => $this->userId]);
        $stats = $stmt->fetch();

        return [
            'total' => (int)$stats['total'],
            'upcoming' => (int)$stats['upcoming'],
            'past' => (int)$stats['past']
        ];
    }

    /**
     * Haal komende taken op
     * @param int $limit
     * @return array
     */
    private function getUpcomingTasks(int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT * FROM tasks 
            WHERE user_id = :user_id 
            AND status != 'afgerond'
            ORDER BY deadline ASC, priority DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Haal eerstvolgende afspraak op
     * @return array|null
     */
    private function getNextAppointment(): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, g.name as group_name, u.name as creator_name
            FROM appointments a
            INNER JOIN `groups` g ON a.group_id = g.id
            INNER JOIN group_members gm ON g.id = gm.group_id
            LEFT JOIN users u ON a.created_by = u.id
            WHERE gm.user_id = :user_id
            AND a.appointment_date >= CURDATE()
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT 1
        ");

        $stmt->execute([':user_id' => $this->userId]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Haal recente activiteit op
     * @param int $limit
     * @return array
     */
    public function getRecentActivity(int $limit = 10): array {
        // Combineer recente taken, groepen en afspraken
        $stmt = $this->db->prepare("
            (SELECT 'task' as type, t.title, t.created_at as date, NULL as group_name
             FROM tasks t
             WHERE t.user_id = :user_id
             ORDER BY t.created_at DESC
             LIMIT 5)
            UNION ALL
            (SELECT 'group' as type, g.name as title, gm.joined_at as date, NULL as group_name
             FROM group_members gm
             INNER JOIN `groups` g ON gm.group_id = g.id
             WHERE gm.user_id = :user_id
             ORDER BY gm.joined_at DESC
             LIMIT 5)
            UNION ALL
            (SELECT 'appointment' as type, a.title, a.created_at as date, g.name as group_name
             FROM appointments a
             INNER JOIN `groups` g ON a.group_id = g.id
             INNER JOIN group_members gm ON g.id = gm.group_id
             WHERE gm.user_id = :user_id
             ORDER BY a.created_at DESC
             LIMIT 5)
            ORDER BY date DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Haal groepen met komende afspraken op
     * @return array
     */
    public function getGroupsWithUpcomingAppointments(): array {
        $stmt = $this->db->prepare("
            SELECT g.id, g.name, 
                   COUNT(a.id) as appointment_count,
                   MIN(a.appointment_date) as next_appointment_date,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
            FROM `groups` g
            INNER JOIN group_members gm ON g.id = gm.group_id
            LEFT JOIN appointments a ON g.id = a.group_id AND a.appointment_date >= CURDATE()
            WHERE gm.user_id = :user_id
            GROUP BY g.id, g.name
            HAVING appointment_count > 0
            ORDER BY next_appointment_date ASC
        ");

        $stmt->execute([':user_id' => $this->userId]);
        return $stmt->fetchAll();
    }

    /**
     * Haal prioriteit taken overzicht op
     * @return array
     */
    public function getTasksByPriority(): array {
        $stmt = $this->db->prepare("
            SELECT priority, COUNT(*) as count
            FROM tasks
            WHERE user_id = :user_id AND status != 'afgerond'
            GROUP BY priority
        ");

        $stmt->execute([':user_id' => $this->userId]);
        $results = $stmt->fetchAll();

        $priorities = [
            'hoog' => 0,
            'normaal' => 0,
            'laag' => 0
        ];

        foreach ($results as $row) {
            $priorities[$row['priority']] = (int)$row['count'];
        }

        return $priorities;
    }
}
