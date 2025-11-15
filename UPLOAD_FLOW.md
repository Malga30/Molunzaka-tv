# Video Upload Flow Implementation Guide

## Overview

This document describes the complete video upload workflow implementation for the Molunzaka-tv streaming platform. The implementation follows a three-step process: initiate upload, upload to S3, and complete upload with background processing.

## Architecture

### Upload Workflow

```
Client                          API Server                    S3 Storage              Queue
  |                               |                             |                       |
  |--- POST /api/videos -------> |                             |                       |
  |                          Create Video metadata            |                       |
  |                          Generate pre-signed URL          |                       |
  |<--- 201 + Upload URL --------|                             |                       |
  |                               |                             |                       |
  |--- PUT to S3 (pre-signed) ---->|                             |                       |
  |                               |        Upload Video File    |                       |
  |                               |<----------OK----------------|                       |
  |<--- File uploaded OK ---------|                             |                       |
  |                               |                             |                       |
  |--- POST /api/videos/{id}/complete ---> |                  |                       |
  |                          Verify file in S3                 |                       |
  |                          Create VideoFile record           |                       |
  |                          Dispatch ProcessUploadJob         |                       |
  |<--- 200 + Processing Started |                             |                       |
  |                               |                             |                       |
  |                               |                             |                       |
  |                               |                             |                   Encode Video
  |                               |                             |                   Generate Renditions
  |                               |<--- Retrieve File ---------|                       |
  |                               |                             |                       |
  |                               |                    Upload Renditions -->  |
  |                               |                             |                       |
  |                               |                             |         Update Status |
  |                               |                             |                       |
```

## Implementation Components

### 1. Services

#### UploadService (`app/Services/UploadService.php`)

Manages S3 interactions and pre-signed URL generation.

**Key Methods:**

```php
public function createPreSignedUrl(Video $video, string $filename): array
```

- Generates a temporary PUT URL for direct S3 upload
- Supports S3-compatible endpoints (AWS, Minio, DigitalOcean Spaces)
- Returns array with:
  - `upload_url`: Pre-signed PUT URL (1 hour expiration by default)
  - `expires_at`: ISO 8601 expiration timestamp
  - `storage_path`: S3 path where file will be stored
  - `video_id`: Associated video ID

**Storage Path Format:**
```
videos/uploads/{video_id}/{uuid}.{extension}
```

**Example Response:**
```json
{
  "video_id": 1,
  "upload_url": "https://s3.amazonaws.com/bucket/videos/uploads/1/abc123.mp4?X-Amz-Signature=...",
  "expires_at": "2024-11-15T10:31:00+00:00",
  "storage_path": "videos/uploads/1/abc123.mp4"
}
```

**Additional Methods:**

```php
// Verify file was uploaded to S3
public function fileExists(string $storagePath): bool

// Get file size and MIME type
public function getFileMetadata(string $storagePath): array

// Configure storage disk (e.g., 's3', 'minio')
public function setDisk(string $disk): self

// Set custom expiration time (minutes)
public function setExpiration(int $minutes): self
```

### 2. Controllers

#### VideoController (`app/Http/Controllers/Api/VideoController.php`)

Handles video CRUD operations and upload workflow.

**Upload Initiation:**

```php
public function store(StoreVideoRequest $request): JsonResponse
```

- Creates Video record with metadata
- Calls UploadService to generate pre-signed URL
- Returns 201 Created with upload details

**Request Example:**
```bash
POST /api/videos HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "My Awesome Video",
  "description": "Video description",
  "genres": ["action", "drama"],
  "filename": "my-video.mp4"
}
```

**Response Example:**
```json
{
  "success": true,
  "message": "Video created successfully. Ready for upload.",
  "data": {
    "video_id": 1,
    "video": {
      "id": 1,
      "title": "My Awesome Video",
      "slug": "my-awesome-video"
    },
    "upload": {
      "url": "https://s3.amazonaws.com/...",
      "expires_at": "2024-11-15T10:31:00+00:00",
      "storage_path": "videos/uploads/1/abc123.mp4",
      "method": "PUT",
      "headers": {
        "Content-Type": "video/mp4"
      }
    }
  }
}
```

