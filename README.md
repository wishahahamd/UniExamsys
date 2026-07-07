# Examsys - University Examination Management System

A premium web-based academic performance evaluation and examination management system for students, faculty, and administrators.

## Project Credentials

### 1. Administrator Portal
- **Login URL**: `http://localhost/UniExamsys/admin_login.php`
- **Username**: `admin`
- **Password**: `admin123`

### 2. Teacher Portal
- **Login URL**: `http://localhost/UniExamsys/login.php?role=teacher`
- **Username**: `teacher1`
- **Password**: `admin123`

### 3. Student Portal
- **Login URL**: `http://localhost/UniExamsys/login.php?role=student`
- **Username**: `mueez` (or Roll Number `S-BBA-2026-99`)
- **Password**: `student123`

---

## Installation & Setup Instructions

### Prerequisites
- **XAMPP** (Apache & MySQL/MariaDB)

### Step 1: Copy the Code
Ensure the project folder `UniExamsys` is placed inside your local web server root directory:
`C:\xampp\htdocs\UniExamsys`

### Step 2: Database Setup
1. Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. Open your browser and navigate to **phpMyAdmin**: `http://localhost/phpmyadmin/`.
3. Create a new database named `examsys`.
4. Select the `examsys` database and click the **Import** tab.
5. Choose the SQL database file located inside the project's `DB` folder:
   `C:\xampp\htdocs\UniExamsys\DB\database.sql`
6. Click **Import** (or Go) to execute the schema setup and database seeding.

### Step 3: Run the Application
Open your browser and navigate to:
`http://localhost/UniExamsys/`
