<?php

namespace App\Jobs;

use App\Models\CourseMaterial;
use App\Services\MaterialProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMaterialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $material;

    public function __construct(CourseMaterial $material)
    {
        $this->material = $material;
    }

    public function handle()
    {
        $processingService = app(MaterialProcessingService::class);
        $processingService->processMaterial($this->material);
    }
}