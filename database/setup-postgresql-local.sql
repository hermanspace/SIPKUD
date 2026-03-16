-- Setup PostgreSQL untuk local development
-- Jalankan sebagai superuser (postgres): psql -U postgres -f database/setup-postgresql-local.sql

-- Buat database
CREATE DATABASE "SIPKUDDB"
    WITH 
    OWNER = postgres
    ENCODING = 'UTF8'
    LC_COLLATE = 'en_US.UTF-8'
    LC_CTYPE = 'en_US.UTF-8'
    TABLESPACE = pg_default
    CONNECTION LIMIT = -1;

-- Buat user
CREATE USER sipkuddbuser WITH PASSWORD 'sipkuddbpass';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE "SIPKUDDB" TO sipkuddbuser;

-- PostgreSQL 15+: grant schema public (default)
\connect "SIPKUDDB"
GRANT ALL ON SCHEMA public TO sipkuddbuser;
GRANT CREATE ON SCHEMA public TO sipkuddbuser;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO sipkuddbuser;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO sipkuddbuser;