**Upload Completion:**

```php
public function complete(CompleteUploadRequest $request, Video $video): JsonResponse
```

- Verifies file exists in S3
- Creates VideoFile record with metadata
- Dispatches ProcessUploadJob for encoding
- Returns 200 OK with processing status

**Request Example:**
```bash
POST /api/videos/1/complete HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "storage_path": "videos/uploads/1/abc123.mp4"
}
```

**Response Example:**
```json
{
  "success": true,
  "message": "Upload completed. Processing started.",
  "data": {
    "video_id": 1,
    "video_file_id": 1,
    "status": "pending",
    "file_size": "245.6 MB",
    "next_steps": [
      "The video is now being processed and encoded",
      "You can check the status by polling the video details endpoint",
      "Encoded renditions will be available when processing completes"
    ]
  }
}
```

### 3. Jobs

#### ProcessUploadJob (`app/Jobs/ProcessUploadJob.php`)

Handles asynchronous video encoding and rendition generation.

**Key Responsibilities:**

1. **Download** source video from S3 to temporary storage
2. **Extract Metadata** using FFprobe (duration, codec, bitrate, dimensions)
3. **Generate Renditions** at multiple quality levels:
   - 360p: 500 kbps
   - 480p: 1000 kbps
   - 720p: 2500 kbps
   - 1080p: 5000 kbps
4. **Upload Renditions** back to S3
5. **Generate Thumbnail** from video frame at 5 seconds
6. **Update Status** in database (pending → processing → completed/failed)

**Job Configuration:**

- **Timeout:** 2 hours (7200 seconds)
- **Retries:** 3 attempts
- **Backoff:** 1 min, 5 min, 15 min
- **Queue:** Default queue (Redis in production)

**Storage Paths:**

```
Renditions:    videos/renditions/{video_file_id}/{quality}.mp4
Thumbnails:    videos/thumbnails/{video_file_id}/thumbnail.jpg
```

**Example Processing Log:**

```
[2024-11-15 10:31:00] Processing video upload - video_id: 1, video_file_id: 1
[2024-11-15 10:31:15] Downloaded source video - temp_path: /tmp/videos/abc123.mp4
[2024-11-15 10:31:20] Generating rendition - quality: 360p
[2024-11-15 10:32:30] Generating rendition - quality: 480p
[2024-11-15 10:34:15] Generating rendition - quality: 720p
[2024-11-15 10:37:45] Generating rendition - quality: 1080p
[2024-11-15 10:42:00] Video processing completed - duration: 1234.5s
```

**FFmpeg Commands Used:**

```bash
# Extract metadata
ffprobe -v error -select_streams v:0 -show_entries \
  stream=duration,codec_name,bit_rate,width,height -of json video.mp4

# Encode rendition
ffmpeg -i input.mp4 -c:v libx264 -b:v {bitrate} -s {resolution} \
  -c:a aac -b:a 128k -preset medium -movflags faststart -f mp4 output.mp4

# Extract thumbnail
ffmpeg -i video.mp4 -ss 5 -vframes 1 -q:v 2 thumbnail.jpg
```

### 4. Form Requests

#### StoreVideoRequest (`app/Http/Requests/StoreVideoRequest.php`)

Validates video creation request data.

**Rules:**

| Field | Rule | Notes |
|-------|------|-------|
| `title` | required, string, max:255 | Video title |
| `description` | nullable, string, max:5000 | Optional video description |
| `genres` | nullable, array | Optional genre tags |
| `genres.*` | string, max:50 | Each genre is a string |
| `filename` | nullable, string, max:255 | Original filename |

**Example Validation Error:**

```json
{
  "message": "The title field is required.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

#### CompleteUploadRequest (`app/Http/Requests/CompleteUploadRequest.php`)

Validates upload completion request data.

**Rules:**

| Field | Rule | Notes |
|-------|------|-------|
| `storage_path` | required, string, regex | Must match S3 path pattern |
| `file_size` | nullable, integer, min:1048576 | Minimum 1 MB |

**Path Regex:**
```regex
^videos\/uploads\/\d+\/.+\.(?:mp4|webm|mov|avi|mkv|flv|wmv)$
```

**Supported Formats:** mp4, webm, mov, avi, mkv, flv, wmv

### 5. API Routes

Routes are defined in `routes/api.php`:

```php
// Authenticated routes (require auth:sanctum, throttled 60/min)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('videos', VideoController::class);
    Route::post('videos/{video}/complete', [VideoController::class, 'complete']);
});

