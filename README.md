# Molunzaka Streaming Platform

A modern HLS video streaming platform built with **Laravel 11+**, **FFmpeg**, **Redis**, and **PostgreSQL**, containerized with Docker for seamless local development and production deployment.

## 🎯 Features

- **HLS Streaming**: Adaptive bitrate video streaming with FFmpeg
- **API-First**: RESTful API with Sanctum authentication
- **Real-time Processing**: Queue jobs for video encoding and processing
- **Admin Panel**: Filament admin interface for content management
- **Role-Based Access**: Permission management with Spatie Laravel Permission
- **Redis Caching**: High-performance caching and queue management
- **S3-Compatible Storage**: Support for AWS S3, Minio, and other S3-compatible services
- **Docker Ready**: Full Docker Compose setup for local and production environments

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11+ |
| Language | PHP 8.3 |
| Database | PostgreSQL (prod) / SQLite (dev) |
| Cache/Queue | Redis |
| Video Processing | FFmpeg, HLS |
| Storage | S3-compatible (AWS S3, Minio) |
| Admin | Filament |
| Authentication | Sanctum |
| Permissions | Spatie Laravel Permission |

## 📋 Prerequisites

- Docker & Docker Compose (v3.9+)
- PHP 8.3 (for local development without Docker)
- Composer 2.0+
- Git

## 🚀 Quick Start

### 1. Clone & Setup

```bash
git clone https://github.com/Malga30/Molunzaka-tv.git
cd Molunzaka-tv
```

### 2. Environment Configuration

```bash
cp .env.example .env
```

Edit `.env` if needed (defaults use SQLite locally):

```bash
APP_KEY=  # Will be generated automatically
DB_CONNECTION=sqlite
REDIS_HOST=redis
REDIS_PORT=6379
```

### 3. Build & Start Containers

```bash
docker compose build
docker compose up -d
```

**Expected output:**
```
✔ Network molunzaka Created
✔ Volume "molunzaka_dbdata" Created
✔ Container molunzaka_redis Created
✔ Container molunzaka_db Created
✔ Container molunzaka_app Created
✔ Container molunzaka_worker Created
```

### 4. Install Dependencies

```bash
docker compose exec app composer install
```

### 5. Generate Application Key

```bash
docker compose exec app php artisan key:generate
```

### 6. Database Setup

```bash
# Run migrations
docker compose exec app php artisan migrate

# Seed database (optional)
docker compose exec app php artisan db:seed
```

### 7. Access the Application

| Service | URL | Default Credentials |
|---------|-----|-------------------|
| **API** | http://localhost:8000 | - |
| **Admin Panel** | http://localhost:8000/admin | Username/Password (configure in seeder) |

## 📁 Project Structure

```
.
├── docker-compose.yml           # Docker Compose configuration
├── Dockerfile.app               # PHP-FPM app container
├── Dockerfile.worker            # PHP-CLI worker container (FFmpeg)
├── composer.json                # PHP dependencies
├── .env.example                 # Environment template
├── app/
│   ├── Services/                # Business logic (Streaming, Encoding, etc.)
│   ├── Jobs/                    # Queue jobs (VideoEncoding, etc.)
│   ├── Http/
│   │   ├── Controllers/         # API controllers
│   │   └── Requests/            # Request validation
│   ├── Models/                  # Eloquent models
│   └── Filament/                # Admin panel resources
├── database/
│   ├── migrations/              # Database migrations
│   └── seeders/                 # Database seeders
├── routes/
│   ├── api.php                  # API routes
│   └── web.php                  # Web routes
├── storage/
│   ├── app/videos/              # Video uploads
│   └── logs/                    # Application logs
├── tests/
│   ├── Unit/                    # Unit tests
│   └── Feature/                 # Feature/integration tests
└── resources/
    └── views/                   # Blade templates
```

## 🔧 Common Commands

### Docker & Containers

```bash
# Start all containers
docker compose up -d

# Stop all containers
docker compose down

# View logs
docker compose logs -f app

# Execute command in app container
docker compose exec app <command>
```

### Laravel Artisan

```bash
# Create migration
docker compose exec app php artisan make:migration create_videos_table

# Create model with migration
docker compose exec app php artisan make:model Video -m

# Create controller
docker compose exec app php artisan make:controller VideoController

# Create service class
docker compose exec app php artisan make:class Services/VideoService

# Create job
docker compose exec app php artisan make:job ProcessVideoEncoding

# Create filament resource
docker compose exec app php artisan make:filament-resource Video
```

### Database

```bash
# Run migrations
docker compose exec app php artisan migrate

# Rollback migrations
docker compose exec app php artisan migrate:rollback

# Fresh migration (warning: drops all tables)
docker compose exec app php artisan migrate:fresh --seed

# Create seeder
docker compose exec app php artisan make:seeder UserSeeder
```

