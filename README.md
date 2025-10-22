ProjetoPPI_Shoptime

Quick deploy & schema instructions

1) Environment
- This project uses PHP + PDO with MySQL (Laragon recommended for Windows local dev).
- DB name expected: `ppi_v02_showtime`.
- DB connection: edit `connections/conectarBD.php` if you need to change host/user/password.

2) Applying schema (safe options)
A) Recommended (production-safe): Use a DB admin (root) and run the SQL file `sql/schema_v02.sql` using your DB client (phpMyAdmin, MySQL Workbench, or mysql CLI). This ensures triggers and FOREIGN KEYs are created with proper privileges.

B) Local / convenience: Use the runner `connections/apply_schema.php`. From the machine running Laragon, open in the browser:

    http://localhost/ProjetoPPI_Shoptime/connections/apply_schema.php?run=1

If you want additional security, create a token file at `connections/.schema_token` containing a random secret, then call:

    http://localhost/ProjetoPPI_Shoptime/connections/apply_schema.php?run=1&token=YOURTOKEN

You can also run the apply script from CLI (safer):

    php connections/apply_schema.php

Note: Triggers and some ALTER statements may require elevated DB privileges. If statements fail due to permissions, run `sql/schema_v02.sql` manually with an admin DB user.

3) Safe defaults for production
- Do NOT enable automatic schema application on every request. The current `connections/conectarBD.php` does not auto-run migrations.
- Use CI/CD deployment scripts to run migrations with an admin account, or run them manually during deploy.

4) Next developer tasks implemented
- `meusProdutos.php` — list user products; edit/delete actions.
- `editarProduto.php` — edit product, add images, remove multiple images.
- `meusFavoritos.php` — list and remove favorites.

5) How to run locally (Laragon / Windows PowerShell)

Open PowerShell in the project folder and start the built-in PHP server (optional for testing without full Apache):

```powershell
php -S localhost:8080 -t .
```

Then open http://localhost:8080 in your browser.

6) Contact / next steps
- If you want, I can add a small migration script for production that requires interactive confirmation and logs changes.
- I can also add an admin-only page to run schema updates securely.
