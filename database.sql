-- Database E-Commerce
CREATE DATABASE ecommerce_db;
USE ecommerce_db;

-- Tabel Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tabel Orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Order Items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabel Cart
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) 
VALUES ('admin', 'admin@ecommerce.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Insert sample categories
INSERT INTO categories (name, description) VALUES 
('Elektronik', 'Produk elektronik dan gadget'),
('Fashion', 'Pakaian dan aksesoris'),
('Makanan', 'Makanan dan minuman');

-- Insert sample products
INSERT INTO products (category_id, name, description, price, stock, image) VALUES 
(1, 'Laptop Gaming', 'Laptop gaming high performance', 15000000.00, 10, 'laptop.jpg'),
(1, 'Smartphone', 'Smartphone terbaru 2024', 5000000.00, 25, 'phone.jpg'),
(2, 'Kaos Polos', 'Kaos polos cotton combed', 75000.00, 100, 'shirt.jpg'),
(3, 'Kopi Arabica', 'Kopi arabica premium 250gr', 50000.00, 50, 'coffee.jpg');

-- 1. Tabel Payments (Sistem Pembayaran)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('unpaid', 'pending', 'verified', 'rejected') DEFAULT 'unpaid',
    payment_proof VARCHAR(255), -- Nama file bukti transfer
    bank_account VARCHAR(100), -- Nomor rekening pengirim
    account_name VARCHAR(100), -- Nama pengirim
    payment_date DATETIME,
    verified_by INT, -- ID Admin yang verifikasi
    verified_at DATETIME,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 2. Tabel Shipping (Pelacakan Pengiriman)
CREATE TABLE shipping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    tracking_number VARCHAR(100), -- Nomor resi
    courier VARCHAR(50), -- JNE, TIKI, JNT, etc
    shipping_status ENUM('processing', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered') DEFAULT 'processing',
    estimated_delivery DATE,
    actual_delivery DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 3. Tabel Tracking History (Timeline Detail)
CREATE TABLE tracking_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipping_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    location VARCHAR(200),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipping_id) REFERENCES shipping(id) ON DELETE CASCADE
);

-- 4. Modifikasi Tabel Orders (Tambah Kolom)
ALTER TABLE orders 
ADD COLUMN payment_status ENUM('unpaid', 'pending', 'verified', 'rejected') DEFAULT 'unpaid' AFTER status,
ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0 AFTER total_amount;

-- 5. Insert Sample Bank Info (Opsional)
CREATE TABLE bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(50) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data rekening toko
INSERT INTO bank_accounts (bank_name, account_number, account_name) VALUES
('BCA', '1234567890', 'E-Commerce Store'),
('Mandiri', '0987654321', 'E-Commerce Store'),
('BNI', '1122334455', 'E-Commerce Store');