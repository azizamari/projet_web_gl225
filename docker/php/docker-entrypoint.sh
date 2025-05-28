#!/bin/bash
set -e

echo "Starting PHP container..."

# Create and fix permissions for var directories
echo "Setting up directory permissions..."
mkdir -p /var/www/html/var/cache /var/www/html/var/log
chown -R www-data:www-data /var/www/html/var
chmod -R 777 /var/www/html/var

# Install dependencies if needed
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
  echo "Installing dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Wait for database to be ready (more robust check)
echo "Checking database connection..."
MAX_TRIES=30
COUNT=0
until mysqladmin ping -h database -u ${MYSQL_USER:-app} -p${MYSQL_PASSWORD:-!ChangeMe!} --silent || [ $COUNT -eq $MAX_TRIES ]; do
  echo "Waiting for database to be ready... ($COUNT/$MAX_TRIES)"
  sleep 2
  COUNT=$((COUNT+1))
done

if [ $COUNT -eq $MAX_TRIES ]; then
  echo "WARNING: Could not connect to database after $MAX_TRIES tries, but continuing anyway"
else
  echo "Database is ready!"
fi

# Check if Symfony is properly installed
if [ -f "bin/console" ]; then
  # Handle database setup for MySQL
  echo "Setting up database for MySQL..."
  
  # Keep existing migrations as they're already for MySQL
  echo "Using existing MySQL migrations..."
  
  # Create database if it doesn't exist
  php bin/console doctrine:database:create --if-not-exists --no-interaction || echo "Database creation failed, but continuing"
  
  # Generate migration for MySQL
  php bin/console doctrine:migrations:diff --no-interaction || echo "Migration generation failed, but continuing"
  
  # Apply migrations
  echo "Running database migrations..."
  php bin/console doctrine:migrations:migrate --no-interaction || echo "Migration failed, but continuing"

  # Create admin user if it doesn't exist
  echo "Creating admin user if needed..."
  php bin/console app:create-admin-user ${ADMIN_USERNAME:-admin} ${ADMIN_EMAIL:-admin@example.com} ${ADMIN_PASSWORD:-adminpass} --no-interaction || echo "Admin creation failed, but continuing"
else
  echo "WARNING: Symfony console not found, skipping migrations and admin creation"
fi

# Execute the main command
echo "Starting PHP-FPM..."
exec "$@"
