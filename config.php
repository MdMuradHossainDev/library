<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'library_management';

// Create connection
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if (!mysqli_query($conn, $sql)) {
    die("Error creating database: " . mysqli_error($conn));
}

// Select database
mysqli_select_db($conn, $database);

// Create tables if not exist
$tables = [
    "CREATE TABLE IF NOT EXISTS books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book_no VARCHAR(50) UNIQUE NOT NULL,
        book_name VARCHAR(255) NOT NULL,
        author_name VARCHAR(255) NOT NULL,
        publisher VARCHAR(255),
        copies INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        category VARCHAR(100),
        rack_no VARCHAR(50),
        added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        joined_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS borrowings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book_id INT NOT NULL,
        member_id INT NOT NULL,
        borrow_date DATE NOT NULL,
        due_date DATE NOT NULL,
        return_date DATE,
        fine_amount DECIMAL(10,2) DEFAULT 0,
        status ENUM('borrowed', 'returned') DEFAULT 'borrowed',
        FOREIGN KEY (book_id) REFERENCES books(id),
        FOREIGN KEY (member_id) REFERENCES members(id)
    )",
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $sql) {
    if (!mysqli_query($conn, $sql)) {
        die("Error creating table: " . mysqli_error($conn));
    }
}

// Create admin user if not exists
$admin_check = mysqli_query($conn, "SELECT id FROM users WHERE username = 'admin'");
if (mysqli_num_rows($admin_check) == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password, email, role) VALUES ('admin', '$admin_password', 'admin@example.com', 'admin')";
    if (!mysqli_query($conn, $sql)) {
        die("Error creating admin user: " . mysqli_error($conn));
    }
}

// Set timezone
date_default_timezone_set('Asia/Dhaka');
?>
