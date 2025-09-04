# pgAdmin Setup Guide

This guide explains how to use pgAdmin to manage the PostgreSQL database in the SpecSrv development environment.

## 🐳 Docker Configuration

pgAdmin has been added to the development Docker setup in `docker-compose.dev.yml`:

```yaml
specsrv-pgadmin:
  image: dpage/pgadmin4:8.13
  container_name: specsrv-pgadmin-dev
  env_file: .env.dev
  environment:
    - PGADMIN_DEFAULT_EMAIL=${PGADMIN_DEFAULT_EMAIL}
    - PGADMIN_DEFAULT_PASSWORD=${PGADMIN_DEFAULT_PASSWORD}
  ports:
    - "5050:80"
```

**Security Note**: Create a `.env.dev` file with secure credentials:

```bash
# .env.dev
PGADMIN_DEFAULT_EMAIL=admin@specsrv.local
PGADMIN_DEFAULT_PASSWORD=your_secure_password_here
```

**Important**: Add `.env.dev` to your `.gitignore` to prevent committing credentials to the repository.

## 🚀 Starting pgAdmin

### Option 1: Start all services including pgAdmin
```bash
docker-compose -f docker-compose.dev.yml up -d
```

### Option 2: Start just pgAdmin and PostgreSQL
```bash
docker-compose -f docker-compose.dev.yml up specsrv-pgadmin specsrv-postgres-dev -d
```

## 🌐 Accessing pgAdmin

1. **Open your browser** and navigate to: http://localhost:5050

2. **Login Credentials:**
   - **Email:** Use the value from your `.env.dev` file (`PGADMIN_DEFAULT_EMAIL`)
   - **Password:** Use the value from your `.env.dev` file (`PGADMIN_DEFAULT_PASSWORD`)

## 🔌 Adding PostgreSQL Server Connection

After logging into pgAdmin, you need to add the PostgreSQL server:

### Step 1: Add New Server
1. Right-click on "Servers" in the left panel
2. Select "Register" → "Server..."

### Step 2: Configure Connection
**General Tab:**
- **Name:** `SpecSrv Dev Database`

**Connection Tab:**
- **Host name/address:** `specsrv-postgres-dev`
- **Port:** `5432`
- **Username:** `specsrv-db-user`
- **Password:** `specsrv1234`
- **Maintenance database:** `specsrv_dev`

**Advanced Tab:**
- **DB restriction:** `specsrv_dev` (optional, to show only our database)

### Step 3: Save and Connect
Click "Save" to add the server connection.

## 📊 Database Information

- **Database Name:** `specsrv_dev`
- **Username:** `specsrv-db-user`
- **Password:** `specsrv1234`
- **Port:** `5433` (external), `5432` (internal Docker network)

## 🛠️ Common Tasks

### Viewing Tables
1. Expand: Servers → SpecSrv Dev Database → Databases → specsrv_dev → Schemas → public → Tables

### Running Queries
1. Right-click on "specsrv_dev" database
2. Select "Query Tool"
3. Write and execute SQL queries

### Managing Users
1. Navigate to: Servers → SpecSrv Dev Database → Login/Group Roles

## 🐛 Troubleshooting

### Cannot Connect to Database
- Ensure PostgreSQL container is running: `docker ps | grep postgres`
- Check if the database is healthy: `docker-compose -f docker-compose.dev.yml ps`

### pgAdmin Won't Load
- Check if pgAdmin container is running: `docker ps | grep pgadmin`
- Verify port 5050 is not in use: `lsof -i :5050`

### Connection Refused
- Make sure you're using the internal Docker hostname `specsrv-postgres-dev`
- Verify both containers are on the same network: `specsrv-dev-network`

## 🧹 Cleanup

To remove pgAdmin data and start fresh:
```bash
docker-compose -f docker-compose.dev.yml down
docker volume rm specsrv_specsrv-dev-pgadmin-data
```