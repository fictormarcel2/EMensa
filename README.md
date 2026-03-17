# E Mensa Web Application

Hello! I am Fictor marcelbrian, an Informatics student at FH Aachen.
This repository contains my E Mensa web application,
which I built completely from scratch for my Database and Web Technologies module.

This project is a fully functional cafeteria management and ordering system.
I used a custom MVC architecture to handle routing, secure user sessions, and database models.

## Technologies Used
* Backend: PHP 8
* Database: MariaDB
* Frontend: HTML5, CSS3

## How to Run This Project Locally

If you want to test this application on your own machine, here is the quick setup guide:

1. Clone this repository to your local computer.
2. **Database Setup:** Open your database manager (like phpMyAdmin) and
create a new database named `emensawerbeseite`.
3. Run the `database/init.sql` script included in this repository.
This will automatically build all the required tables,
establish the relational keys, and insert the test data.
4. **Connect the App:** Open the database configuration file in the project
(usually in the `models` or `config` folder) and
5. update the `mysqli_connect` line with your local database username and password.
6. Start the local PHP development server by opening your terminal
in the main folder and running the command below:
   `php -S localhost:8000 -t public`
7. Open your browser and go to `http://localhost:8000`.

## Demo Account
You can log in and test the secure features (like the visitor counter and wishlist system)
using this admin account:
* **Email:** admin@emensa.example
* **Password:** admin123