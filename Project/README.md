# Crime Investigation Management System

PHP + MySQL academic DBMS project from the Software Design Document. Future SDD items (roles, maps, file attachments, PDF export, audit trail, notifications, case timeline) are **out of scope** and are not implemented.

## Run locally (XAMPP / WAMP)

1. Copy this folder into `htdocs` (or point the web root here).
2. Start Apache and MySQL. Default connection is `root` with an empty password. Override with `CIMS_DB_HOST`, `CIMS_DB_NAME`, `CIMS_DB_USER`, `CIMS_DB_PASS` if needed.
3. Open `http://localhost/DBMS%20project/sql/install.php` (adjust the folder name) once. It creates `crime_investigation`, tables, constraints, and sample rows with hashed passwords.
4. Log in at `index.php`.

**Demo login:** `kevin.m@example.com` / `Admin@123`

## What to show in the viva

- Schema and FKs: `sql/schema.sql`
- Extra SQL (CREATE/ALTER/DROP, LIKE, BETWEEN, DISTINCT): `sql/demo_queries.sql`
- Live aggregates, joins, HAVING, subquery, outer join: **Reports** page
- Try deleting a location that still has cases — the app should refuse (RESTRICT)

## Layout

```
index.php, dashboard.php
config/database.php
auth/login.php, auth/logout.php
includes/helpers.php, header.php, footer.php
modules/{cases,officers,suspects,victims,evidence,teams,locations,reports}/
assets/css/style.css, assets/js/app.js
sql/schema.sql, install.php, demo_queries.sql
```
