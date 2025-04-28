<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\MaterialChunk;
use App\Models\CourseMaterial;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AIService
{
    public function generateResponse(ChatSession $chatSession, string $userMessage)
    {
        // Always get some general course content first
        $generalChunks = $this->getGeneralCourseContent($chatSession->course_id);

        // Then try to get relevant chunks for the specific question
        $relevantChunks = $this->findRelevantMaterialChunks($chatSession->course_id, $userMessage);

        // Combine both sets of chunks, prioritizing relevant ones
        $combinedChunks = $relevantChunks->merge($generalChunks)->unique('id');

        // Log what we're working with
        Log::info('Chunks for response', [
            'general_count' => $generalChunks->count(),
            'relevant_count' => $relevantChunks->count(),
            'combined_count' => $combinedChunks->count(),
            'course_id' => $chatSession->course_id
        ]);

        // If we still have no chunks (very unlikely), log an error
        if ($combinedChunks->count() == 0) {
            Log::error('No chunks found for course', ['course_id' => $chatSession->course_id]);

            // Create a dummy chunk with an error message
            $dummyChunk = new MaterialChunk();
            $dummyChunk->content = "This is a course in the system. Please ask questions about the course content.";
            $dummyChunk->courseMaterial = new CourseMaterial();
            $dummyChunk->courseMaterial->title = "Course Introduction";

            $combinedChunks = collect([$dummyChunk]);
        }

        $context = $this->prepareContext($combinedChunks);

        // Get chat history
        $history = $this->getChatHistory($chatSession);

        // Call Gemini API
        $response = $this->callAI($history, $userMessage, $context);

        return [
            'content' => $response['content'],
            'sources' => $this->extractSourcesFromChunks($combinedChunks),
            'metadata' => [
                'model' => config('ai.gemini_model'),
                'tokens' => $response['usage'] ?? null,
            ],
        ];
    }

    /**
     * Find relevant material chunks for a query
     * 
     * @param int $courseId The course ID
     * @param string $query The search query
     * @return \Illuminate\Database\Eloquent\Collection The relevant chunks
     */
    private function findRelevantMaterialChunks($courseId, $query)
    {
        Log::info('Finding relevant chunks', [
            'course_id' => $courseId,
            'query' => $query
        ]);

        // Extract keywords from the query
        $keywords = $this->extractKeywords($query);

        // Log the keywords we're searching for
        Log::info('Searching with keywords', ['keywords' => $keywords]);

        // First try: Use full-text search if available
        try {
            // Use a more sophisticated query to find relevant chunks
            $chunks = MaterialChunk::whereHas('courseMaterial', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })
                ->where(function ($q) use ($keywords, $query) {
                    // Try to match the exact query first
                    $q->where('content', 'LIKE', '%' . $query . '%');

                    // Then try to match individual keywords
                    foreach ($keywords as $keyword) {
                        if (strlen($keyword) > 3) { // Only use keywords with more than 3 characters
                            $q->orWhere('content', 'LIKE', '%' . $keyword . '%');
                        }
                    }
                })
                ->orderByRaw('LENGTH(content) DESC') // Prefer longer chunks as they may contain more context
                ->limit(10)
                ->get();

            // If we found enough chunks, return them
            if ($chunks->count() >= 3) {
                Log::info('Found chunks using keyword search', ['count' => $chunks->count()]);
                return $chunks;
            }
        } catch (\Exception $e) {
            Log::error('Error in full-text search', ['error' => $e->getMessage()]);
        }

        // Second try: Get chunks from materials with titles matching keywords
        try {
            $chunks = MaterialChunk::whereHas('courseMaterial', function ($q) use ($courseId, $keywords) {
                $q->where('course_id', $courseId);
                $q->where(function ($subq) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        if (strlen($keyword) > 3) {
                            $subq->orWhere('title', 'LIKE', '%' . $keyword . '%');
                        }
                    }
                });
            })
                ->orderBy('chunk_index')
                ->limit(10)
                ->get();

            if ($chunks->count() > 0) {
                Log::info('Found chunks by material title', ['count' => $chunks->count()]);
                return $chunks;
            }
        } catch (\Exception $e) {
            Log::error('Error in material title search', ['error' => $e->getMessage()]);
        }

        // Fallback: Get a mix of chunks to provide general course context
        Log::info('No specific relevant chunks found, using general course content');

        // Get some introductory content (first chunks from materials)
        $introChunks = MaterialChunk::whereHas('courseMaterial', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })
            ->where('chunk_index', 0)  // First chunks of materials often contain introductory content
            ->limit(5)
            ->get();

        // Get some recent content
        $recentChunks = MaterialChunk::whereHas('courseMaterial', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        // Combine and return unique chunks
        $combinedChunks = $introChunks->merge($recentChunks)->unique('id');

        if ($combinedChunks->count() > 0) {
            Log::info('Using general course content', ['count' => $combinedChunks->count()]);
            return $combinedChunks;
        }

        // Last resort: Get ANY chunks from this course
        return MaterialChunk::whereHas('courseMaterial', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })
            ->inRandomOrder()
            ->limit(10)
            ->get();
    }

    /**
     * Extract keywords from a query
     * 
     * @param string $query The query
     * @return array The keywords
     */
    private function extractKeywords($query)
    {
        // Convert to lowercase
        $query = strtolower($query);

        // Remove punctuation
        $query = preg_replace('/[^\w\s]/', '', $query);

        // Split into words
        $words = preg_split('/\s+/', $query);

        // Remove stop words
        $stopWords = ['the', 'and', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'of', 'is', 'are', 'was', 'were', 'what', 'when', 'where', 'how', 'why', 'who', 'which', 'this', 'that', 'these', 'those'];
        $words = array_diff($words, $stopWords);

        // Return the keywords
        return array_values($words);
    }

    /**
     * Prepare context from chunks
     * 
     * @param \Illuminate\Database\Eloquent\Collection $chunks The chunks
     * @return array The context
     */
    private function prepareContext($chunks)
    {
        $context = [];

        foreach ($chunks as $chunk) {
            $context[] = [
                'source' => $chunk->courseMaterial->title,
                'content' => $chunk->content,
            ];
        }

        // Log the context we're using
        Log::info('Prepared context', [
            'context_count' => count($context),
            'first_source' => $context[0]['source'] ?? 'none',
            'first_content_length' => isset($context[0]) ? strlen($context[0]['content']) : 0
        ]);

        return $context;
    }

    private function getChatHistory(ChatSession $chatSession)
    {
        return $chatSession->messages()
            ->orderBy('created_at')
            ->limit(10)
            ->get()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            });
    }

    private function callAI($history, $userMessage, $context)
    {
        $apiKey = config('ai.gemini_api_key');
        $model = config('ai.gemini_model', 'gemini-1.5-flash');

        // Log the API key (first few characters only for security)
        $maskedKey = substr($apiKey, 0, 5) . '...' . substr($apiKey, -5);
        Log::info('Using Gemini API', [
            'model' => $model,
            'api_key_prefix' => $maskedKey,
        ]);

        // Use the updated v1 endpoint with direct model reference
        $apiEndpoint = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

        $systemPrompt = "You are a professional and knowledgeable tutor for this course. Your role is to help students understand the course material through interactive, conversational guidance. " .
            "You already have access to all the course materials in the system - NEVER ask the student to provide materials. " .
            "Base your answers on the course materials provided in the context, but feel free to elaborate with relevant examples and explanations to enhance understanding. " .
            "Maintain a warm, encouraging tone while being clear and precise. Remember previous parts of the conversation to provide continuity. " .
            "If a student asks for clarification or expansion on a topic, provide more depth while keeping explanations accessible. " .
            "If you don't know the answer based on the materials, acknowledge this honestly rather than inventing information. " .
            "When citing sources, mention the material title once in a natural way. " .
            "Engage with the student by occasionally asking thoughtful follow-up questions to check understanding or prompt deeper thinking. " .
            "For first-time interactions, welcome the student and offer to help with any questions about the course content. " .
            "CRITICAL INSTRUCTION: You ALWAYS have course materials available to you in the context. NEVER tell the student you don't have access to course materials or ask them to provide materials.";

        $contextText = "Relevant course materials:\n";
        foreach ($context as $item) {
            $contextText .= "--- From: {$item['source']} ---\n{$item['content']}\n\n";
        }

        // Create a more structured prompt that clearly separates system instructions from content
        $messages = [];

        // Add system message
        $messages[] = [
            "role" => "system",
            "parts" => [
                ["text" => $systemPrompt]
            ]
        ];

        // Add context as a system message
        $messages[] = [
            "role" => "system",
            "parts" => [
                ["text" => $contextText]
            ]
        ];

        // Add conversation history
        foreach ($history as $message) {
            $role = $message['role'] === 'user' ? 'user' : 'assistant';
            $messages[] = [
                "role" => $role,
                "parts" => [
                    ["text" => $message['content']]
                ]
            ];
        }

        // Add the current user message
        $messages[] = [
            "role" => "user",
            "parts" => [
                ["text" => $userMessage]
            ]
        ];

        $payload = [
            "contents" => $messages,
            "generationConfig" => [
                "temperature" => (float) config('ai.gemini_temperature', 0.7),
                "maxOutputTokens" => (int) config('ai.gemini_max_tokens', 2000),
                "topP" => 0.95,
                "topK" => 40
            ]
        ];

        try {
            Log::info('Sending request to Gemini API', [
                'endpoint' => $apiEndpoint,
                'message_count' => count($messages),
                'payload_structure' => array_keys($payload),
            ]);

            // Disable SSL verification for testing
            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($apiEndpoint, $payload);

            $data = $response->json();
            $status = $response->status();

            Log::info('Gemini API Response', [
                'status' => $status,
                'has_error' => isset($data['error']),
                'response_structure' => array_keys($data),
            ]);

            if (isset($data['error'])) {
                Log::error('Gemini API Error', [
                    'error_code' => $data['error']['code'] ?? 'unknown',
                    'error_message' => $data['error']['message'] ?? 'unknown',
                    'error_status' => $data['error']['status'] ?? 'unknown',
                    'error_details' => $data['error']['details'] ?? [],
                ]);

                // If there's an error with the structured format, fall back to the simpler format
                return $this->callAIWithSimpleFormat($userMessage, $context, $history, $systemPrompt);
            }

            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not generate a response.';

            // Estimate token usage (Gemini doesn't provide this directly)
            $inputTokens = strlen(json_encode($messages)) / 4; // Rough estimate
            $outputTokens = strlen($content) / 4; // Rough estimate

            return [
                'content' => $content,
                'usage' => [
                    'prompt_tokens' => (int) $inputTokens,
                    'completion_tokens' => (int) $outputTokens,
                    'total_tokens' => (int) ($inputTokens + $outputTokens),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Gemini API Exception', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'content' => 'Sorry, there was an error connecting to the AI service: ' . $e->getMessage(),
                'usage' => null,
            ];
        }
    }

    /**
     * Fallback method that uses a simpler format for the Gemini API
     */
    private function callAIWithSimpleFormat($userMessage, $context, $history, $systemPrompt)
    {
        $apiKey = config('ai.gemini_api_key');
        $model = config('ai.gemini_model', 'gemini-1.5-flash');
        $apiEndpoint = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

        // Prepare context
        $contextText = "Relevant course materials:\n";
        foreach ($context as $item) {
            $contextText .= "--- From: {$item['source']} ---\n{$item['content']}\n\n";
        }

        // Build a simple text prompt
        $prompt = $systemPrompt . "\n\n" . $contextText . "\n\n";

        // Add conversation history in a simplified format
        // Make sure to include ALL history to maintain continuity
        foreach ($history as $message) {
            $role = $message['role'] === 'user' ? 'User' : 'Assistant';
            $prompt .= $role . ": " . $message['content'] . "\n\n";
        }

        // Add the current user message
        $prompt .= "User: " . $userMessage . "\n\nAssistant:";

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => (float) config('ai.gemini_temperature', 0.7),
                "maxOutputTokens" => (int) config('ai.gemini_max_tokens', 2000),
                "topP" => 0.95,
                "topK" => 40
            ]
        ];

        try {
            Log::info('Sending fallback request to Gemini API', [
                'endpoint' => $apiEndpoint,
                'prompt_length' => strlen($prompt),
            ]);

            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($apiEndpoint, $payload);

            $data = $response->json();

            if (isset($data['error'])) {
                Log::error('Fallback Gemini API Error', [
                    'error_message' => $data['error']['message'] ?? 'Unknown error',
                ]);
                return [
                    'content' => 'Sorry, I encountered an error while trying to answer your question. Please try again later.',
                    'usage' => null,
                ];
            }

            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not generate a response.';

            return [
                'content' => $content,
                'usage' => [
                    'prompt_tokens' => strlen($prompt) / 4,
                    'completion_tokens' => strlen($content) / 4,
                    'total_tokens' => (strlen($prompt) + strlen($content)) / 4,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Fallback Gemini API Exception', [
                'exception' => $e->getMessage(),
            ]);
            return [
                'content' => 'Sorry, there was an error connecting to the AI service. Please try again later.',
                'usage' => null,
            ];
        }
    }

    /**
     * Extract sources from chunks for citation
     * 
     * @param \Illuminate\Database\Eloquent\Collection $chunks The chunks
     * @return array The sources
     */
    private function extractSourcesFromChunks($chunks)
    {
        // Use a map to track unique sources by ID
        $uniqueSources = [];

        foreach ($chunks as $chunk) {
            $materialId = $chunk->courseMaterial->id;

            // Only add each source once
            if (!isset($uniqueSources[$materialId])) {
                $uniqueSources[$materialId] = [
                    'id' => $materialId,
                    'title' => $chunk->courseMaterial->title,
                    'chunk_id' => $chunk->id,
                ];
            }
        }

        // Return the values (unique sources)
        return array_values($uniqueSources);
    }

    /**
     * Generate simple keyword-based embeddings for a text
     * This is a fallback for the free version that doesn't have embedding API
     * 
     * @param string $text The text to generate embeddings for
     * @return array The embedding vector (simplified)
     */
    public function generateEmbeddings($text)
    {
        // For the free version, we'll use a simple keyword extraction approach
        // This is not as good as real embeddings but can work as a fallback

        // Clean the text
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', '', $text);

        // Get the words
        $words = preg_split('/\s+/', $text);

        // Remove common stop words
        $stopWords = ['the', 'and', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'of', 'is', 'are', 'was', 'were'];
        $words = array_diff($words, $stopWords);

        // Count word frequencies
        $wordCounts = array_count_values($words);

        // Sort by frequency
        arsort($wordCounts);

        // Take top 50 words
        $topWords = array_slice($wordCounts, 0, 50, true);

        // Create a simple vector (1 if word exists, 0 if not)
        $vector = [];
        foreach (array_keys($topWords) as $word) {
            $vector[] = 1;
        }

        // Pad to 50 dimensions
        while (count($vector) < 50) {
            $vector[] = 0;
        }

        Log::info('Generated simple embeddings', ['word_count' => count($topWords)]);

        return $vector;
    }

    /**
     * Test the connection to the Gemini API
     * 
     * @return array The test result
     */
    public function testConnection()
    {
        $apiKey = config('ai.gemini_api_key');
        $model = config('ai.gemini_model', 'gemini-1.5-flash');

        // Use the updated v1 endpoint with direct model reference
        $apiEndpoint = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => "Hello, can you hear me?"]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 100,
            ]
        ];

        try {
            // Disable SSL verification for testing
            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($apiEndpoint, $payload);

            $data = $response->json();

            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'message' => 'API Error: ' . ($data['error']['message'] ?? 'Unknown error'),
                    'data' => $data,
                ];
            }

            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response content';

            return [
                'success' => true,
                'message' => 'Connection successful',
                'response' => $content,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    }

    /**
     * Generate a fallback response without using the API
     * 
     * @param string $userMessage The user's message
     * @param array $context The context from course materials
     * @return array The response
     */
    public function generateFallbackResponse($userMessage, $context = [])
    {
        // Create a simple response that acknowledges the question
        $response = "I'm currently experiencing technical difficulties connecting to my knowledge base. ";
        $response .= "Your question was: \"{$userMessage}\". ";

        if (!empty($context)) {
            $response .= "I found some potentially relevant materials that might help:\n\n";

            foreach (array_slice($context, 0, 3) as $item) {
                $response .= "From {$item['source']}:\n";
                $response .= substr($item['content'], 0, 200) . "...\n\n";
            }
        } else {
            $response .= "Unfortunately, I couldn't find any relevant course materials for your question.";
        }

        $response .= "\n\nPlease try again later or contact support if this issue persists.";

        return [
            'content' => $response,
            'usage' => null,
        ];
    }

    /**
     * Get general course content regardless of the query
     * 
     * @param int $courseId The course ID
     * @return \Illuminate\Database\Eloquent\Collection The chunks
     */
    private function getGeneralCourseContent($courseId)
    {
        Log::info('Fetching general course content', ['course_id' => $courseId]);

        // Get ALL materials for this course
        $materials = DB::table('course_materials')
            ->where('course_id', $courseId)
            ->where('is_processed', true)
            ->get();

        if ($materials->isEmpty()) {
            Log::warning('No processed materials found for course', ['course_id' => $courseId]);
            return collect([]);
        }

        // Get a sample of chunks from each material
        $allChunks = collect();

        foreach ($materials as $material) {
            // Get the first chunk from each material (usually contains intro/overview)
            $firstChunk = MaterialChunk::where('course_material_id', $material->id)
                ->orderBy('chunk_index')
                ->first();

            if ($firstChunk) {
                $allChunks->push($firstChunk);
            }

            // Also get a random chunk from the middle of longer materials
            $randomChunk = MaterialChunk::where('course_material_id', $material->id)
                ->inRandomOrder()
                ->first();

            if ($randomChunk && $randomChunk->id != $firstChunk->id) {
                $allChunks->push($randomChunk);
            }
        }

        Log::info('Found general course chunks', [
            'count' => $allChunks->count(),
            'materials_count' => $materials->count()
        ]);

        return $allChunks;
    }
}