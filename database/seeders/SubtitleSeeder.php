<?php

namespace Database\Seeders;

use App\Models\Video;
use App\Models\Subtitle;
use Illuminate\Database\Seeder;

class SubtitleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $videos = Video::all();

        $languages = [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'es', 'name' => 'Spanish'],
            ['code' => 'fr', 'name' => 'French'],
            ['code' => 'de', 'name' => 'German'],
            ['code' => 'it', 'name' => 'Italian'],
        ];

        foreach ($videos as $video) {
            // Add 1-3 random language subtitles per video
            $selectedLanguages = array_slice(
                $languages,
                0,
                rand(1, 3)
            );

            foreach ($selectedLanguages as $language) {
                Subtitle::factory()->create([
                    'video_id' => $video->id,
                    'language_code' => $language['code'],
                    'language_name' => $language['name'],
                ]);
            }
        }
    }
}
