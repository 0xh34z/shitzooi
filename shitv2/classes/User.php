<?php
/**
 * User class - Gebruikersbeheer en authenticatie
 */
class User {
    private PDO $db;
    private ?int $id = null;
    private ?string $name = null;
    private ?string $email = null;
    private ?string $role = null;
    private ?bool $isBlocked = null;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Registreer een nieuwe gebruiker
     * @param string $name
     * @param string $email
     * @param string $password
     * @return bool|int - false bij fout, anders user ID
     */
    public function register(string $name, string $email, string $password): bool|int {
        // Validatie
        if (empty($name) || empty($email) || empty($password)) {
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (strlen($password) < 10) {
            return false;
        }

        // Check of email al bestaat
        if ($this->getUserByEmail($email)) {
            return false;
        }

        // Hash wachtwoord
        $hashedPassword = self::hashPassword($password);

        // Insert gebruiker
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password, role) 
            VALUES (:name, :email, :password, 'student')
        ");

        try {
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hashedPassword
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Login gebruiker
     * @param string $email
     * @param string $password
     * @return bool|array - false bij fout, anders user data
     */
    public function login(string $email, string $password): bool|array {
        $user = $this->getUserByEmail($email);

        if (!$user) {
            return false;
        }

        // Check of gebruiker geblokkeerd is
        if ($user['is_blocked']) {
            return false;
        }

        // Verifieer wachtwoord
        if (!self::verifyPassword($password, $user['password'])) {
            return false;
        }

        // Return user data zonder wachtwoord
        unset($user['password']);
        return $user;
    }

    /**
     * Hash wachtwoord
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifieer wachtwoord
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Haal gebruiker op o.b.v. ID
     * @param int $id
     * @return array|null
     */
    public function getUserById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Haal gebruiker op o.b.v. email
     * @param string $email
     * @return array|null
     */
    public function getUserByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update gebruiker
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateUser(int $id, array $data): bool {
        $allowedFields = ['name', 'email'];
        $fields = [];
        $values = [':id' => $id];

        // Check if email is being changed and if it already exists
        if (isset($data['email'])) {
            $existingUser = $this->getUserByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $id) {
                return false; // Email already exists for another user
            }
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $values[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute($values);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Blokkeer gebruiker (alleen admin)
     * @param int $id
     * @return bool
     */
    public function blockUser(int $id): bool {
        $stmt = $this->db->prepare("UPDATE users SET is_blocked = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Deblokkeer gebruiker (alleen admin)
     * @param int $id
     * @return bool
     */
    public function unblockUser(int $id): bool {
        $stmt = $this->db->prepare("UPDATE users SET is_blocked = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Wijzig rol van gebruiker (alleen admin)
     * @param int $id
     * @param string $role
     * @return bool
     */
    public function changeRole(int $id, string $role): bool {
        $validRoles = ['student', 'admin'];
        if (!in_array($role, $validRoles)) {
            return false;
        }
        
        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :id");
        return $stmt->execute([':id' => $id, ':role' => $role]);
    }

    /**
     * Verwijder gebruiker (alleen admin)
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Haal alle gebruikers op (alleen admin)
     * @return array
     */
    public function getAllUsers(): array {
        $stmt = $this->db->query("SELECT id, name, email, role, is_blocked, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
}
