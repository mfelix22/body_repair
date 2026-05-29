#!/bin/bash

# Wait for MySQL to be reachable before running artisan commands
echo "Waiting for database..."
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
  sleep 2
done
echo "Database is ready."

php artisan config:clear
php artisan view:clear
php artisan cache:clear || true
php artisan migrate --force || true

exec apache2-foreground
