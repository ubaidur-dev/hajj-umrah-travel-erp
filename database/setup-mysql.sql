-- Run once as MySQL root (phpMyAdmin > SQL tab), then set DB_* in .env and run: php artisan migrate --seed
CREATE DATABASE IF NOT EXISTS gulshan_e_harmain CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'gulshan'@'localhost' IDENTIFIED BY 'Gulshan#2026';
GRANT ALL PRIVILEGES ON gulshan_e_harmain.* TO 'gulshan'@'localhost';
FLUSH PRIVILEGES;
