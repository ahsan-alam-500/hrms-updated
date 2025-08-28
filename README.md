# HRMS Backend

This is the backend for a Human Resource Management System (HRMS). It is built with Laravel 11 and provides a RESTful API for managing employees, departments, attendance, leaves, payroll, and documents. It uses JWT for authentication and Spatie's Laravel Permission for role-based access control.

## Features

*   User authentication (register, login, logout, password reset)
*   JWT-based API authentication
*   Role-based access control using Spatie's Laravel Permission
*   CRUD operations for:
    *   Departments
    *   Employees
    *   Shifts
    *   Attendances
    *   Leaves
    *   Payrolls
    *   Documents
    *   Holidays
    *   Notices
*   Filter attendance records by date and employee.
*   Sync attendance data from an external source.
*   API for fetching employee attributes.
*   Dashboard API for admin summary data.

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

| Method      | URI                       | Description                                 |
| :---------- | :------------------------ | :------------------------------------------ |
| GET         | `/profile`                | Get the user's profile.                     |
| POST        | `/logout`                 | Logout a user.                              |
| `apiResource` | `/admin/dashboard`        | CRUD for admin summary data.                |
| `apiResource` | `/departments`            | CRUD for departments.                       |
| `apiResource` | `/shifts`                 | CRUD for shifts.                            |
| `apiResource` | `/employees`              | CRUD for employees.                         |
| GET         | `/employee/attributes`    | Get employee attributes.                    |
| `apiResource` | `/attendances`            | CRUD for attendances.                       |
| POST        | `/attendance/filter`      | Filter attendance records.                  |
| POST        | `/attendance/filter/{id}` | Filter attendance records for a user.       |
| GET         | `/employee/attendance/{id}` | Get attendance for a specific employee.     |
| POST        | `/attendance/sync`        | Sync attendance data.                       |
| `apiResource` | `/leaves`                 | CRUD for leaves.                            |
| `apiResource` | `/payrolls`               | CRUD for payrolls.                          |
| `apiResource` | `/documents`              | CRUD for documents.                         |
| `apiResource` | `/holiday`                | CRUD for holidays.                          |
| `apiResource` | `/notice`                 | CRUD for notices.                           |

### Automation Routes

| Method | URI                  | Description                               |
| :----- | :------------------- | :---------------------------------------- |
| POST   | `/local/attendance`  | Bulk store attendance data from a machine.|
| GET    | `/local/set/users`   | Set users to a machine.                   |

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
*   **WorkingShift:** Stores information about working shifts.
*   **EmployeeHasShift:** Maps employees to their shifts.

## Dependencies

### Production
- [laravel/framework](https://laravel.com/): ^12.0
- [laravel/sanctum](https://laravel.com/docs/sanctum): ^4.0
- [laravel/tinker](https://github.com/laravel/tinker): ^2.10.1
- [spatie/laravel-permission](https://spatie.be/docs/laravel-permission/v6/introduction): ^6.21
- [tymon/jwt-auth](https://jwt-auth.readthedocs.io/en/develop/): ^2.2

### Development
- [fakerphp/faker](https://github.com/fakerphp/faker): ^1.23
- [laravel/pail](https://laravel.com/docs/11.x/pail): ^1.2.2
- [laravel/pint](https://github.com/laravel/pint): ^1.13
- [laravel/sail](https://laravel.com/docs/11.x/sail): ^1.41
- [mockery/mockery](https://github.com/mockery/mockery): ^1.6
- [nunomaduro/collision](https://github.com/nunomaduro/collision): ^8.6
- [pestphp/pest](https://pestphp.com/): ^3.8
- [pestphp/pest-plugin-laravel](https://pestphp.com/docs/plugins/laravel): ^3.2

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

## Contributing

Contributions are welcome! Please feel free to submit a pull request.

## License

This project is open-sourced software licensed under the [MIT license](httpshttps://opensource.org/licenses/MIT).
