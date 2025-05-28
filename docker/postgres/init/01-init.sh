#!/bin/bash
set -e

# This script initializes the PostgreSQL database
# It runs automatically when the container is created

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    -- Create extensions if needed
    CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

    -- Additional database setup can be added here
EOSQL

echo "PostgreSQL database initialized successfully"
