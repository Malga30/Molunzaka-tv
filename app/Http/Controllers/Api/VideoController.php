<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVideoRequest;
use App\Http\Requests\CompleteUploadRequest;
use App\Jobs\ProcessUploadJob;
use App\Models\Video;
use App\Models\VideoFile;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * VideoController handles video CRUD operations and upload workflow.
 *
 * Endpoints:
 * - POST /api/videos - Initiate upload (returns pre-signed URL)
 * - POST /api/videos/{video}/complete - Complete upload, start processing
 * - GET /api/videos - List videos
 * - GET /api/videos/{video} - Get video details
 * - PUT /api/videos/{video} - Update video metadata
 * - DELETE /api/videos/{video} - Delete video
 */
class VideoController extends Controller
{
    /**
     * Create a new VideoController instance.
     *
     * @param UploadService $uploadService
     */
    public function __construct(protected UploadService $uploadService)
    {
    }

    /**
     * Create a new video and generate pre-signed upload URL.
     *
     * Initiates the video upload workflow by:
     * 1. Creating a Video record
     * 2. Generating a pre-signed S3 PUT URL
     * 3. Returning upload details to client
     *
     * @param StoreVideoRequest $request
     * @return JsonResponse
     */
    public function store(StoreVideoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $video = DB::transaction(function () use ($validated, $request) {
                // Create video record with metadata
                $video = $request->user()->videos()->create([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'slug' => $this->generateSlug($validated['title']),
                    'genres' => $validated['genres'] ?? [],
                    'is_published' => false,
                    'is_featured' => false,
                ]);

                return $video;
            });

            // Generate pre-signed URL for upload
            $uploadData = $this->uploadService->createPreSignedUrl(
                $video,
                $validated['filename'] ?? 'video.mp4'
            );

            return response()->json([
                'success' => true,
                'message' => 'Video created successfully. Ready for upload.',
                'data' => [
                    'video_id' => $video->id,
                    'video' => [
                        'id' => $video->id,
                        'title' => $video->title,
                        'slug' => $video->slug,
                    ],
                    'upload' => [
                        'url' => $uploadData['upload_url'],
                        'expires_at' => $uploadData['expires_at'],
                        'storage_path' => $uploadData['storage_path'],
                        'method' => 'PUT',
                        'headers' => [
                            'Content-Type' => 'video/mp4',
                        ],
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create video upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete video upload and start processing.
     *
     * Verifies the file was uploaded to S3, creates a VideoFile record,
     * and dispatches a job to process the uploaded video.
     *
     * @param CompleteUploadRequest $request
     * @param Video $video
     * @return JsonResponse
     */
    public function complete(CompleteUploadRequest $request, Video $video): JsonResponse
    {
        $validated = $request->validated();

        // Check authorization
        if ($video->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to complete this upload',
            ], 403);
        }

        try {
            return DB::transaction(function () use ($validated, $video, $request) {
                // Verify file exists in storage
                if (!$this->uploadService->fileExists($validated['storage_path'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Uploaded file not found in storage',
                    ], 422);
                }

                // Get file metadata
                $metadata = $this->uploadService->getFileMetadata($validated['storage_path']);

                // Create VideoFile record
                $videoFile = $video->videoFiles()->create([
                    'storage_path' => $validated['storage_path'],
                    'file_size_bytes' => $metadata['file_size_bytes'],
                    'mime_type' => $metadata['mime_type'],
                    'status' => 'pending',
                ]);

                // Dispatch processing job
                ProcessUploadJob::dispatch($video->id, $videoFile->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Upload completed. Processing started.',
                    'data' => [
                        'video_id' => $video->id,
                        'video_file_id' => $videoFile->id,
                        'status' => $videoFile->status,
                        'file_size' => $this->formatBytes($metadata['file_size_bytes']),
                        'next_steps' => [
                            'The video is now being processed and encoded',
                            'You can check the status by polling the video details endpoint',
                            'Encoded renditions will be available when processing completes',
                        ],
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List videos accessible to the authenticated user.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $videos = Video::with(['user', 'videoFiles', 'subtitles'])
            ->where('is_published', true)
            ->orWhere('user_id', auth()->id())
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $videos,
        ]);
    }

    /**
     * Get a specific video with all related data.
     *
     * @param Video $video
     * @return JsonResponse
     */
    public function show(Video $video): JsonResponse
    {
        // Authorization: published videos or owner
        if (!$video->is_published && $video->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }

        $video->load(['user', 'videoFiles.videoRenditions', 'subtitles', 'watchHistories']);

        return response()->json([
            'success' => true,
            'data' => $video,
        ]);
    }

    /**
     * Update video metadata.
     *
     * @param Video $video
     * @return JsonResponse
     */
    public function update(Video $video): JsonResponse
    {
        // Authorization
        if ($video->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = request()->validate([
            'title' => 'string|max:255',
            'description' => 'string|nullable',
            'genres' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $video->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Video updated successfully',
            'data' => $video,
        ]);
    }

    /**
     * Delete a video and associated files.
     *
     * @param Video $video
     * @return JsonResponse
     */
    public function destroy(Video $video): JsonResponse
    {
        // Authorization
        if ($video->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully',
        ]);
    }

    /**
     * Generate a unique slug from a title.
     *
     * @param string $title
     * @return string
     */
    protected function generateSlug(string $title): string
    {
        $slug = \Str::slug($title);
        $count = Video::where('slug', 'like', "{$slug}%")->count();

        return $count > 0 ? "{$slug}-{$count}" : $slug;
    }

    /**
     * Format bytes to human-readable format.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
