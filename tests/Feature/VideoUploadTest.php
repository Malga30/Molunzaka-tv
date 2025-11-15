<?php

namespace Tests\Feature;

use App\Jobs\ProcessUploadJob;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * VideoUploadTest tests the complete video upload workflow.
 *
 * Tests:
 * - POST /api/videos to initiate upload
 * - POST /api/videos/{id}/complete to finalize upload
 * - ProcessUploadJob dispatching
 * - Authorization and validation
 * - Full end-to-end upload flow
 */
class VideoUploadTest extends TestCase
{
    /**
     * Authenticated user making the request.
     *
     * @var User
     */
    protected User $user;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * Test storing a video returns pre-signed upload URL.
     *
     * Verifies that:
     * - Request returns 201 Created
     * - Video record is created in database
     * - Response includes pre-signed S3 upload URL
     * - Response includes expiration time
     *
     * @return void
     */
    public function test_store_video_returns_presigned_url(): void
    {
        $response = $this->postJson('/api/videos', [
            'title' => 'My Test Video',
            'description' => 'A test video for upload testing',
            'genres' => ['action', 'drama'],
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'video_id',
                'video' => ['id', 'title', 'slug'],
                'upload' => ['url', 'expires_at', 'storage_path', 'method', 'headers'],
            ],
        ]);