// Public routes (throttled 100/min)
Route::middleware(['throttle:100,1'])->group(function () {
    Route::get('videos', [VideoController::class, 'index']);
    Route::get('videos/{video}', [VideoController::class, 'show']);
});
```

**Endpoints Summary:**

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/videos` | Yes | Create video, get pre-signed URL |
| POST | `/api/videos/{id}/complete` | Yes | Complete upload, start processing |
| GET | `/api/videos` | No* | List published videos or user's videos |
| GET | `/api/videos/{id}` | No* | Get video details |
| PUT | `/api/videos/{id}` | Yes | Update video metadata |
| DELETE | `/api/videos/{id}` | Yes | Delete video |

*No auth required for published videos, but authenticated users see their own videos

## Testing

### Unit Tests

#### UploadServiceTest (`tests/Unit/Services/UploadServiceTest.php`)

Tests the UploadService in isolation using mocked storage.

**Test Cases:**

1. **test_creates_presigned_url** - Pre-signed URL generation
2. **test_sets_custom_expiration** - Custom expiration time
3. **test_verifies_file_exists** - File existence checks
4. **test_retrieves_file_metadata** - File metadata extraction
5. **test_detects_mime_types** - MIME type detection for various formats
6. **test_generates_correct_storage_path** - Storage path format validation
7. **test_switches_storage_disk** - Multi-disk support
8. **test_fluent_interface** - Method chaining

**Running Tests:**

```bash
./vendor/bin/phpunit tests/Unit/Services/UploadServiceTest.php
```

### Feature Tests

#### VideoUploadTest (`tests/Feature/VideoUploadTest.php`)

Tests the complete upload workflow end-to-end.

**Test Cases:**

1. **test_store_video_returns_presigned_url** - Video creation returns upload URL
2. **test_store_video_with_minimal_data** - Optional fields handling
3. **test_store_video_validation_fails** - Validation error handling
4. **test_complete_upload_starts_processing** - Upload completion and job dispatch
5. **test_complete_upload_requires_file_in_storage** - File existence verification
6. **test_cannot_complete_other_users_upload** - Authorization checks
7. **test_storage_path_validation** - Invalid path rejection
8. **test_unauthenticated_user_cannot_create_video** - Authentication requirement
9. **test_video_slug_uniqueness** - Unique slug generation
10. **test_complete_upload_workflow** - Full workflow end-to-end
11. **test_list_videos_endpoint** - Public video listing

**Running Tests:**

```bash
./vendor/bin/phpunit tests/Feature/VideoUploadTest.php
```

**Running All Tests:**

```bash
./vendor/bin/phpunit
```

## Client Integration

### JavaScript/TypeScript Example

```typescript
async function uploadVideo(title: string, file: File): Promise<void> {
  // Step 1: Create video and get pre-signed URL
  const createResponse = await fetch('/api/videos', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      title,
      description: 'User uploaded video',
      genres: [],
      filename: file.name,
    }),
  });

  const { data } = await createResponse.json();
  const { video_id, upload } = data;

  // Step 2: Upload file directly to S3
  await fetch(upload.url, {
    method: upload.method,
    headers: upload.headers,
    body: file,
  });

  // Step 3: Complete upload
  const completeResponse = await fetch(
    `/api/videos/${video_id}/complete`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        storage_path: upload.storage_path,
      }),
    }
  );

  const { data: completeData } = await completeResponse.json();
  console.log('Processing started:', completeData.status);
}
```

### cURL Example

