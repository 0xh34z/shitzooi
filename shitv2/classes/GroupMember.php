<?php
/**
 * GroupMember class - Lidmaatschap beheer
 */
class GroupMember {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Voeg lid toe aan groep
     * @param int $groupId
     * @param int $userId
     * @return bool
     */
    public function addMember(int $groupId, int $userId): bool {
        // Check of al lid
        if ($this->isMember($groupId, $userId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO group_members (group_id, user_id) 
            VALUES (:group_id, :user_id)
        ");

        try {
            return $stmt->execute([
                ':group_id' => $groupId,
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Verwijder lid uit groep
     * @param int $groupId
     * @param int $userId
     * @return bool
     */
    public function removeMember(int $groupId, int $userId): bool {
        $stmt = $this->db->prepare("
            DELETE FROM group_members 
            WHERE group_id = :group_id AND user_id = :user_id
        ");

        try {
            return $stmt->execute([
                ':group_id' => $groupId,
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Check of gebruiker lid is van groep
     * @param int $groupId
     * @param int $userId
     * @return bool
     */
    public function isMember(int $groupId, int $userId): bool {
        $stmt = $this->db->prepare("
            SELECT id FROM group_members 
            WHERE group_id = :group_id AND user_id = :user_id
        ");
        
        $stmt->execute([
            ':group_id' => $groupId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetch() !== false;
    }

    /**
     * Haal alle leden van een groep op
     * @param int $groupId
     * @return array
     */
    public function getMembersByGroup(int $groupId): array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.email, gm.joined_at,
                   (u.id = g.owner_id) as is_owner
            FROM group_members gm
            INNER JOIN users u ON gm.user_id = u.id
            INNER JOIN `groups` g ON gm.group_id = g.id
            WHERE gm.group_id = :group_id
            ORDER BY is_owner DESC, gm.joined_at ASC
        ");
        
        $stmt->execute([':group_id' => $groupId]);
        return $stmt->fetchAll();
    }

    /**
     * Haal alle groepen op waar gebruiker lid van is
     * @param int $userId
     * @return array
     */
    public function getGroupsByUser(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT g.id, g.name, g.description, g.owner_id, g.invite_code, 
                   g.created_at, gm.joined_at,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                   (g.owner_id = :user_id) as is_owner
            FROM group_members gm
            INNER JOIN `groups` g ON gm.group_id = g.id
            WHERE gm.user_id = :user_id
            ORDER BY g.created_at DESC
        ");
        
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Haal aantal leden van een groep op
     * @param int $groupId
     * @return int
     */
    public function getMemberCount(int $groupId): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM group_members 
            WHERE group_id = :group_id
        ");
        
        $stmt->execute([':group_id' => $groupId]);
        $result = $stmt->fetch();
        
        return (int)$result['count'];
    }

    /**
     * Haal alle groepen op met statistieken
     * @param int $userId
     * @return array
     */
    public function getGroupsWithStats(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT g.*, 
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                   (SELECT COUNT(*) FROM appointments WHERE group_id = g.id) as appointment_count,
                   (g.owner_id = :user_id1) as is_owner,
                   u.name as owner_name
            FROM group_members gm
            INNER JOIN `groups` g ON gm.group_id = g.id
            LEFT JOIN users u ON g.owner_id = u.id
            WHERE gm.user_id = :user_id2
            ORDER BY g.created_at DESC
        ");
        
        $stmt->execute([':user_id1' => $userId, ':user_id2' => $userId]);
        return $stmt->fetchAll();
    }
}
