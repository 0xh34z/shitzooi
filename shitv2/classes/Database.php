<?php
/**
 * Database class - Singleton pattern voor PDO connectie
 */
class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Private constructor - voorkomt directe instantiatie
     */
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connectie mislukt: " . $e->getMessage());
        }
    }

    /**
     * Singleton getInstance methode
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Geeft PDO connectie terug
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->pdo;
    }

    /**
     * Voorkom clonen van singleton
     */
    private function __clone() {}

    /**
     * Voorkom unserialize van singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
