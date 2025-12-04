<?php
echo "db_setup.php is being executed!<br>";

// Database configuration
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password (empty)
$dbname = "school_management_system"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database '$dbname' created successfully or already exists.<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($dbname);

// Create users table if it doesn't exist
$users_table_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($users_table_sql) === TRUE) {
    echo "Users table created successfully or already exists.<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create teachers table if it doesn't exist
$teachers_table_sql = "CREATE TABLE IF NOT EXISTS teachers (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    subject VARCHAR(50),
    school_name VARCHAR(100),
    designation VARCHAR(50),
    class_assigned VARCHAR(20),
    mobile VARCHAR(15),
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($teachers_table_sql) === TRUE) {
    echo "Teachers table created successfully or already exists.<br>";
} else {
    echo "Error creating teachers table: " . $conn->error . "<br>";
}

// Create principals table if it doesn't exist
$principals_table_sql = "CREATE TABLE IF NOT EXISTS principals (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    school_name VARCHAR(100),
    school_code VARCHAR(50),
    district VARCHAR(50),
    school_type VARCHAR(50),
    address TEXT,
    mobile VARCHAR(15),
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($principals_table_sql) === TRUE) {
    echo "Principals table created successfully or already exists.<br>";
} else {
    echo "Error creating principals table: " . $conn->error . "<br>";
}

// Alter principals table to add missing columns if they don't exist
$alter_principals_sql = "ALTER TABLE principals
    ADD COLUMN IF NOT EXISTS school_code VARCHAR(50),
    ADD COLUMN IF NOT EXISTS district VARCHAR(50),
    ADD COLUMN IF NOT EXISTS school_type VARCHAR(50),
    ADD COLUMN IF NOT EXISTS address TEXT,
    ADD COLUMN IF NOT EXISTS mobile VARCHAR(15),
    ADD COLUMN IF NOT EXISTS principal_name VARCHAR(50),
    ADD COLUMN IF NOT EXISTS zone VARCHAR(50)";

if ($conn->query($alter_principals_sql) === TRUE) {
    echo "Principals table altered successfully.<br>";
} else {
    echo "Error altering principals table: " . $conn->error . "<br>";
}

// Alter teachers table to add missing columns if they don't exist
$alter_teachers_sql = "ALTER TABLE teachers
    ADD COLUMN IF NOT EXISTS school_name VARCHAR(100),
    ADD COLUMN IF NOT EXISTS designation VARCHAR(50),
    ADD COLUMN IF NOT EXISTS class_assigned VARCHAR(20),
    ADD COLUMN IF NOT EXISTS mobile VARCHAR(15)";

if ($conn->query($alter_teachers_sql) === TRUE) {
    echo "Teachers table altered successfully.<br>";
} else {
    echo "Error altering teachers table: " . $conn->error . "<br>";
}

// Alter users table to add points column if it doesn't exist
$alter_users_sql = "ALTER TABLE users
    ADD COLUMN IF NOT EXISTS points INT(11) DEFAULT 0";

if ($conn->query($alter_users_sql) === TRUE) {
    echo "Users table altered successfully (added points column).<br>";
} else {
    echo "Error altering users table: " . $conn->error . "<br>";
}

// Alter users table to add phone and gender columns if they don't exist
$alter_users_profile_sql = "ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone VARCHAR(15),
    ADD COLUMN IF NOT EXISTS gender VARCHAR(10)";

if ($conn->query($alter_users_profile_sql) === TRUE) {
    echo "Users table altered successfully (added phone and gender columns).<br>";
} else {
    echo "Error altering users table: " . $conn->error . "<br>";
}

// Alter users table to add address columns if they don't exist
$alter_users_address_sql = "ALTER TABLE users
    ADD COLUMN IF NOT EXISTS flat_house VARCHAR(255),
    ADD COLUMN IF NOT EXISTS building_apartment VARCHAR(255),
    ADD COLUMN IF NOT EXISTS street_road VARCHAR(255),
    ADD COLUMN IF NOT EXISTS landmark VARCHAR(255),
    ADD COLUMN IF NOT EXISTS area_locality VARCHAR(255),
    ADD COLUMN IF NOT EXISTS pincode VARCHAR(10),
    ADD COLUMN IF NOT EXISTS district VARCHAR(100),
    ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT 'Delhi',
    ADD COLUMN IF NOT EXISTS state VARCHAR(100) DEFAULT 'Delhi (NCT)'";

if ($conn->query($alter_users_address_sql) === TRUE) {
    echo "Users table altered successfully (added address columns).<br>";
} else {
    echo "Error altering users table: " . $conn->error . "<br>";
}

