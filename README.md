# E-Commerce Database Management System

A university project developed for the **Databases 2 (Baze podataka 2)** course at the **Faculty of Organization and Informatics (FOI), University of Zagreb**.

The project focuses on designing and implementing a relational database for an online store, with a simple **PHP administration interface** for managing order items and inventory. Business rules are implemented directly in PostgreSQL using **PL/pgSQL triggers**.

## Technologies and Tools

- **PostgreSQL** – relational database, constraints, and SQL queries
- **PL/pgSQL** – database functions and triggers
- **PHP (PDO)** – database connectivity and administration forms
- **XAMPP (Apache)** – local PHP web server
- **JetBrains DataGrip** – database design and SQL development
- **Visual Studio Code** – PHP development

## Database Design

The database consists of **10 related tables**:

| Table | Purpose |
| --- | --- |
| `korisnik` | Customers / system users |
| `adresa` | Customer addresses |
| `kategorija` | Product categories and subcategories |
| `proizvod` | Products and prices |
| `skladiste` | Warehouses |
| `zaliha` | Product quantities by warehouse |
| `narudzba` | Customer orders |
| `stavke_narudzbe` | Order items |
| `dostava` | Order deliveries |
| `placanje` | Order payments |

The schema uses primary and foreign keys, referential integrity constraints, and validation rules. All project tables are created in the PostgreSQL schema `projekt`.

## Main Features

### Order Item Management

The `forma_stavke.php` administration page provides:

- Selection of an existing order and viewing its items and total value
- Adding products to an order (**INSERT**)
- Updating ordered quantities (**UPDATE**)
- Removing order items (**DELETE**)

### Inventory Management

The `zaliha.php` page provides:

- Viewing product stock levels across warehouses
- Adding inventory records
- Updating existing inventory quantities

### Database Triggers

Two PostgreSQL triggers enforce key business rules:

1. **`trg_izracun_ukupno`** recalculates an order's total after an order item is inserted, updated, or deleted.
2. **`trg_smanji_zalihu`** adjusts warehouse inventory when order items change, restores inventory after reductions or deletions, and rejects operations when stock is insufficient.

## SQL Queries

The project includes examples of:

- Basic SQL queries using `COUNT`, `SUM`, and `MAX`
- Multi-table queries using `JOIN`
- Aggregation and grouping with `GROUP BY` and `ORDER BY`

## Repository Files

| File | Description |
| --- | --- |
| [`database.sql`](database.sql) | Creates the PostgreSQL schema and 10 tables |
| [`trigger.sql`](trigger.sql) | Defines two PL/pgSQL functions and their triggers |
| [`queries.sql`](queries.sql) | Contains simple and complex SQL queries |
| [`forma_stavke.php`](forma_stavke.php) | Order item administration interface |
| [`zaliha.php`](zaliha.php) | Warehouse inventory administration interface |
| [`ValentinoMihalinac.pdf`](ValentinoMihalinac.pdf) | Full project documentation, including ER diagrams and screenshots |

The PHP pages also require a local `db.php` connection file. **Do not publish `db.php` if it contains real database credentials.**

## Local Setup

1. Install **PostgreSQL**, **PHP**, and a local web server such as **XAMPP**. Ensure the PHP `pdo_pgsql` extension is enabled.
2. Create a PostgreSQL database (or use an existing local database).
3. Run **`database.sql`**, followed by **`trigger.sql`**, against that database. These scripts use the `projekt` schema.
4. Add your own **test data** for products, customers, orders, warehouses, and stock. Sample records are not included in the SQL scripts.
5. Create a local `db.php` file alongside the PHP pages. Configure its PDO connection for your PostgreSQL host, port, database name, and credentials, and set `search_path` to `projekt`.
6. Place the PHP files in your XAMPP `htdocs` project folder and start Apache.
7. Open the following pages in your browser (replace `ecommerce-database-system` if your local folder has a different name):

   - `http://localhost/ecommerce-database-system/forma_stavke.php`
   - `http://localhost/ecommerce-database-system/zaliha.php`

The `queries.sql` file can be run separately in DataGrip or another PostgreSQL SQL client after the database contains test data.

> **Security note:** Keep credentials out of GitHub. Add `db.php` and `.env` to `.gitignore` before publishing the project. This is an educational prototype, not a production-ready online store.

## Documentation

The complete written report is available in **[ValentinoMihalinac.pdf](ValentinoMihalinac.pdf)**. It contains the ER model, entity descriptions, example SQL queries, trigger implementations, and screenshots of the PHP interfaces.

## Author

**Valentino Mihalinac**  
Faculty of Organization and Informatics (FOI), University of Zagreb  
Academic year **2025/2026**
