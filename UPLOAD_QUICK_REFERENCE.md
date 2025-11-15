# Video Upload API - Quick Reference

## Three-Step Upload Process

### 1. Initiate Upload
```bash
POST /api/videos
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "My Video Title",
  "description": "Optional description",
  "genres": ["action", "drama"]
}

✓ Returns 201 Created with pre-signed S3 URL
```

### 2. Upload to S3 (Client-side)
```javascript
const response = await fetch(upload.url, {
  method: 'PUT',
  headers: { 'Content-Type': 'video/mp4' },
  body: videoFile
});
```

### 3. Complete Upload
```bash
POST /api/videos/{video_id}/complete
Authorization: Bearer {token}
Content-Type: application/json

{
  "storage_path": "videos/uploads/1/abc123.mp4"
}

✓ Returns 200 OK + processing started
✓ Spawns background ProcessUploadJob
✓ Creates VideoFile and VideoRendition records
```

## Key Classes

| Class | Purpose |
|-------|---------|
| `UploadService` | Generate pre-signed URLs, verify uploads |
| `VideoController` | Handle video CRUD and upload endpoints |
| `ProcessUploadJob` | Encode videos, generate renditions |
| `StoreVideoRequest` | Validate video creation input |
| `CompleteUploadRequest` | Validate upload completion input |

## Response Status Codes

| Code | Meaning |
|------|---------|
| 201 | Video created, ready for upload |
| 200 | Upload completed, processing started |
| 400 | Bad request (malformed JSON) |
| 401 | Unauthorized (missing/invalid token) |
| 403 | Forbidden (not video owner) |
| 422 | Validation failed or file not in S3 |
| 500 | Server error (check logs) |

## Video Statuses

**VideoFile Status:**
- `pending` → `processing` → `completed` / `failed`

**Video Rendition Status:**
- `pending` → `completed` / `failed`

## File Storage Structure

```
S3 Bucket
├── videos/uploads/{video_id}/{uuid}.{ext}     # Original upload
├── videos/renditions/{video_file_id}/360p.mp4 # 360p quality
├── videos/renditions/{video_file_id}/480p.mp4 # 480p quality
├── videos/renditions/{video_file_id}/720p.mp4 # 720p quality
├── videos/renditions/{video_file_id}/1080p.mp4 # 1080p quality
└── videos/thumbnails/{video_file_id}/thumbnail.jpg
```

## Generated Renditions

| Quality | Resolution | Bitrate | Use Case |
|---------|-----------|---------|----------|
| 360p | 640×360 | 500 kbps | Mobile data-saver |
| 480p | 854×480 | 1000 kbps | Low quality SD |
| 720p | 1280×720 | 2500 kbps | HD standard |
| 1080p | 1920×1080 | 5000 kbps | Full HD quality |

## Testing

```bash
# Run upload tests
./vendor/bin/phpunit tests/Feature/VideoUploadTest.php

# Run service tests
./vendor/bin/phpunit tests/Unit/Services/UploadServiceTest.php

# Run all tests
./vendor/bin/phpunit
```

## Database Relations

```
User (1) ──── (M) Video
          ├── (M) Profile
          ├── (M) Subscription
          └── (M) WatchHistory

Video (1) ──── (M) VideoFile
          ├── (M) Subtitle
          └── (M) WatchHistory

VideoFile (1) ──── (M) VideoRendition
```

## Environment Setup

```bash
# Install dependencies
composer install

# Configure S3 storage
# In .env:
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
AWS_URL=https://your-bucket.s3.amazonaws.com

# Run migrations
php artisan migrate

# Start queue worker (processes uploads)
php artisan queue:work redis

# Start Laravel
php artisan serve
```

## Common Curl Commands

```bash
# Create video
curl -X POST http://localhost/api/videos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"My Video","genres":["tutorial"]}'

# Complete upload
curl -X POST http://localhost/api/videos/1/complete \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"storage_path":"videos/uploads/1/abc123.mp4"}'

# List videos
curl http://localhost/api/videos \
  -H "Authorization: Bearer $TOKEN"

# Get video details
curl http://localhost/api/videos/1 \
  -H "Authorization: Bearer $TOKEN"
```

## Monitoring Processing

```bash
# Watch logs while processing
tail -f storage/logs/laravel.log

# Check queue status
php artisan queue:monitor

# Check job details in database
SELECT * FROM jobs;
SELECT * FROM video_files WHERE status='processing';
```

## Performance Tips

1. **Client-side:** Chunk large uploads using resumable upload library
2. **Server-side:** Process multiple renditions in parallel (future)
3. **Storage:** Use S3 Transfer Acceleration for faster uploads
4. **Monitoring:** Track encoding metrics in logs/dashboard
5. **Cleanup:** Delete temporary files after processing completes

## Security

- Pre-signed URLs expire after 1 hour
- Each upload gets unique storage path
- User can only complete their own uploads
- S3 credentials never exposed to client
- All API endpoints require authentication
- Rate limiting: 60 req/min for authenticated, 100 for public

## Troubleshooting

**Upload URL expires before client upload completes?**
→ Increase expiration: `$uploadService->setExpiration(180)`

**ProcessUploadJob not running?**
→ Check queue worker: `php artisan queue:work`

**S3 upload fails?**
→ Verify CORS settings and bucket permissions

**Video not processing after upload completion?**
→ Check logs, verify FFmpeg installed, check queue

**File not found after upload completion?**
→ Verify storage_path matches actual S3 location
