<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ValidateGeminiApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gemini:validate-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate the Gemini API key';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = config('ai.gemini_api_key');

        if (empty($apiKey) || $apiKey === 'your_gemini_api_key_here') {
            $this->error('API key is not set or is using the default placeholder value.');
            $this->info('Please update your .env file with a valid GEMINI_API_KEY.');
            return 1;
        }

        $this->info('Validating Gemini API key...');
        $maskedKey = substr($apiKey, 0, 5) . '...' . substr($apiKey, -5);
        $this->line("Using API key: {$maskedKey}");

        // Try to list models as a simple validation
        $endpoint = "https://generativelanguage.googleapis.com/v1/models?key={$apiKey}";

        try {
            $response = Http::withoutVerifying()->get($endpoint);
            $data = $response->json();

            if (isset($data['error'])) {
                $this->error('API key validation failed:');
                $this->error($data['error']['message'] ?? 'Unknown error');

                if (isset($data['error']['status']) && $data['error']['status'] === 'PERMISSION_DENIED') {
                    $this->info('This error typically means your API key is invalid or has expired.');
                    $this->info('Please check your API key and make sure it has the necessary permissions.');
                }

                return 1;
            }

            if (isset($data['models']) && is_array($data['models'])) {
                $this->info('API key is valid!');
                $this->info('Found ' . count($data['models']) . ' available models.');
                return 0;
            } else {
                $this->warn('API key validation returned an unexpected response format.');
                $this->line('Response: ' . json_encode($data));
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Exception during API key validation:');
            $this->error($e->getMessage());
            return 1;
        }
    }
}