<?php

namespace App\Providers;

use App\Services\AIService;
use App\Services\MaterialProcessingService;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AIService::class, function ($app) {
            return new AIService();
        });

        $this->app->singleton(MaterialProcessingService::class, function ($app) {
            $aiService = $app->make(AIService::class);
            return new MaterialProcessingService($aiService);
        });

        // Register the AI configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/ai.php',
            'ai'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__ . '/../../config/ai.php' => config_path('ai.php'),
        ], 'ai-config');
    }
}