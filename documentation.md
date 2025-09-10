# HRMS Backend Documentation

## Introduction

This is the backend for a Human Resource Management System (HRMS). It is built with Laravel and provides a RESTful API for managing employees, departments, attendance, leaves, payroll, and documents. It uses JWT for authentication and Spatie's Laravel Permission for role-based access control.

## Features

*   User authentication (register, login, logout, password reset, email verification)
*   JWT-based API authentication
*   Role-based access control
*   CRUD operations for departments, employees, attendance, leaves, payroll, documents, projects, teams, and more.
*   Employee shift management
*   Holiday and notice management
*   Project and team management
*   Employee target tracking

## Installation

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/your-username/hrms_backend_updated.git
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

| Method | URI                  | Description                |
| :----- | :------------------- | :------------------------- |
| POST   | `/register`          | Register a new user.       |
| POST   | `/emailverification` | Verify user's email.       |
| POST   | `/login`             | Login a user.              |
| POST   | `/logout`            | Logout a user.             |
| POST   | `/forgotpassword`    | Request a password reset.  |
| POST   | `/optvalidation`     | Validate OTP.              |
| POST   | `/resetpassword`     | Reset the password.        |

### Protected Routes (require JWT authentication)

| Method      | URI                        | Description                                      |
| :---------- | :------------------------- | :----------------------------------------------- |
| GET         | `/profile`                 | Get the user's profile.                          |
| POST        | `/logout`                  | Logout a user.                                   |
| `apiResource` | `/admin/dashboard`         | Get admin dashboard summary data.                |
| `apiResource` | `/departments`             | CRUD for departments.                            |
| `apiResource` | `/shifts`                  | CRUD for working shifts.                         |
| GET         | `/shift/assign`            | Get employees and shifts for assignment.         |
| PUT         | `/shift/assign/{id}`       | Assign an employee to a shift.                   |
| `apiResource` | `/employees`               | CRUD for employees.                              |
| GET         | `/employee/attributes`     | Get attributes for creating/editing an employee. |
| GET         | `/newusers`                | Get a list of newly registered users.            |
| POST        | `/newusers/active`         | Activate or deactivate new users.                |
| `apiResource` | `/attendances`             | CRUD for attendances.                            |
| POST        | `/attendance/filter`       | Filter attendance records.                       |
| POST        | `/attendance/filter/{id}`  | Filter personal attendance records for an employee.|
| GET         | `/employee/attendance/{id}`| Get attendance for a specific employee.          |
| POST        | `/attendance/sync`         | Sync attendance data.                            |
| `apiResource` | `/leaves`                  | CRUD for leaves.                                 |
| `apiResource` | `/payrolls`                | CRUD for payrolls.                               |
| `apiResource` | `/documents`               | CRUD for documents.                              |
| `apiResource` | `/holiday`                 | CRUD for holidays.                               |
| `apiResource` | `/notice`                  | CRUD for notices.                                |
| `apiResource` | `/teams`                   | CRUD for teams.                                  |
| `apiResource` | `/projects`                | CRUD for projects.                               |
| GET         | `/project/attributes`      | Get attributes for creating/editing a project.   |
| GET         | `/project/groups`          | Get projects grouped by status.                  |
| `apiResource` | `/maketeams`               | Assign employees to teams.                       |
| `apiResource` | `/notifications`           | CRUD for notifications.                          |
| POST        | `/notification/{id}`       | Mark a notification as read.                     |
| `apiResource` | `/targets`                 | CRUD for employee targets.                       |
| `apiResource` | `/objections`              | CRUD for employee objections/grievances.         |
| `apiResource` | `/employeeleave`           | Employee's personal leave requests.              |
| `apiResource` | `/employeeproject`         | Employee's personal projects.                    |
| GET         | `/employeeprojects/groups` | Get employee's projects grouped by status.       |

### Automation Routes

These routes are intended for automated processes, such as integrating with attendance machines.

| Method | URI                  | Description                               |
| :----- | :------------------- | :---------------------------------------- |
| POST   | `/local/attendance`  | Bulk store attendance data from a machine.|
| GET    | `/local/set/users`   | Get all users formatted for a machine.    |
| POST   | `/activate/{id}`     | Activate a user account.                  |

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
*   **Holiday:** Stores public holiday information.
*   **PersonalHoliday:** Stores individual employee holidays.
*   **WorkingShift:** Defines different working shifts (e.g., morning, night).
*   **EmployeeHasShift:** Manages the assignment of employees to shifts.
*   **Team:** Represents a team within the organization.
*   **TeamHasEmployee:** Manages employee membership in teams.
*   **ProjectHasEmployee:** Manages employee assignment to projects.
*   **Notice:** Stores announcements or notices for employees.
*   **Notification:** Represents individual notifications sent to users.
*   **EmployeeHasNotification:** Manages the read/unread status of notifications for employees.
*   **Objection:** Stores employee objections or grievances.

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