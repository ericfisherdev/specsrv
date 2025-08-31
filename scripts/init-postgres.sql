-- PostgreSQL initialization script for SpecSrv application
-- This script runs when the PostgreSQL container starts for the first time

-- Create additional databases for different environments
CREATE DATABASE specsrv_test;
CREATE DATABASE specsrv_staging;

-- Grant all privileges to the specsrv-db-user
GRANT ALL PRIVILEGES ON DATABASE specsrv_dev TO "specsrv-db-user";
GRANT ALL PRIVILEGES ON DATABASE specsrv_test TO "specsrv-db-user";
GRANT ALL PRIVILEGES ON DATABASE specsrv_staging TO "specsrv-db-user";

-- Create extensions that might be useful
\c specsrv_dev;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

\c specsrv_test;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

\c specsrv_staging;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Switch back to default database
\c specsrv_dev;