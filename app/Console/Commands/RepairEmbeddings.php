<?php

namespace App\Console\Commands;

use App\Models\CourseMaterial;
use App\Models\MaterialChunk;
use App\Services\MaterialProcessingService;
use Illuminate\Console\Command;

class RepairEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'materials:repair-embeddings {material_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair embeddings for course materials';

    /**
     * Execute the console command.
     */
    public function handle(MaterialProcessingService $processingService)
    {
        $materialId = $this->argument('material_id');

        if ($materialId) {
            $this->repairMaterial($materialId, $processingService);
        } else {
            $materials = CourseMaterial::whereJsonContains('embeddings_status->embeddings_generated', true)
                ->get();

            if ($materials->isEmpty()) {
                $this->error('No materials found with embeddings_generated status.');
                return 1;
            }

            $this->info('Found ' . $materials->count() . ' materials with embeddings_generated status.');

            foreach ($materials as $material) {
                $this->repairMaterial($material->id, $processingService);
            }
        }

        return 0;
    }

    private function repairMaterial($materialId, $processingService)
    {
        $material = CourseMaterial::find($materialId);

        if (!$material) {
            $this->error('Material not found with ID: ' . $materialId);
            return;
        }

        $this->info('Repairing embeddings for material: ' . $material->title . ' (ID: ' . $material->id . ')');

        // Check chunks without embeddings
        $chunksWithoutEmbeddings = $material->chunks()->whereNull('embedding')->count();
        $totalChunks = $material->chunks()->count();

        $this->line('Chunks without embeddings: ' . $chunksWithoutEmbeddings . ' of ' . $totalChunks);

        if ($chunksWithoutEmbeddings > 0) {
            $this->info('Regenerating embeddings...');

            // Call the processing service to generate embeddings
            $processingService->processMaterial($material);

            // Check again after processing
            $chunksWithoutEmbeddings = $material->chunks()->whereNull('embedding')->count();
            $this->info('After repair: ' . $chunksWithoutEmbeddings . ' chunks without embeddings');

            if ($chunksWithoutEmbeddings == 0) {
                $this->info('Repair successful!');
            } else {
                $this->warn('Some chunks still do not have embeddings.');
            }
        } else {
            $this->info('All chunks already have embeddings. No repair needed.');
        }
    }
}