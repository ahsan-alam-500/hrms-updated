# HRMS Backend

This is the backend for a Human Resource Management System (HRMS). It is built with Laravel and provides a RESTful API for managing employees, departments, attendance, leaves, payroll, and more. It uses JWT for authentication and Spatie's Laravel Permission for role-based access control.

For detailed information on API endpoints, database schema, and features, please see the **[Full Documentation](documentation.md)**.

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

## Contributing

Contributions are welcome! Please feel free to submit a pull request.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).