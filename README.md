# Unlabel - Ecommerce Shop

Welcome to the Unlabel - Ecommerce Shop project! This is a web application built using Vite, Vue 3, and Bootstrap 5, designed to provide a user-friendly e-commerce experience with features like user authentication, profile management, shopping cart, and order tracking. This README provides step-by-step instructions to set up and run the project locally, along with details about the database structure.

## Setup Instructions

### Option 1: Git Clone (Recommended)
1. Clone the repository:
   ```bash
   git clone https://github.com/DesmondChuaLiYi/Unlabel.git
   ```
2. Move the cloned folder to your XAMPP htdocs directory and rename it to Project (otherwise it wouldn't work)
   - Windows: `C:\xampp\htdocs\Project`
   - Linux/macOS: `/opt/lampp/htdocs/Project`

### Option 2: Manual Setup
1. Unzip the project folder and place it inside the htdocs directory of your XAMPP installation
   - Windows: `C:\xampp\htdocs\Project`
   - Linux/macOS: `/opt/lampp/htdocs/Project`

2. Import the Database
   1. Open phpMyAdmin
   2. Import the SQL export file `unlabel.sql` provided with the project
   3. Ensure MySQL and Apache services are running in XAMPP

3. Project Setup
   1. Open the project folder in Visual Studio Code
   2. Open terminal and navigate to the project directory
   3. Install dependencies:
      ```bash
      npm install
      ```
   4. Start the development server:
      ```bash
      npm run dev
      ```
   5. Visit http://localhost:5173 in your browser

### Authentication

#### Default Admin Account
- Email: admin@gmail.com
- Password: P@ss_123
- Note: If the above fails, try password: 04_Cly1102nhn

#### Register New Account
You can register using any valid email address. Password requirements:
- Minimum 8 characters
- Must include letter, number, and symbol (!@#$%^&*_)

## Live Demo
Visit the live version at: https://unlabel.lovestoblog.com/

## Project Overview

### Technology Stack
- Frontend: Vite with Vue 3
- Styling: Bootstrap 5
- Backend: PHP with MySQL
- API: RESTful endpoints

### Features
- User authentication
- Profile management
- Address editing
- Shopping cart
- Purchase history
- Account deletion

### Pages
- Home
- Product
- Product detail
- Shopping cart
- My purchases
- My account 
- Registration
- Login

## Appendix
## Database Structure

### Schema Details
The database uses:
- Character Set: utf8mb4
- Collation: utf8mb4_unicode_ci
- ON DELETE CASCADE for referential integrity

```sql
CREATE DATABASE IF NOT EXISTS unlabel
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE unlabel;

CREATE TABLE user (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  firstName VARCHAR(50) NOT NULL,
  lastName VARCHAR(50) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  profile_picture BLOB DEFAULT NULL,
  birthDate DATE DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  created_dt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_dt DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  last_login_dt DATETIME DEFAULT NULL
);

CREATE TABLE user_address (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id VARCHAR(36) NOT NULL,
  address VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  zipCode VARCHAR(20) NOT NULL,
  country VARCHAR(2) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- Table for user's shopping cart
CREATE TABLE user_cart (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  product_id VARCHAR(50) NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  product_price DECIMAL(10,2) NOT NULL,
  product_image VARCHAR(255),
  quantity INT NOT NULL DEFAULT 1,
  date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- Table for user's purchased items
CREATE TABLE user_purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  order_id VARCHAR(50) NOT NULL,
  product_id VARCHAR(50) NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  product_price DECIMAL(10,2) NOT NULL,
  product_image VARCHAR(255),
  quantity INT NOT NULL,
  purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'completed',
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- Table for orders (to group purchased items)
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id VARCHAR(50) NOT NULL,
  user_id VARCHAR(36) NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'completed',
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
```

## Contact
For any questions or feedback, please reach out to the project developer (Li Yi CHUA).
- Email: 104401021@students.swinburne.edu.my
- WhatsApp: (+60) 16-888-1415
