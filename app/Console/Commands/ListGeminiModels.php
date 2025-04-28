<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ListGeminiModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gemini:list-models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available Gemini models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = config('ai.gemini_api_key');

        // Use the v1 API endpoint
        $endpoint = "https://generativelanguage.googleapis.com/v1/models?key={$apiKey}";

        $this->info('Fetching available Gemini models...');

        try {
            $response = Http::withoutVerifying()->get($endpoint);
            $data = $response->json();

            if (isset($data['error'])) {
                $this->error("Error: " . ($data['error']['message'] ?? 'Unknown error'));
                return 1;
            }

            if (isset($data['models']) && is_array($data['models'])) {
                $this->info("Found " . count($data['models']) . " models:");

                $headers = ['Name', 'Display Name', 'Supported Generation Methods'];
                $rows = [];

                foreach ($data['models'] as $model) {
                    // Extract just the model name from the full path
                    $modelName = $model['name'] ?? 'N/A';
                    if (strpos($modelName, '/') !== false) {
                        $parts = explode('/', $modelName);
                        $modelName = end($parts);
                    }

                    $supportedMethods = isset($model['supportedGenerationMethods'])
                        ? implode(', ', $model['supportedGenerationMethods'])
                        : 'N/A';

                    $rows[] = [
                        $modelName,
                        $model['displayName'] ?? 'N/A',
                        $supportedMethods,
                    ];
                }

                $this->table($headers, $rows);

                $this->info("\nTo use a model, set GEMINI_MODEL in your .env file to one of the model names above.");
                $this->info("For example: GEMINI_MODEL=gemini-1.5-flash");
            } else {
                $this->warn("No models found in response");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}