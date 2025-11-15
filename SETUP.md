# Laravel Application Setup & Configuration Guide

This guide provides step-by-step instructions to initialize and run the Molunzaka Streaming Platform locally.

## Automated Setup (Recommended)

The fastest way to get started is using the automated setup script:

```bash
chmod +x setup.sh
./setup.sh
```

This script will:
1. ✓ Copy `.env.example` to `.env`
2. ✓ Build Docker containers
3. ✓ Start all services
4. ✓ Install Composer dependencies
5. ✓ Generate application key
6. ✓ Run database migrations

**Expected output:**
```
🚀 Molunzaka Streaming Platform - Setup Script
================================================

[1/6] Setting up environment configuration...
✓ .env file created

[2/6] Building Docker containers...
[+] Building 45.2s (38/38) FINISHED

[3/6] Starting containers...
✔ Network molunzaka Created
✔ Container molunzaka_redis Created
✔ Container molunzaka_db Created
✔ Container molunzaka_app Created
✔ Container molunzaka_worker Created

[4/6] Installing Composer dependencies...
Installing dependencies from lock file...

[5/6] Generating application key...
Application key set successfully.

[6/6] Setting up database...
Migrating: 2024_11_15_000000_create_users_table

✓ Setup completed successfully!
```

---

## Manual Setup (Step-by-Step)

### Step 1: Clone Repository

```bash
git clone https://github.com/Malga30/Molunzaka-tv.git
cd Molunzaka-tv
```

### Step 2: Configure Environment

```bash
cp .env.example .env
```

Edit `.env` if needed. The default values are configured for local development with SQLite:

```env
APP_NAME=Molunzaka
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite
REDIS_HOST=redis
REDIS_PORT=6379
```

### Step 3: Build & Start Docker Containers

```bash
docker compose build
docker compose up -d
```

**Verify containers are running:**
```bash
docker compose ps
```

Expected output:
```
NAME                COMMAND                  SERVICE    STATUS      PORTS
molunzaka_app       "docker-php-entryp..."   app        running     0.0.0.0:8000->8000/tcp
molunzaka_worker    "docker-php-entryp..."   worker     running
molunzaka_db        "docker-entrypoint..."   db         running     0.0.0.0:5432->5432/tcp
molunzaka_redis     "redis-server --a..."    redis      running     0.0.0.0:6379->6379/tcp
```

### Step 4: Install PHP Dependencies

```bash
docker compose exec app composer install
```

### Step 5: Generate Application Key

```bash
docker compose exec app php artisan key:generate
```

The `.env` file should now have `APP_KEY` populated.

### Step 6: Run Database Migrations

```bash
docker compose exec app php artisan migrate
```

### Step 7: Access the Application

| Service | URL |
|---------|-----|
| **API/Web** | http://localhost:8000 |
| **Admin Panel** | http://localhost:8000/admin |

---

## Using Makefile Commands

A `Makefile` is included for convenience. Common commands:

```bash
# View all available commands
make help

# Build containers
make build

# Start containers
make up

# Stop containers
make down

# View logs
make logs

# Install dependencies
make install

# Run tests
make test

# Run linter
make lint

# Auto-fix code style
make lint-fix

# Run migrations
make migrate

# Seed database
make seed

# Fresh migration (⚠️ drops all tables)
make fresh

# Start Tinker REPL
make tinker
```

---

## Docker Service Details

