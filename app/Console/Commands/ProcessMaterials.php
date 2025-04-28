<?php

namespace App\Console\Commands;

use App\Models\CourseMaterial;
use App\Services\MaterialProcessingService;
use Illuminate\Console\Command;

class ProcessMaterials extends Command
{
    protected $signature = 'materials:process {--id=} {--all}';
    protected $description = 'Process course materials for AI chat';

    public function handle()
    {
        $processingService = app(MaterialProcessingService::class);

        if ($this->option('all')) {
            $materials = CourseMaterial::where('is_processed', false)->get();
            $this->info("Processing {$materials->count()} unprocessed materials...");

            foreach ($materials as $material) {
                $this->info("Processing material: {$material->title}");
                $processingService->processMaterial($material);
            }

            $this->info('All materials processed successfully!');
            return 0;
        }

        if ($id = $this->option('id')) {
            $material = CourseMaterial::findOrFail($id);
            $this->info("Processing material: {$material->title}");
            $processingService->processMaterial($material);
            $this->info('Material processed successfully!');
            return 0;
        }

        $this->error('Please specify --all to process all materials or --id=X to process a specific material.');
        return 1;
    }
}