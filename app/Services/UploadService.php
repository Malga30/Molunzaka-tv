<?php

namespace App\Services;

use App\Models\Video;
use App\Models\VideoFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * UploadService handles video file uploads and pre-signed URL generation.
 *
 * Manages S3-compatible storage interactions, including:
 * - Pre-signed URL generation for direct client uploads
 * - File existence verification after upload
 * - Storage path generation
 */
class UploadService
{
    /**
     * S3 storage disk name.
     *
     * @var string
     */
    protected string $disk = 's3';

    /**
     * Storage path prefix for video uploads.
     *
     * @var string
     */
    protected string $pathPrefix = 'videos/uploads';

    /**
     * Pre-signed URL expiration time in minutes.
     *
     * @var int
     */
    protected int $expirationMinutes = 60;

    /**
     * Create a pre-signed URL for direct S3 upload.
     *
     * Generates a temporary PUT URL that allows clients to upload directly to S3
     * without exposing AWS credentials.
     *
     * @param Video $video The video record to generate URL for
     * @param string $filename Original filename for the upload
     * @return array{url: string, expires_at: string, video_id: int}
     */
    public function createPreSignedUrl(Video $video, string $filename): array
    {
        $storagePath = $this->generateStoragePath($video, $filename);

        $url = Storage::disk($this->disk)->temporaryUrl(
            $storagePath,
            now()->addMinutes($this->expirationMinutes),
            [
                'ResponseContentType' => $this->getMimeType($filename),
            ]
        );

        return [
            'video_id' => $video->id,
            'upload_url' => $url,
            'expires_at' => now()->addMinutes($this->expirationMinutes)->toIso8601String(),
            'storage_path' => $storagePath,
        ];
    }

    /**
     * Verify that an uploaded file exists in storage.
     *
     * @param string $storagePath Path to the file in storage
     * @return bool True if file exists and has size > 0
     */
    public function fileExists(string $storagePath): bool
    {
        $disk = Storage::disk($this->disk);

        return $disk->exists($storagePath) && $disk->size($storagePath) > 0;
    }

    /**
     * Get file metadata after successful upload.
     *
     * @param string $storagePath Path to the file in storage
     * @return array{file_size_bytes: int, mime_type: string}
     */
    public function getFileMetadata(string $storagePath): array
    {
        $disk = Storage::disk($this->disk);

        return [
            'file_size_bytes' => $disk->size($storagePath),
            'mime_type' => $disk->mimeType($storagePath),
        ];
    }

    /**
     * Generate a unique storage path for video uploads.
     *
     * Format: videos/uploads/{video_id}/{uuid}.{extension}
     *
     * @param Video $video The video record
     * @param string $filename Original filename
     * @return string Storage path
     */
    protected function generateStoragePath(Video $video, string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $uuid = Str::uuid();

        return "{$this->pathPrefix}/{$video->id}/{$uuid}.{$extension}";
    }

    /**
     * Get MIME type for a filename.
     *
     * @param string $filename The filename
     * @return string MIME type
     */
    protected function getMimeType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            'flv' => 'video/x-flv',
            'wmv' => 'video/x-ms-wmv',
            default => 'video/mp4',
        };
    }

    /**
     * Set custom disk for storage operations.
     *
     * Useful for testing or using different storage backends.
     *
     * @param string $disk Storage disk name
     * @return self
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set pre-signed URL expiration time.
     *
     * @param int $minutes Expiration time in minutes
     * @return self
     */
    public function setExpiration(int $minutes): self
    {
        $this->expirationMinutes = $minutes;

        return $this;
    }
}