### Testing

```bash
# Run all tests
docker compose exec app php artisan test

# Run specific test file
docker compose exec app php artisan test tests/Feature/StreamingTest.php

# Run with coverage report
docker compose exec app php artisan test --coverage

# Run linting
docker compose exec app composer lint
```

### Queue & Horizon

```bash
# Process queued jobs manually (dev)
docker compose exec app php artisan queue:work redis

# Access Horizon dashboard
# Open: http://localhost:8000/horizon
```

## 🎬 Video Upload & Streaming Example

### 1. Create Video Record

```bash
curl -X POST http://localhost:8000/api/videos \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "My Video",
    "description": "A test video",
    "file": "path/to/video.mp4"
  }'
```

### 2. Video Processing Flow

1. **Upload** → Video stored in `storage/app/videos/`
2. **Queue Job** → `ProcessVideoEncoding` job dispatched
3. **FFmpeg** → Worker processes encoding to HLS
4. **Output** → HLS segments stored in `storage/app/hls/{id}/`
5. **Stream** → Access at `/api/videos/{id}/stream`

### 3. Stream Video

```html
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<video id="video" controls width="800"></video>
<script>
  if (Hls.isSupported()) {
    const hls = new Hls();
    hls.loadSource('http://localhost:8000/api/videos/1/stream');
    hls.attachMedia(document.getElementById('video'));
  }
</script>
```

## 📚 API Endpoints (Examples)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/videos` | List videos |
| `POST` | `/api/videos` | Upload video |
| `GET` | `/api/videos/{id}` | Get video details |
| `DELETE` | `/api/videos/{id}` | Delete video |
| `GET` | `/api/videos/{id}/stream` | Stream HLS |
| `GET` | `/api/videos/{id}/stats` | Video statistics |

## 🔐 Authentication

This project uses **Laravel Sanctum** for API authentication:

```bash
# Register user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password"
  }'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password"
  }'
```

## 🗄️ Database Setup (Production PostgreSQL)

To use PostgreSQL instead of SQLite:

1. Update `.env`:
```bash
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=molunzaka
DB_USERNAME=molunzaka
DB_PASSWORD=your_secure_password
```

2. Restart containers:
```bash
docker compose restart db app worker
```

## 💾 Storage Configuration (S3)

### AWS S3

```env
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=molunzaka-storage
FILESYSTEM_DISK=s3
```

### Minio (Local S3-compatible)

```env
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=molunzaka
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
FILESYSTEM_DISK=s3
```

## 🧪 Testing

Create feature and unit tests for all new functionality:

### Example Unit Test

```php
// tests/Unit/Services/VideoServiceTest.php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\VideoService;

class VideoServiceTest extends TestCase
{
    public function test_can_validate_video_format(): void
    {
        $service = new VideoService();
        $this->assertTrue($service->isValidFormat('video.mp4'));
        $this->assertFalse($service->isValidFormat('video.txt'));
    }
}
```

### Example Feature Test

```php
// tests/Feature/VideoUploadTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class VideoUploadTest extends TestCase
{
    public function test_authenticated_user_can_upload_video(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/videos', [
                'title' => 'Test Video',
                'description' => 'A test video',
            ]);

        $response->assertStatus(201);
    }
}
```

Run tests:

```bash
docker compose exec app php artisan test
```

## 🐛 Troubleshooting

### Containers won't start

```bash
# Check Docker logs
docker compose logs

# Rebuild containers
docker compose down --volumes
docker compose build --no-cache
docker compose up -d
```

### Database connection errors

```bash
# Check database health
docker compose exec db pg_isready -U molunzaka

# View database logs
docker compose logs db
```

### Redis connection issues

```bash
# Test Redis connection
docker compose exec redis redis-cli ping
# Expected output: PONG
```

### Permission errors in storage

```bash
# Fix storage permissions
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

## 📖 Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [FFmpeg Documentation](https://ffmpeg.org/documentation.html)
- [HLS Specification](https://tools.ietf.org/html/draft-pantos-http-live-streaming-23)

## 🤝 Contributing

1. Create a feature branch: `git checkout -b feature/your-feature`
2. Commit changes: `git commit -am 'Add feature'`
3. Push to branch: `git push origin feature/your-feature`
4. Submit a pull request

## 📝 Code Style

This project follows **PSR-12** coding standards:

```bash
# Run Pint linter
docker compose exec app composer lint

# Auto-fix code style
docker compose exec app composer lint -- --fix
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🤖 Support

For issues, feature requests, or questions:

- **Issues**: [GitHub Issues](https://github.com/Malga30/Molunzaka-tv/issues)
- **Email**: support@molunzaka.com

---

**Happy streaming! 🎬**