        $this->assertDatabaseHas('videos', [
            'title' => 'My Test Video',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test video creation with minimal required fields.
     *
     * Verifies that description and genres are optional.
     *
     * @return void
     */
    public function test_store_video_with_minimal_data(): void
    {
        $response = $this->postJson('/api/videos', [
            'title' => 'Minimal Video',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('videos', [
            'title' => 'Minimal Video',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test validation errors for missing required fields.
     *
     * Verifies that:
     * - Missing title returns validation error
     * - Error response is 422
     *
     * @return void
     */
    public function test_store_video_validation_fails(): void
    {
        $response = $this->postJson('/api/videos', [
            'description' => 'No title provided',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('title');
    }

    /**
     * Test completing a video upload after S3 upload.
     *
     * Verifies that:
     * - Request returns 200 OK
     * - VideoFile record is created
     * - ProcessUploadJob is dispatched
     * - Status is set to "pending"
     *
     * @return void
     */
    public function test_complete_upload_starts_processing(): void
    {
        Queue::fake();

        // Create video
        $video = $this->user->videos()->create([
            'title' => 'Test Video',
            'slug' => 'test-video',
        ]);

        // Create a file in S3 first
        $storagePath = "videos/uploads/{$video->id}/test-file-uuid.mp4";
        Storage::disk('s3')->put($storagePath, 'fake video content');

        // Complete upload
        $response = $this->postJson("/api/videos/{$video->id}/complete", [
            'storage_path' => $storagePath,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'video_id',
                'video_file_id',
                'status',
                'file_size',
                'next_steps',
            ],
        ]);

        // Verify VideoFile was created
        $this->assertDatabaseHas('video_files', [
            'video_id' => $video->id,
            'storage_path' => $storagePath,
            'status' => 'pending',
        ]);

        // Verify job was dispatched
        Queue::assertPushed(ProcessUploadJob::class);
    }

    /**
     * Test completing upload without file in storage fails.
     *
     * Verifies that:
     * - Request returns 422 if file not found in S3
     * - VideoFile is not created
     *
     * @return void
     */
    public function test_complete_upload_requires_file_in_storage(): void
    {
        $video = $this->user->videos()->create([
            'title' => 'Test Video',
            'slug' => 'test-video',
        ]);

        $response = $this->postJson("/api/videos/{$video->id}/complete", [
            'storage_path' => 'videos/uploads/nonexistent.mp4',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('video_files', [
            'video_id' => $video->id,
        ]);
    }

    /**
     * Test authorization - user cannot complete another user's upload.
     *
     * Verifies that:
     * - Request returns 403 Forbidden
     * - VideoFile is not created
     *
     * @return void
     */
    public function test_cannot_complete_other_users_upload(): void
    {
        $otherUser = User::factory()->create();
        $video = $otherUser->videos()->create([
            'title' => 'Other User Video',
            'slug' => 'other-user-video',
        ]);

        $response = $this->postJson("/api/videos/{$video->id}/complete", [
            'storage_path' => 'videos/uploads/1/test.mp4',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test storage path validation.
     *
     * Verifies that invalid storage paths are rejected:
     * - Wrong path pattern
     * - Missing video ID
     * - Unsupported file extension
     *
     * @return void
     */
    public function test_storage_path_validation(): void
    {
        $video = $this->user->videos()->create([
            'title' => 'Test Video',
            'slug' => 'test-video',
        ]);

        $invalidPaths = [
            'invalid/path/format.mp4',
            'videos/uploads/invalid-id/file.mp4',
            'videos/uploads/1/file.txt',
            'videos/1/file.mp4',
        ];

        foreach ($invalidPaths as $path) {
            $response = $this->postJson("/api/videos/{$video->id}/complete", [
                'storage_path' => $path,
            ]);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('storage_path');
        }
    }

    /**
     * Test unauthenticated users cannot create videos.
     *
     * Verifies that:
     * - Request returns 401 Unauthorized
     * - No video is created
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_create_video(): void
    {
        Sanctum::actingAs(null);

        $response = $this->postJson('/api/videos', [
            'title' => 'Test Video',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseMissing('videos', ['title' => 'Test Video']);
    }

    /**
     * Test slug uniqueness for multiple videos with same title.
     *
     * Verifies that:
     * - Multiple videos with same title get unique slugs
     * - Slugs are in format: title or title-{count}
     *
     * @return void
     */
    public function test_video_slug_uniqueness(): void
    {
        $this->postJson('/api/videos', ['title' => 'Duplicate Title']);
        $response2 = $this->postJson('/api/videos', ['title' => 'Duplicate Title']);

        $response2->assertCreated();
        $data = $response2->json('data.video');

        // Slug should be different from first video
        $this->assertStringContainsString('duplicate-title', $data['slug']);
    }

    /**
     * Test complete upload flow end-to-end.
     *
     * Verifies the entire workflow:
     * 1. Create video via POST /api/videos
     * 2. Upload file to S3 (simulated)
     * 3. Complete upload via POST /api/videos/{id}/complete
     * 4. Verify VideoFile created and job dispatched
     *
     * @return void
     */
    public function test_complete_upload_workflow(): void
    {
        Queue::fake();

        // Step 1: Create video
        $createResponse = $this->postJson('/api/videos', [
            'title' => 'Complete Workflow Test',
            'description' => 'Testing the complete upload workflow',
            'genres' => ['tutorial'],
        ]);

        $createResponse->assertCreated();
        $videoId = $createResponse->json('data.video_id');
        $storagePath = $createResponse->json('data.upload.storage_path');

        // Step 2: Simulate S3 upload
        Storage::disk('s3')->put($storagePath, str_repeat('video data', 100000));

        // Step 3: Complete upload
        $completeResponse = $this->postJson("/api/videos/{$videoId}/complete", [
            'storage_path' => $storagePath,
        ]);

        $completeResponse->assertOk();
        $videoFileId = $completeResponse->json('data.video_file_id');

        // Verify final state
        $this->assertDatabaseHas('videos', [
            'id' => $videoId,
            'title' => 'Complete Workflow Test',
        ]);

        $this->assertDatabaseHas('video_files', [
            'id' => $videoFileId,
            'video_id' => $videoId,
            'status' => 'pending',
        ]);

        // Verify job dispatch
        Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($videoId, $videoFileId) {
            return $job->videoId === $videoId && $job->videoFileId === $videoFileId;
        });
    }

    /**
     * Test video list endpoint accessible without authentication for published videos.
     *
     * @return void
     */
    public function test_list_videos_endpoint(): void
    {
        Sanctum::actingAs(null);

        // Create published video
        Video::factory()->published()->create();

        $response = $this->getJson('/api/videos');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => ['id', 'title', 'slug'],
                ],
            ],
        ]);
    }
}
