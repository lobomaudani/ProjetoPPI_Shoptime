@echo off
SET PATH=C:\laragon\bin\php;%PATH%
cd /d "%~dp0"
php connections\migrate_images_to_blob.php
pause