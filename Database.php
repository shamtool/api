  
<?php

/**
 * Represents a connection to the shamtool database.
 */
class STDatabase extends PDO {
    private static ?STDatabase $instance = null;

    public function __construct() {
        try {
            parent::__construct(sprintf("mysql:host=%s;dbname=%s", $_ENV['DB_HOST'], $_ENV['DB_NAME']), $_ENV['DB_USER'], $_ENV['DB_PASS']);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): STDatabase {
        if (self::$instance == null) {
            self::$instance = new STDatabase();
        }
        return self::$instance;
    }
}
