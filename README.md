School Management & E-Commerce System

A comprehensive web-based application designed to bridge the gap between school administration, teachers, and students. This platform allows schools to manage their staff, teachers to manage educational products, and students/users to purchase items via an integrated e-commerce shop.

🚀 Features

🏫 For School Principals/Admins

School Registration & Login: Secure onboarding for schools.

Dashboard: specialized PrincipalAdmin.php dashboard for overview.

Teacher Management: Add, remove, and manage teacher accounts.

Reports: View school reports and analytics.

Settings: Manage school profile and configuration.

👩‍🏫 For Teachers

Dedicated Portal: Secure login for teachers.

Product Management: Upload (upload_product.php), edit, and delete products.

Order Management: Oversee product related queries or status (based on implementation).

🎓 For Students/Users

User Accounts: Sign up and login functionality.

E-Commerce Shop: Browse products in the Shop.html interface.

Shopping Features: * View product details (Product.html).

Add delivery addresses.

Process orders.

Profile Management: Update personal details and view order history.

Leaderboard: Gamification element to track engagement.

🛠️ Tech Stack

Frontend: HTML5, CSS3 (shop.css, teacher_admin.css), JavaScript

Backend: PHP (Native)

Database: MySQL

Server: Apache (via XAMPP/WAMP/MAMP)

📂 Project Structure

├── images/                 # Static images for the UI
├── product_images/         # Uploaded product images
├── config.php              # Database configuration file
├── db_setup.php            # Database initialization script
├── PrincipalAdmin.php      # Main dashboard for Principals
├── TeacherAdmin.php        # Main dashboard for Teachers
├── Shop.html               # Main shopping interface for Users
├── Product.php             # Product details logic
├── manage_*.php            # various management scripts (members, products, teachers)
└── ...


⚙️ Installation & Setup

Prerequisites

A local server environment (e.g., XAMPP, WAMP, or MAMP) installed on your machine.

Git (optional, for cloning).

Steps

Clone the Repository

git clone [https://github.com/Kishang0106R/Minor-Project-web-version.git](https://github.com/Kishang0106R/Minor-Project-web-version.git)


Or download the ZIP and extract it.

Move to Web Root

Copy the extracted project folder to your server's root directory.

XAMPP: C:\xampp\htdocs\

WAMP: C:\wamp64\www\

MAMP: /Applications/MAMP/htdocs/

Database Setup

Open phpMyAdmin (usually at http://localhost/phpmyadmin).

Create a new database (e.g., named school_project_db).

Import Schema: * Check if there is a .sql file in the repo to import.

Alternatively, run the db_setup.php script in your browser (see step 5) if it is designed to auto-create tables.

Configure Connection

Open config.php in a text editor.

Update the database credentials to match your local setup:

$servername = "localhost";
$username = "root";       // Default XAMPP user
$password = "";           // Default XAMPP password (leave empty)
$dbname = "school_project_db"; // Your database name


Run the Application

Start your Apache and MySQL modules in XAMPP/WAMP.

Open your browser and navigate to:
http://localhost/Minor-Project-web-version/

🔑 Usage

Principal: Navigate to SchoolLogin.php or SchoolRegistration.html to start managing the institution.

Teacher: Use TeacherLogin.html to access the teacher dashboard.

Student: Use UserLogin.html or Shop.html to browse and purchase items.

🤝 Contributing

Contributions are welcome! Please fork the repository and submit a pull request for any enhancements or bug fixes.

📄 License

This project is open-source. Please check the repository for license details.
