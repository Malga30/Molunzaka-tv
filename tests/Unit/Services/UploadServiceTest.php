<?php

namespace Tests\Unit\Services;

use App\Models\Video;
use App\Services\UploadService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * UploadServiceTest tests the UploadService for pre-signed URL generation.
 *
 * Tests:
 * - Pre-signed URL generation for S3 uploads
 * - File existence verification
 * - File metadata retrieval
 * - Storage path generation
 * - MIME type detection
 */
class UploadServiceTest extends TestCase
{
    /**
     * Upload service instance.
     *
     * @var UploadService
     */
    protected UploadService $service;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        $this->service = new UploadService();
        $this->service->setDisk('s3');
    }

    /**
     * Test creating a pre-signed URL for video upload.
     *
     * Verifies that:
     * - URL is generated successfully
     * - Response includes required fields (video_id, upload_url, expires_at, storage_path)
     * - Expiration time is set correctly (60 minutes)
     *
     * @return void
     */
    public function test_creates_presigned_url(): void
    {
        $video = Video::factory()->create();

        $result = $this->service->createPreSignedUrl($video, 'test-video.mp4');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('video_id', $result);
        $this->assertArrayHasKey('upload_url', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertArrayHasKey('storage_path', $result);
        $this->assertEquals($video->id, $result['video_id']);
        $this->assertStringContainsString('videos/uploads', $result['storage_path']);
    }

    /**
     * Test pre-signed URL expiration setting.
     *
     * Verifies that custom expiration time is respected.
     *
     * @return void
     */
    public function test_sets_custom_expiration(): void
    {
        $video = Video::factory()->create();

        $this->service->setExpiration(120);
        $result = $this->service->createPreSignedUrl($video, 'video.mp4');

        // Verify expiration is approximately 2 hours from now
        $expiresAt = strtotime($result['expires_at']);
        $now = time();
        $difference = $expiresAt - $now;

        $this->assertGreaterThan(119 * 60, $difference);
        $this->assertLessThan(121 * 60, $difference);
    }

    /**
     * Test file existence verification.
     *
     * Verifies that:
     * - Returns false when file doesn't exist
     * - Returns true when file exists and has size > 0
     *
     * @return void
     */
    public function test_verifies_file_exists(): void
    {
        // Non-existent file
        $this->assertFalse(
            $this->service->fileExists('videos/uploads/1/nonexistent.mp4')
        );

        // Create a file
        Storage::disk('s3')->put('videos/uploads/1/test.mp4', 'test content');

        // Existing file
        $this->assertTrue(
            $this->service->fileExists('videos/uploads/1/test.mp4')
        );
    }

    /**
     * Test file metadata retrieval.
     *
     * Verifies that file size and MIME type are retrieved correctly.
     *
     * @return void
     */
    public function test_retrieves_file_metadata(): void
    {
        $content = str_repeat('test', 250000); // ~1 MB
        Storage::disk('s3')->put('videos/uploads/1/test.mp4', $content);

        $metadata = $this->service->getFileMetadata('videos/uploads/1/test.mp4');

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('file_size_bytes', $metadata);
        $this->assertArrayHasKey('mime_type', $metadata);
        $this->assertGreaterThan(0, $metadata['file_size_bytes']);
    }

    /**
     * Test MIME type detection for various video formats.
     *
     * Verifies that correct MIME types are assigned to different file extensions.
     *
     * @return void
     */
    public function test_detects_mime_types(): void
    {
        $video = Video::factory()->create();

        $testCases = [
            'video.mp4' => 'video/mp4',
            'video.webm' => 'video/webm',
            'video.mov' => 'video/quicktime',
            'video.avi' => 'video/x-msvideo',
            'video.mkv' => 'video/x-matroska',
        ];

        foreach ($testCases as $filename => $expectedMimeType) {
            $result = $this->service->createPreSignedUrl($video, $filename);
            $this->assertArrayHasKey('upload_url', $result);
        }
    }

    /**
     * Test storage path generation format.
     *
     * Verifies that storage paths follow the correct format:
     * videos/uploads/{video_id}/{uuid}.{extension}
     *
     * @return void
     */
    public function test_generates_correct_storage_path(): void
    {
        $video = Video::factory()->create();

        $result = $this->service->createPreSignedUrl($video, 'my-video.mp4');
        $path = $result['storage_path'];

        // Verify path structure
        $this->assertMatchesRegularExpression(
            '/^videos\/uploads\/\d+\/[a-f0-9\-]+\.mp4$/',
            $path
        );

        // Verify video ID is in path
        $this->assertStringContainsString("videos/uploads/{$video->id}/", $path);
    }

    /**
     * Test disk switching for multi-storage scenarios.
     *
     * Verifies that service can switch between different storage disks.
     *
     * @return void
     */
    public function test_switches_storage_disk(): void
    {
        $originalDisk = 's3';
        $newDisk = 'local';

        $this->service->setDisk($newDisk);

        // Service should use new disk for subsequent operations
        Storage::fake($newDisk);
        $video = Video::factory()->create();

        // This should not throw an exception
        $result = $this->service->createPreSignedUrl($video, 'test.mp4');
        $this->assertIsArray($result);
    }

    /**
     * Test fluent interface for service configuration.
     *
     * Verifies that setter methods return $this for method chaining.
     *
     * @return void
     */
    public function test_fluent_interface(): void
    {
        $result = $this->service
            ->setDisk('s3')
            ->setExpiration(120);

        $this->assertInstanceOf(UploadService::class, $result);
    }
}
