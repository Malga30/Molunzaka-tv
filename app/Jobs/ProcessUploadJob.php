<?php

namespace App\Jobs;

use App\Models\Video;
use App\Models\VideoFile;
use App\Models\VideoRendition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * ProcessUploadJob handles video encoding and rendition generation.
 *
 * Responsibilities:
 * - Download source video from S3
 * - Extract video metadata (duration, codec, bitrate, etc.)
 * - Generate multiple quality renditions (360p, 480p, 720p, 1080p)
 * - Upload renditions back to S3
 * - Update database records with processing status
 * - Handle processing failures gracefully
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout in seconds (2 hours for large video processing).
     *
     * @var int
     */
    public int $timeout = 7200;

    /**
     * Maximum job attempts.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Backoff time in seconds for failed attempts.
     *
     * @var array
     */
    public array $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Video ID being processed.
     *
     * @var int
     */
    protected int $videoId;

    /**
     * VideoFile ID being processed.
     *
     * @var int
     */
    protected int $videoFileId;

    /**
     * Supported video rendition qualities.
     *
     * Each quality includes resolution and bitrate for encoding.
     *
     * @var array{name: string, resolution: string, bitrate: string, codec: string}[]
     */
    protected array $renditions = [
        [
            'name' => '360p',
            'resolution' => '640x360',
            'bitrate' => '500k',
            'codec' => 'libx264',
        ],
        [
            'name' => '480p',
            'resolution' => '854x480',
            'bitrate' => '1000k',
            'codec' => 'libx264',
        ],
        [
            'name' => '720p',
            'resolution' => '1280x720',
            'bitrate' => '2500k',
            'codec' => 'libx264',
        ],
        [
            'name' => '1080p',
            'resolution' => '1920x1080',
            'bitrate' => '5000k',
            'codec' => 'libx264',
        ],
    ];

    /**
     * Create a new job instance.
     *
     * @param int $videoId
     * @param int $videoFileId
     */
    public function __construct(int $videoId, int $videoFileId)
    {
        $this->videoId = $videoId;
        $this->videoFileId = $videoFileId;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        try {
            Log::info("Processing video upload", [
                'video_id' => $this->videoId,
                'video_file_id' => $this->videoFileId,
            ]);

            // Load models
            $video = Video::findOrFail($this->videoId);
            $videoFile = VideoFile::findOrFail($this->videoFileId);

            // Mark as processing
            $videoFile->update(['status' => 'processing']);

            // Download source video from S3 to temporary location
            $sourcePath = $this->downloadSourceVideo($videoFile);

            // Extract metadata from source video
            $metadata = $this->extractVideoMetadata($sourcePath);
            $videoFile->update([
                'duration' => $metadata['duration'] ?? 0,
                'codec' => $metadata['codec'] ?? 'h264',
                'bitrate' => $metadata['bitrate'] ?? '5000k',
            ]);

            // Generate renditions
            $this->generateRenditions($videoFile, $sourcePath, $metadata);

            // Mark as complete
            $videoFile->update(['status' => 'completed']);

            // Update video with metadata
            $video->update([
                'thumbnail' => $this->generateThumbnail($videoFile),
            ]);

            Log::info("Video processing completed", [
                'video_id' => $this->videoId,
                'video_file_id' => $this->videoFileId,
                'duration' => $metadata['duration'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            Log::error("Video processing failed", [
                'video_id' => $this->videoId,
                'video_file_id' => $this->videoFileId,
                'error' => $e->getMessage(),
            ]);

            // Update status to failed
            $videoFile = VideoFile::findOrFail($this->videoFileId);
            $videoFile->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }

    /**
     * Download source video from S3 to local temporary storage.
     *
     * @param VideoFile $videoFile
     * @return string Path to downloaded video
     * @throws \Exception
     */
    protected function downloadSourceVideo(VideoFile $videoFile): string
    {
        $tempDir = storage_path('temp/videos');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . '/' . basename($videoFile->storage_path);
        $content = Storage::disk('s3')->get($videoFile->storage_path);

        if (!file_put_contents($tempPath, $content)) {
            throw new \Exception("Failed to download video from S3");
        }

        Log::info("Downloaded source video", ['temp_path' => $tempPath]);

        return $tempPath;
    }

    /**
     * Extract video metadata using FFprobe.
     *
     * @param string $videoPath Path to video file
     * @return array{duration: float, codec: string, bitrate: string, width: int, height: int}
     * @throws \Exception
     */
    protected function extractVideoMetadata(string $videoPath): array
    {
        $process = new Process([
            'ffprobe',
            '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=duration,codec_name,bit_rate,width,height',
            '-of', 'json',
            $videoPath,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception("FFprobe failed: {$process->getErrorOutput()}");
        }

        $output = json_decode($process->getOutput(), true);
        $stream = $output['streams'][0] ?? [];

        return [
            'duration' => (float) ($stream['duration'] ?? 0),
            'codec' => $stream['codec_name'] ?? 'h264',
            'bitrate' => $stream['bit_rate'] ?? '5000k',
            'width' => (int) ($stream['width'] ?? 1920),
            'height' => (int) ($stream['height'] ?? 1080),
        ];
    }

    /**
     * Generate video renditions for different quality levels.
     *
     * @param VideoFile $videoFile
     * @param string $sourcePath
     * @param array $metadata
     * @return void
     * @throws \Exception
     */
    protected function generateRenditions(VideoFile $videoFile, string $sourcePath, array $metadata): void
    {
        $tempDir = storage_path('temp/renditions');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        foreach ($this->renditions as $renditionConfig) {
            Log::info("Generating rendition", [
                'quality' => $renditionConfig['name'],
                'video_file_id' => $videoFile->id,
            ]);

            // Generate output file name
            $outputFilename = "{$videoFile->id}-{$renditionConfig['name']}.mp4";
            $outputPath = "{$tempDir}/{$outputFilename}";

            // Create FFmpeg process
            $process = new Process([
                'ffmpeg',
                '-i', $sourcePath,
                '-c:v', $renditionConfig['codec'],
                '-b:v', $renditionConfig['bitrate'],
                '-s', $renditionConfig['resolution'],
                '-c:a', 'aac',
                '-b:a', '128k',
                '-preset', 'medium',
                '-movflags', 'faststart',
                '-f', 'mp4',
                $outputPath,
            ]);

            $process->setTimeout(3600);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error("FFmpeg encoding failed", [
                    'error' => $process->getErrorOutput(),
                    'quality' => $renditionConfig['name'],
                ]);
                throw new \Exception("FFmpeg failed for {$renditionConfig['name']}: {$process->getErrorOutput()}");
            }

            // Upload rendition to S3
            $s3Path = "videos/renditions/{$videoFile->id}/{$outputFilename}";
            Storage::disk('s3')->put(
                $s3Path,
                file_get_contents($outputPath)
            );

            // Create VideoRendition record
            $rendition = $videoFile->videoRenditions()->create([
                'name' => $renditionConfig['name'],
                'resolution' => $renditionConfig['resolution'],
                'bitrate' => $renditionConfig['bitrate'],
                'codec' => $renditionConfig['codec'],
                'format' => 'mp4',
                'storage_path' => $s3Path,
                'status' => 'completed',
            ]);

            Log::info("Created video rendition", [
                'rendition_id' => $rendition->id,
                'quality' => $renditionConfig['name'],
            ]);

            // Clean up temporary file
            unlink($outputPath);
        }
    }

    /**
     * Generate a thumbnail image from the video.
     *
     * Extracts a frame at 5 seconds and saves as JPEG.
     *
     * @param VideoFile $videoFile
     * @return string S3 URL to thumbnail
     * @throws \Exception
     */
    protected function generateThumbnail(VideoFile $videoFile): string
    {
        $tempDir = storage_path('temp/thumbnails');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $sourcePath = storage_path("temp/videos/" . basename($videoFile->storage_path));
        $thumbnailPath = "{$tempDir}/{$videoFile->id}-thumbnail.jpg";

        // Extract frame at 5 seconds
        $process = new Process([
            'ffmpeg',
            '-i', $sourcePath,
            '-ss', '5',
            '-vframes', '1',
            '-q:v', '2',
            $thumbnailPath,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            Log::warning("Failed to generate thumbnail", [
                'video_file_id' => $videoFile->id,
                'error' => $process->getErrorOutput(),
            ]);

            return ''; // Return empty if thumbnail generation fails
        }

        // Upload thumbnail to S3
        $s3Path = "videos/thumbnails/{$videoFile->id}/thumbnail.jpg";
        Storage::disk('s3')->put(
            $s3Path,
            file_get_contents($thumbnailPath),
            'public'
        );

        unlink($thumbnailPath);

        return Storage::disk('s3')->url($s3Path);
    }

    /**
     * Handle a failed job.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessUploadJob failed permanently", [
            'video_id' => $this->videoId,
            'video_file_id' => $this->videoFileId,
            'exception' => $exception->getMessage(),
        ]);

        try {
            $videoFile = VideoFile::find($this->videoFileId);
            if ($videoFile) {
                $videoFile->update(['status' => 'failed']);
            }
        } catch (\Exception $e) {
            Log::error("Failed to update video file status", ['error' => $e->getMessage()]);
        }
    }
}
