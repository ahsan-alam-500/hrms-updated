
# HRMS Backend Documentation

## Introduction

This is the backend for a Human Resource Management System (HRMS). It is built with Laravel and provides a RESTful API for managing employees, departments, attendance, leaves, payroll, and documents. It uses JWT for authentication and Spatie's Laravel Permission for role-based access control.

## Features

*   User authentication (register, login, logout, password reset)
*   JWT-based API authentication
*   Role-based access control
*   CRUD operations for departments, employees, attendance, leaves, payroll, and documents.

## Installation

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/ahsan-alam-500/hrms-updated.git
    ```
2.  **Install dependencies:**
    ```bash
    composer install
    npm install
    ```
3.  **Create a copy of the `.env.example` file and name it `.env`:**
    ```bash
    cp .env.example .env
    ```
4.  **Generate an application key:**
    ```bash
    php artisan key:generate
    ```
5.  **Configure your database in the `.env` file.**
6.  **Run the database migrations:**
    ```bash
    php artisan migrate
    ```
7.  **Run the database seeders (optional):**
    ```bash
    php artisan db:seed
    ```

## API Endpoints

All endpoints are prefixed with `/api/v1`.

### Public Routes

| Method | URI                  | Description              |
| :----- | :------------------- | :----------------------- |
| POST   | `/register`          | Register a new user.     |
| POST   | `/login`             | Login a user.            |
| POST   | `/logout`            | Logout a user.           |
| POST   | `/forgotpassword`    | Request a password reset.|
| POST   | `/optvalidation`     | Validate OTP.            |
| POST   | `/resetpassword`     | Reset the password.      |

### Protected Routes (require JWT authentication)

| Method      | URI             | Description                        |
| :---------- | :-------------- | :--------------------------------- |
| GET         | `/profile`      | Get the user's profile.            |
| POST        | `/logout`       | Logout a user.                     |
| `apiResource` | `/departments`  | CRUD for departments.              |
| `apiResource` | `/employees`    | CRUD for employees.                |
| `apiResource` | `/attendances`  | CRUD for attendances.              |
| `apiResource` | `/leaves`       | CRUD for leaves.                   |
| `apiResource` | `/payrolls`     | CRUD for payrolls.                 |
| `apiResource` | `/documents`    | CRUD for documents.                |

## Database Schema

The database schema is defined by the migration files in the `database/migrations` directory. The main models are:

*   **User:** Stores user information and handles authentication.
*   **Department:** Stores department information.
*   **Employee:** Stores employee information.
*   **Attendance:** Stores employee attendance records.
*   **Leave:** Stores employee leave records.
*   **Payroll:** Stores employee payroll records.
*   **EmployeeDocument:** Stores employee documents.
*   **Bonus:** Stores employee bonus information.
*   **ProjectIncentives:** Stores project incentive information for employees.
*   **Projects:** Stores project information.
*   **Quarter:** Stores quarter information for targets and bonuses.
*   **EmployeeTarget:** Stores employee target information.
*   **Holiday:** Stores holiday information.

## How to Run

1.  **Start the development server:**
    ```bash
    php artisan serve
    ```
2.  **Start the queue worker:**
    ```bash
    php artisan queue:work
    ```
3.  **Start the Vite development server:**
    ```bash
    npm run dev
    ```

You can also use the `dev` script to run all three commands concurrently:
```bash
npm run dev
```