// Create teams table if it doesn't exist
$teams_table_sql = "CREATE TABLE IF NOT EXISTS teams (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    teacher_id INT(6) UNSIGNED NOT NULL,
    school_name VARCHAR(100),
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_name_teacher (team_name, teacher_id)
)";

if ($conn->query($teams_table_sql) === TRUE) {
    echo "Teams table created successfully or already exists.<br>";
} else {
    echo "Error creating teams table: " . $conn->error . "<br>";
}

// Alter teams table to add points column if it doesn't exist
$alter_teams_sql = "ALTER TABLE teams
    ADD COLUMN IF NOT EXISTS points INT(11) DEFAULT 0";

if ($conn->query($alter_teams_sql) === TRUE) {
    echo "Teams table altered successfully (added points column).<br>";
} else {
    echo "Error altering teams table: " . $conn->error . "<br>";
}

// Create products table
$products_table_sql = "CREATE TABLE IF NOT EXISTS products (
    product_id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    product_description TEXT,
    quantity INT(11) DEFAULT 0,
    product_price DECIMAL(10,2) NOT NULL,
    product_image VARCHAR(255),
    status VARCHAR(20) DEFAULT 'active',
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploader_id INT(6) UNSIGNED NOT NULL,
    team_id INT(6) UNSIGNED NOT NULL,
    FOREIGN KEY (uploader_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
)";

if ($conn->query($products_table_sql) === TRUE) {
    echo "Products table created successfully.<br>";
} else {
    echo "Error creating products table: " . $conn->error . "<br>";
}

// Create teams table if it doesn't exist
$teams_table_sql = "CREATE TABLE IF NOT EXISTS teams (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    teacher_id INT(6) UNSIGNED NOT NULL,
    school_name VARCHAR(100),
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_name_teacher (team_name, teacher_id)
)";

if ($conn->query($teams_table_sql) === TRUE) {
    echo "Teams table created successfully or already exists.<br>";
} else {
    echo "Error creating teams table: " . $conn->error . "<br>";
}

// Create team_members table if it doesn't exist
$team_members_table_sql = "CREATE TABLE IF NOT EXISTS team_members (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT(6) UNSIGNED NOT NULL,
    user_id INT(6) UNSIGNED NOT NULL,
    joined_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_user (team_id, user_id)
)";

if ($conn->query($team_members_table_sql) === TRUE) {
    echo "Team members table created successfully or already exists.<br>";
} else {
    echo "Error creating team members table: " . $conn->error . "<br>";
}

// Create orders table if it doesn't exist
$orders_table_sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT(6) UNSIGNED NOT NULL,
    user_id INT(6) UNSIGNED NOT NULL,
    product_id INT(6) UNSIGNED NOT NULL,
    team_id INT(6) UNSIGNED NOT NULL,
    quantity INT(11) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'pending',
    rating TINYINT(1) DEFAULT NULL,
    review TEXT,
    rating_date TIMESTAMP NULL,
    can_rate TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
)";

if ($conn->query($orders_table_sql) === TRUE) {
    echo "Orders table created successfully or already exists.<br>";
} else {
    echo "Error creating orders table: " . $conn->error . "<br>";
}

// Alter orders table to add team_id column if it doesn't exist (for existing databases)
$alter_orders_add_column_sql = "ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS team_id INT(6) UNSIGNED";

if ($conn->query($alter_orders_add_column_sql) === TRUE) {
    echo "Orders table altered successfully (added team_id column).<br>";
} else {
    echo "Error altering orders table (adding column): " . $conn->error . "<br>";
}

// Update existing orders to set team_id from products
$update_orders_sql = "UPDATE orders o
    JOIN products p ON o.product_id = p.product_id
    SET o.team_id = p.team_id
    WHERE o.team_id IS NULL";

if ($conn->query($update_orders_sql) === TRUE) {
    echo "Existing orders updated with team_id.<br>";
} else {
    echo "Error updating existing orders: " . $conn->error . "<br>";
}

// Now add the NOT NULL constraint and foreign key
$alter_orders_constraints_sql = "ALTER TABLE orders
    MODIFY COLUMN team_id INT(6) UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_orders_team_id FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE";

if ($conn->query($alter_orders_constraints_sql) === TRUE) {
    echo "Orders table constraints added successfully.<br>";
} else {
    echo "Error adding constraints to orders table: " . $conn->error . "<br>";
}

echo "Database setup completed successfully!<br>";

$conn->close();
?>
