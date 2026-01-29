<?php
/**
 * Group class - Groepenbeheer
 */
class Group {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Maak een nieuwe groep aan
     * @param string $name
     * @param int $ownerId
     * @param string|null $description
     * @return bool|int - false bij fout, anders group ID
     */
    public function create(string $name, int $ownerId, ?string $description = null): bool|int {
        // Validatie
        if (empty($name)) {
            return false;
        }

        // Genereer unieke invite code
        $inviteCode = $this->generateInviteCode();

        // Insert groep
        $stmt = $this->db->prepare("
            INSERT INTO `groups` (name, description, owner_id, invite_code) 
            VALUES (:name, :description, :owner_id, :invite_code)
        ");

        try {
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':owner_id' => $ownerId,
                ':invite_code' => $inviteCode
            ]);
            
            $groupId = (int)$this->db->lastInsertId();
            
            // Voeg eigenaar automatisch toe als lid
            $memberStmt = $this->db->prepare("
                INSERT INTO group_members (group_id, user_id) 
                VALUES (:group_id, :user_id)
            ");
            $memberStmt->execute([
                ':group_id' => $groupId,
                ':user_id' => $ownerId
            ]);
            
            return $groupId;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Genereer unieke invite code
     * @return string
     */
    private function generateInviteCode(): string {
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            $stmt = $this->db->prepare("SELECT id FROM `groups` WHERE invite_code = :code");
            $stmt->execute([':code' => $code]);
        } while ($stmt->fetch());

        return $code;
    }

    /**
     * Haal groep op via ID
     * @param int $groupId
     * @return array|false
     */
    public function getGroupById(int $groupId): array|false {
        $stmt = $this->db->prepare("
            SELECT g.*, u.name as owner_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
            FROM `groups` g
            LEFT JOIN users u ON g.owner_id = u.id
            WHERE g.id = :id
        ");
        
        $stmt->execute([':id' => $groupId]);
        return $stmt->fetch();
    }

    /**
     * Haal groep op via invite code
     * @param string $inviteCode
     * @return array|false
     */
    public function getGroupByInviteCode(string $inviteCode): array|false {
        $stmt = $this->db->prepare("
            SELECT g.*, u.name as owner_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
            FROM `groups` g
            LEFT JOIN users u ON g.owner_id = u.id
            WHERE g.invite_code = :code
        ");
        
        $stmt->execute([':code' => $inviteCode]);
        return $stmt->fetch();
    }

    /**
     * Haal alle groepen op waar gebruiker lid van is
     * @param int $userId
     * @return array
     */
    public function getGroupsByUser(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT g.*, u.name as owner_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                   (g.owner_id = :user_id) as is_owner
            FROM `groups` g
            INNER JOIN group_members gm ON g.id = gm.group_id
            LEFT JOIN users u ON g.owner_id = u.id
            WHERE gm.user_id = :user_id
            ORDER BY g.created_at DESC
        ");
        
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Haal alle leden van een groep op
     * @param int $groupId
     * @return array
     */
    public function getMembers(int $groupId): array {
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
     * Join een groep via invite code
     * @param string $inviteCode
     * @param int $userId
     * @return bool|int - false bij fout, anders group ID
     */
    public function joinByInviteCode(string $inviteCode, int $userId): bool|int {
        // Haal groep op
        $group = $this->getGroupByInviteCode($inviteCode);
        
        if (!$group) {
            return false;
        }

        $groupId = $group['id'];

        // Check of al lid
        if ($this->isMember($groupId, $userId)) {
            return false;
        }

        // Voeg toe als lid
        $stmt = $this->db->prepare("
            INSERT INTO group_members (group_id, user_id) 
            VALUES (:group_id, :user_id)
        ");

        try {
            $stmt->execute([
                ':group_id' => $groupId,
                ':user_id' => $userId
            ]);
            return $groupId;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Verlaat een groep
     * @param int $groupId
     * @param int $userId
     * @return bool
     */
    public function leave(int $groupId, int $userId): bool {
        // Check of gebruiker eigenaar is
        $group = $this->getGroupById($groupId);
        
        if (!$group) {
            return false;
        }

        // Eigenaar kan niet zomaar vertrekken
        if ($group['owner_id'] == $userId) {
            return false;
        }

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
     * Verwijder een groep (alleen eigenaar)
     * @param int $groupId
     * @param int $userId
     * @return bool
     */
    public function delete(int $groupId, int $userId): bool {
        // Check of gebruiker eigenaar is
        $group = $this->getGroupById($groupId);
        
        if (!$group || $group['owner_id'] != $userId) {
            return false;
        }

        $stmt = $this->db->prepare("
            DELETE FROM `groups` WHERE id = :id
        ");

        try {
            return $stmt->execute([':id' => $groupId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update groep informatie (alleen eigenaar)
     * @param int $groupId
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function update(int $groupId, int $userId, array $data): bool {
        // Check of gebruiker eigenaar is
        $group = $this->getGroupById($groupId);
        
        if (!$group || $group['owner_id'] != $userId) {
            return false;
        }

        $allowedFields = ['name', 'description'];
        $updateFields = [];
        $params = [':id' => $groupId];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE `groups` SET " . implode(', ', $updateFields) . " WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }
}
