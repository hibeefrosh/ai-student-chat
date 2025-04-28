<?php

namespace App\Console\Commands;

use App\Services\AIService;
use Illuminate\Console\Command;

class TestGeminiConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gemini:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the connection to the Gemini API';

    /**
     * Execute the console command.
     */
    public function handle(AIService $aiService)
    {
        $this->info('Testing connection to Gemini API...');

        $result = $aiService->testConnection();

        if ($result['success']) {
            $this->info('Connection successful!');
            $this->info('Response: ' . $result['response']);
        } else {
            $this->error('Connection failed!');
            $this->error('Error: ' . $result['message']);

            if (isset($result['trace'])) {
                $this->line('Trace:');
                $this->line($result['trace']);
            }
        }

        return $result['success'] ? 0 : 1;
    }
}