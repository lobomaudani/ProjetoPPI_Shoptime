Migration notes for adding discount, createdAt and visitas table

1) Backup your database

Before running any migration, create a full dump of your database:

On Windows (PowerShell):

mysqldump -u <user> -p <database_name> > backup_before_migration.sql

2) Run migration script

Open your MySQL client and run the provided script `sql/migrate_add_discount_createdat_visitas.sql`.
It uses `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` and `CREATE TABLE IF NOT EXISTS` (MySQL 8+). If you're on an older MySQL, modify accordingly.

3) Recalculate FavoritosCount (optional but recommended)

If you already have data in `favoritos`, update `produtos.FavoritosCount` once after migration:

UPDATE produtos p SET FavoritosCount = (SELECT COUNT(*) FROM favoritos f WHERE f.Produtos_idProdutos = p.idProdutos);

4) Triggers

If your DB does not have the triggers that keep `FavoritosCount` up-to-date, add them (requires DELIMITER in your client). See the migration script for sample triggers.

5) Verifications

- Check that `produtos` has columns `TemDesconto`, `Desconto`, `CreatedAt`, `FavoritosCount`.
- Check that `visitas` table exists and you can insert a row.
- Test the site product page and the recommender endpoint `recommendations.php`.

6) Rollback

If you need to rollback, restore the DB from the backup created in step 1.

If you want, I can produce a small PHP script that runs safe checks before applying the migration (e.g., detect MySQL version and ask for confirmation) and execute the migration for you if you give permission to run SQL locally.