### App Container (php8.3-fpm)
- **Image**: PHP 8.3-FPM with Laravel extensions
- **Port**: 8000 (http://localhost:8000)
- **Volumes**: Project files, storage, cache
- **Command**: Runs `php artisan serve`

### Worker Container (php8.3-cli)
- **Image**: PHP 8.3-CLI with FFmpeg
- **Includes**: FFmpeg for video processing, Redis extension
- **Command**: Runs `php artisan horizon` (queue processor)

### Database Container (PostgreSQL)
- **Image**: PostgreSQL 16 Alpine
- **Port**: 5432
- **Credentials** (from .env):
  - Username: `molunzaka`
  - Password: `secret`
  - Database: `molunzaka`
- **Volume**: `dbdata` (persists data)

### Redis Container
- **Image**: Redis 7 Alpine
- **Port**: 6379
- **Features**: Appendonly persistence enabled

---

## Development Workflow

### 1. Create a New Database Migration

```bash
docker compose exec app php artisan make:migration create_videos_table
```

Edit the migration file in `database/migrations/`, then run:

```bash
docker compose exec app php artisan migrate
```

### 2. Create a New Model with Migration

```bash
docker compose exec app php artisan make:model Video -m
```

### 3. Create a Controller

```bash
docker compose exec app php artisan make:controller Api/VideoController --resource
```

### 4. Create a Service Class

```bash
docker compose exec app php artisan make:class Services/VideoService
```

### 5. Create a Queue Job

```bash
docker compose exec app php artisan make:job ProcessVideoEncoding
```

### 6. Create a Filament Admin Resource

```bash
docker compose exec app php artisan make:filament-resource Video
```

### 7. Run Tests

```bash
# Run all tests
docker compose exec app php artisan test

# Run a specific test file
docker compose exec app php artisan test tests/Feature/VideoUploadTest.php

# Run with coverage report
docker compose exec app php artisan test --coverage
```

### 8. Check Code Style

```bash
# Check code style (Pint)
docker compose exec app composer lint

# Auto-fix code style
docker compose exec app composer lint -- --fix
```

---

## Database Access

### Using Artisan Tinker

```bash
docker compose exec app php artisan tinker
```

```php
>>> User::count()
=> 0
>>> User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')])
```

### Using PostgreSQL Client

```bash
# Connect to PostgreSQL
docker compose exec db psql -U molunzaka -d molunzaka

# List tables
molunzaka=# \dt

# Exit
molunzaka=# \q
```

### Database Schema Inspection

```bash
# Show all tables
docker compose exec app php artisan db:show

# Show specific table structure
docker compose exec app php artisan db:table users
```

---

## File Structure

```
Molunzaka-tv/
├── docker-compose.yml              # Docker Compose services
├── Dockerfile.app                  # PHP-FPM app container
├── Dockerfile.worker               # PHP-CLI worker with FFmpeg
├── composer.json                   # PHP dependencies
├── .env.example                    # Environment template
├── .env                            # Local environment (created after setup)
├── .editorconfig                   # Editor configuration (PSR-12)
├── .dockerignore                   # Docker build ignore patterns
├── .gitignore                      # Git ignore patterns
├── Makefile                        # Development commands
├── setup.sh                        # Automated setup script
├── README.md                       # Project documentation
├── app/                            # Application code
│   ├── Http/Controllers/           # API controllers
│   ├── Http/Requests/              # Form request validation
│   ├── Jobs/                       # Queue jobs
│   ├── Models/                     # Eloquent models
│   ├── Services/                   # Business logic
│   └── Filament/                   # Admin resources
├── database/
│   ├── migrations/                 # Database migrations
│   ├── factories/                  # Model factories (testing)
│   └── seeders/                    # Database seeders
├── routes/
│   ├── api.php                     # API routes
│   ├── web.php                     # Web routes
│   └── console.php                 # Artisan commands
├── storage/
│   ├── app/                        # User files (videos, etc.)
│   ├── logs/                       # Application logs
│   └── framework/                  # Laravel cache/sessions
├── resources/
│   ├── views/                      # Blade templates
│   ├── css/                        # Stylesheets
│   └── js/                         # JavaScript
├── tests/
│   ├── Feature/                    # Feature/integration tests
│   ├── Unit/                       # Unit tests
│   └── TestCase.php                # Test base class
└── bootstrap/
    └── cache/                      # Bootstrap cache

```

---

## Troubleshooting

### Issue: Containers won't start

```bash
# View logs
docker compose logs

# Rebuild containers
docker compose down --volumes
docker compose build --no-cache
docker compose up -d
```

### Issue: "Port 8000 already in use"

```bash
# Find process using port 8000
lsof -i :8000

# Kill the process
kill -9 <PID>

# Or use a different port
docker compose up -d -p 8001:8000
```

### Issue: Database migration errors

```bash
# Check database connection
docker compose exec app php artisan db:show

# Reset database (warning: drops all data)
docker compose exec app php artisan migrate:fresh

# View migration status
docker compose exec app php artisan migrate:status
```

### Issue: Redis connection failed

```bash
# Test Redis connection
docker compose exec redis redis-cli ping
# Expected: PONG

# Check Redis logs
docker compose logs redis
```

### Issue: Permission denied errors in storage

```bash
# Fix storage permissions
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Issue: Composer memory errors during install

```bash
# Increase memory limit for Composer
COMPOSER_MEMORY_LIMIT=-1 docker compose exec app composer install
```

---

## Production Database (PostgreSQL)

To switch from SQLite to PostgreSQL in `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=molunzaka_prod
DB_USERNAME=molunzaka_user
DB_PASSWORD=your_strong_password_here
```

Restart services:
```bash
docker compose restart db app worker
```

---

## S3 Storage Configuration

### AWS S3

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=molunzaka-bucket
```

### Minio (Local S3-compatible)

Add to `docker-compose.yml`:
```yaml
minio:
  image: minio/minio:latest
  ports:
    - "9000:9000"
    - "9001:9001"
  environment:
    MINIO_ROOT_USER: minioadmin
    MINIO_ROOT_PASSWORD: minioadmin
  command: server /data --console-address ":9001"
  networks:
    - molunzaka
```

Then in `.env`:
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=molunzaka
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Access Minio Console: http://localhost:9001

---

## Next Steps

1. **Create Models & Migrations**: Define your data structures
2. **Create Controllers**: Build API endpoints
3. **Create Services**: Implement business logic
4. **Create Tests**: Write unit and feature tests
5. **Create Filament Resources**: Build admin panel
6. **Deploy**: Use Docker Compose for production

For more information, see [README.md](README.md) and [Laravel Documentation](https://laravel.com/docs).

---

**Happy coding! 🚀**
