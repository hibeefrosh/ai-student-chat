<?php

namespace App\Console\Commands;

use App\Models\CourseMaterial;
use App\Models\MaterialChunk;
use App\Models\Course;
use Illuminate\Console\Command;

class CheckCourseMaterials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'materials:check {course_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check course materials in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $courseId = $this->argument('course_id');

        if ($courseId) {
            $this->checkCourseMaterials($courseId);
        } else {
            $courses = Course::all();

            if ($courses->isEmpty()) {
                $this->error('No courses found in the database.');
                return 1;
            }

            $this->info('Found ' . $courses->count() . ' courses:');

            foreach ($courses as $course) {
                $this->line('');
                $this->line('Course ID: ' . $course->id . ' - ' . $course->title);
                $this->checkCourseMaterials($course->id);
            }
        }

        return 0;
    }

    private function checkCourseMaterials($courseId)
    {
        $materials = CourseMaterial::where('course_id', $courseId)->get();

        if ($materials->isEmpty()) {
            $this->warn('No materials found for course ID ' . $courseId);
            return;
        }

        $this->info('Found ' . $materials->count() . ' materials for course ID ' . $courseId . ':');

        $headers = ['ID', 'Title', 'Type', 'Chunks', 'Embeddings Status'];
        $rows = [];

        foreach ($materials as $material) {
            $chunkCount = MaterialChunk::where('course_material_id', $material->id)->count();

            $embeddingsStatus = 'Not processed';
            if (isset($material->embeddings_status)) {
                if (isset($material->embeddings_status['text_extracted']) && $material->embeddings_status['text_extracted']) {
                    $embeddingsStatus = 'Text extracted';
                }
                if (isset($material->embeddings_status['chunks_created']) && $material->embeddings_status['chunks_created']) {
                    $embeddingsStatus = 'Chunks created';
                }
                if (isset($material->embeddings_status['embeddings_generated']) && $material->embeddings_status['embeddings_generated']) {
                    $embeddingsStatus = 'Embeddings generated';
                }
            }

            $rows[] = [
                $material->id,
                $material->title,
                $material->type,
                $chunkCount,
                $embeddingsStatus,
            ];
        }

        $this->table($headers, $rows);

        // Check a sample chunk
        $sampleChunk = MaterialChunk::whereHas('courseMaterial', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })->first();

        if ($sampleChunk) {
            $this->info('Sample chunk content (first 200 characters):');
            $this->line(substr($sampleChunk->content, 0, 200) . '...');

            if ($sampleChunk->embedding) {
                $this->info('This chunk has embeddings.');
            } else {
                $this->warn('This chunk does not have embeddings.');
            }
        } else {
            $this->warn('No chunks found for this course.');
        }
    }
}