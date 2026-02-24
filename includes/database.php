<?php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $conn;
    private $error;
    private $stmt;
    
    public function __construct() {
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        );
        
        try {
            // First connect without database to create it if needed
            $pdo = new PDO('mysql:host=' . $this->host . ';charset=utf8mb4', $this->user, $this->pass, $options);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = null;
            
            // Now connect to the database
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
            
            // Create tables if they don't exist
            $this->createTables();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            die('Database Connection Error: ' . $this->error);
        }
    }
    
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS warehouse (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(255) NOT NULL,
            quantity INT DEFAULT 0,
            unit VARCHAR(50) DEFAULT 'دانە',
            unit_price DECIMAL(10,2) DEFAULT 0,
            min_quantity INT DEFAULT 10,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS male_birds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            batch_name VARCHAR(255) NOT NULL,
            quantity INT DEFAULT 0,
            sold_count INT DEFAULT 0,
            dead_count INT DEFAULT 0,
            entry_date DATE,
            status ENUM('active','sold','dead') DEFAULT 'active',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS female_birds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            batch_name VARCHAR(255) NOT NULL,
            quantity INT DEFAULT 0,
            sold_count INT DEFAULT 0,
            dead_count INT DEFAULT 0,
            entry_date DATE,
            status ENUM('active','laying','sold','dead') DEFAULT 'active',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS eggs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            female_bird_id INT,
            quantity INT DEFAULT 0,
            damaged_count INT DEFAULT 0,
            collection_date DATE,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS chicks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            batch_name VARCHAR(255) DEFAULT '',
            egg_id INT,
            quantity INT DEFAULT 0,
            dead_count INT DEFAULT 0,
            hatch_date DATE,
            status ENUM('active','sold') DEFAULT 'active',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            address TEXT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            address TEXT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_code VARCHAR(50),
            customer_id INT,
            item_type ENUM('egg','chick','male_bird','female_bird') NOT NULL,
            quantity INT DEFAULT 0,
            unit_price DECIMAL(10,2) DEFAULT 0,
            total_price DECIMAL(10,2) DEFAULT 0,
            sale_date DATE,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS purchases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            purchase_code VARCHAR(50),
            supplier_id INT,
            item_type VARCHAR(100) NOT NULL,
            quantity INT DEFAULT 0,
            unit_price DECIMAL(10,2) DEFAULT 0,
            total_price DECIMAL(10,2) DEFAULT 0,
            purchase_date DATE,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_type ENUM('income','expense') NOT NULL,
            category VARCHAR(100),
            amount DECIMAL(10,2) DEFAULT 0,
            description TEXT,
            reference_type VARCHAR(50),
            reference_id INT,
            transaction_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ";
        
        $this->conn->exec($sql);
        
        // Create incubator table
        $this->conn->exec("
        CREATE TABLE IF NOT EXISTS incubator (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_name VARCHAR(255) NOT NULL,
            customer_id INT,
            egg_id INT,
            egg_quantity INT NOT NULL DEFAULT 0,
            entry_date DATE NOT NULL,
            expected_hatch_date DATE NOT NULL,
            status ENUM('incubating','hatched') DEFAULT 'incubating',
            hatched_count INT DEFAULT 0,
            damaged_count INT DEFAULT 0,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Add customer_id column to incubator table if not exists
        try {
            $this->conn->exec("ALTER TABLE incubator ADD COLUMN customer_id INT AFTER group_name");
        } catch (PDOException $e) {
            // Column already exists, ignore
        }
        
        // Add egg_id column to chicks table if it doesn't exist
        try {
            $this->conn->exec("ALTER TABLE chicks ADD COLUMN egg_id INT AFTER batch_name");
        } catch (PDOException $e) {
            // Column already exists, ignore
        }
        
        // Make batch_name allow default value
        try {
            $this->conn->exec("ALTER TABLE chicks MODIFY COLUMN batch_name VARCHAR(255) DEFAULT ''");
        } catch (PDOException $e) {
            // Already modified, ignore
        }
        
        // Add incubator_id column to chicks table if not exists
        try {
            $this->conn->exec("ALTER TABLE chicks ADD COLUMN incubator_id INT AFTER egg_id");
        } catch (PDOException $e) {
            // Column already exists, ignore
        }
        
        // Add customer_id column to chicks table if not exists
        try {
            $this->conn->exec("ALTER TABLE chicks ADD COLUMN customer_id INT AFTER incubator_id");
        } catch (PDOException $e) {
            // Column already exists, ignore
        }
    }
    
    public function query($sql) {
        $this->stmt = $this->conn->prepare($sql);
    }
    
    public function bind($param, $value, $type = null) {
        if(is_null($type)) {
            switch(true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }
    
    public function execute() {
        return $this->stmt->execute();
    }
    
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Create global database instance
$db = new Database();
?>
