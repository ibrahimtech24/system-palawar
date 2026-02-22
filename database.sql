-- سیستەمی بەڕێوەبردنی مریشک و جوجکە
-- Database: poultry_system

CREATE DATABASE IF NOT EXISTS poultry_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE poultry_system;

-- جەدوەلی مەخزەن (Warehouse)
CREATE TABLE IF NOT EXISTS warehouse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    category ENUM('feed', 'medicine', 'equipment', 'other') NOT NULL DEFAULT 'other',
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL,
    price_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جەدوەلی هەوێردەی نێرە (Male Birds)
CREATE TABLE IF NOT EXISTS male_birds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bird_code VARCHAR(50) UNIQUE NOT NULL,
    breed VARCHAR(100) NOT NULL,
    birth_date DATE,
    age_months INT DEFAULT 0,
    weight DECIMAL(5,2) DEFAULT 0.00,
    health_status ENUM('healthy', 'sick', 'recovering', 'dead') DEFAULT 'healthy',
    purchase_price DECIMAL(10,2) DEFAULT 0.00,
    purchase_date DATE,
    notes TEXT,
    status ENUM('active', 'sold', 'dead') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جەدوەلی هەوێردەی مێیە (Female Birds)
CREATE TABLE IF NOT EXISTS female_birds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bird_code VARCHAR(50) UNIQUE NOT NULL,
    breed VARCHAR(100) NOT NULL,
    birth_date DATE,
    age_months INT DEFAULT 0,
    weight DECIMAL(5,2) DEFAULT 0.00,
    health_status ENUM('healthy', 'sick', 'recovering', 'dead') DEFAULT 'healthy',
    egg_production_rate DECIMAL(5,2) DEFAULT 0.00,
    purchase_price DECIMAL(10,2) DEFAULT 0.00,
    purchase_date DATE,
    notes TEXT,
    status ENUM('active', 'sold', 'dead') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جەدوەلی هێلکە (Eggs)
CREATE TABLE IF NOT EXISTS eggs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_code VARCHAR(50) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    collection_date DATE NOT NULL,
    quality ENUM('grade_a', 'grade_b', 'grade_c', 'damaged') DEFAULT 'grade_a',
    female_bird_id INT,
    storage_location VARCHAR(100),
    expiry_date DATE,
    status ENUM('available', 'sold', 'hatching', 'expired') DEFAULT 'available',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (female_bird_id) REFERENCES female_birds(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جەدوەلی جوجکە (Chicks)
CREATE TABLE IF NOT EXISTS chicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_code VARCHAR(50) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    hatch_date DATE NOT NULL,
    age_days INT DEFAULT 0,
    breed VARCHAR(100),
    health_status ENUM('healthy', 'sick', 'dead') DEFAULT 'healthy',
    mortality_count INT DEFAULT 0,
    parent_male_id INT,
    parent_female_id INT,
    notes TEXT,
    status ENUM('growing', 'sold', 'matured') DEFAULT 'growing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_male_id) REFERENCES male_birds(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_female_id) REFERENCES female_birds(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جەدوەلی کڕیاران (Customers)
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    total_purchases DECIMAL(12,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جەدوەلی دابینکەران (Suppliers)
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    total_supplies DECIMAL(12,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جەدوەلی فرۆشتن (Sales)
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_code VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    item_type ENUM('male_bird', 'female_bird', 'eggs', 'chicks', 'warehouse') NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('paid', 'pending', 'partial') DEFAULT 'paid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جەدوەلی کڕین (Purchases)
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_code VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT,
    item_type ENUM('male_bird', 'female_bird', 'eggs', 'chicks', 'warehouse') NOT NULL,
    item_description VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('paid', 'pending', 'partial') DEFAULT 'paid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جەدوەلی مێژووی مامەڵەکان (Transaction History)
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('sale', 'purchase', 'expense', 'income') NOT NULL,
    reference_id INT,
    reference_table VARCHAR(50),
    amount DECIMAL(12,2) NOT NULL,
    description TEXT,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO customers (name, phone, address) VALUES 
('ئەحمەد محەمەد', '07501234567', 'هەولێر'),
('کارزان علی', '07701234567', 'سلێمانی'),
('هێمن کەریم', '07801234567', 'دهۆک');

INSERT INTO suppliers (name, phone, address) VALUES 
('کۆمپانیای خواردنی مریشک', '07501111111', 'بەغداد'),
('فارمی گەورە', '07702222222', 'هەولێر');