```bash
# Step 1: Create video and get pre-signed URL
curl -X POST http://localhost/api/videos \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My Video",
    "description": "A test video",
    "genres": ["tutorial"],
    "filename": "video.mp4"
  }'

# Response includes upload.url and upload.storage_path

# Step 2: Upload to S3 (replace URL with response URL)
curl -X PUT \
  -H "Content-Type: video/mp4" \
  --data-binary @video.mp4 \
  "https://s3.amazonaws.com/bucket/videos/uploads/1/abc123.mp4?X-Amz-Signature=..."

# Step 3: Complete upload
curl -X POST http://localhost/api/videos/1/complete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "storage_path": "videos/uploads/1/abc123.mp4"
  }'
```

## Configuration

### Environment Variables

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.s3.amazonaws.com
AWS_ENDPOINT=https://s3.amazonaws.com  # or Minio endpoint

# Queue processing
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

### Disk Configuration (`config/filesystems.php`)

The application uses the `s3` disk defined in the filesystems configuration for all storage operations.

## Error Handling

### Common Errors and Solutions

**401 Unauthorized**
- Missing or invalid authentication token
- Solution: Include valid Bearer token in Authorization header

**403 Forbidden**
- Attempting to complete another user's upload
- Solution: Only complete uploads you initiated

**422 Unprocessable Entity**
- File not found in S3
- Invalid storage path format
- Validation failed
- Solution: Check file was uploaded and path matches expected format

**500 Internal Server Error**
- FFmpeg not installed (for ProcessUploadJob)
- S3 credentials invalid
- Storage disk not configured
- Solution: Check logs and verify configuration

## Database Schema

### Key Tables

**videos**
```
id, user_id, title, slug, description, genres, 
thumbnail, poster, rating, views_count, is_published, 
is_featured, created_at, updated_at
```

**video_files**
```
id, video_id, storage_path, file_size_bytes, mime_type, 
duration, codec, bitrate, status (pending/processing/completed/failed), 
created_at, updated_at
```

**video_renditions**
```
id, video_file_id, name (360p/480p/720p/1080p), resolution, 
bitrate, codec, format, storage_path, status, created_at, updated_at
```

## Performance Considerations

### Pre-signed URL Security
- URLs expire after 1 hour by default (configurable)
- Each URL is unique per video/upload
- No AWS credentials exposed to client

### Background Processing
- Large video encoding happens in queue worker
- Multiple quality renditions generated in parallel (future enhancement)
- Temporary files cleaned up after processing

### Storage Optimization
- Original uploaded file kept for future re-encoding
- Renditions stored in separate S3 paths
- Thumbnails generated and cached

## Future Enhancements

1. **Parallel Rendition Generation** - Encode multiple qualities concurrently
2. **HLS/DASH Support** - Adaptive bitrate streaming
3. **Webhooks** - Notify clients when processing completes
4. **Progress Tracking** - WebSocket updates during upload
5. **Resumable Uploads** - Handle interrupted uploads
6. **Compression** - Auto-optimize based on source quality
7. **Subtitle Generation** - Auto-generate captions from audio
8. **Analytics** - Track encoding metrics and performance

## Troubleshooting

### Videos Not Processing
1. Check queue is running: `php artisan queue:work`
2. Verify Redis connection: `redis-cli ping`
3. Check logs: `tail storage/logs/laravel.log`
4. Verify S3 credentials and permissions

### Pre-signed URLs Failing
1. Verify AWS credentials configured correctly
2. Check S3 bucket CORS settings
3. Ensure bucket exists and is accessible
4. Verify storage disk configuration

### FFmpeg Errors
1. Verify FFmpeg installed: `which ffmpeg`
2. Verify FFprobe installed: `which ffprobe`
3. Check file permissions on temporary storage
4. Verify video format is supported

## Files Created

- `app/Services/UploadService.php` - Pre-signed URL generation
- `app/Http/Controllers/Api/VideoController.php` - Video endpoints
- `app/Jobs/ProcessUploadJob.php` - Video encoding job
- `app/Http/Requests/StoreVideoRequest.php` - Video creation validation
- `app/Http/Requests/CompleteUploadRequest.php` - Upload completion validation
- `routes/api.php` - API route definitions
- `tests/Unit/Services/UploadServiceTest.php` - Service unit tests
- `tests/Feature/VideoUploadTest.php` - Integration tests
