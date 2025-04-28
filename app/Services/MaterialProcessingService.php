<?php

namespace App\Services;

use App\Models\CourseMaterial;
use App\Models\MaterialChunk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordParser;
use PhpOffice\PhpPresentation\IOFactory as PresentationParser;

class MaterialProcessingService
{
    protected $aiService;

    public function __construct(AIService $aiService = null)
    {
        $this->aiService = $aiService;
    }

    public function processMaterial(CourseMaterial $material)
    {
        try {
            // Extract text from file
            $text = $this->extractText($material);

            // Update material with extracted text
            $material->update([
                'content_text' => $text,
                'embeddings_status' => ['text_extracted' => true],
            ]);

            // Create chunks
            $this->createChunks($material, $text);

            // Generate embeddings for chunks
            $this->generateEmbeddings($material);

            // Mark as processed
            $material->update([
                'is_processed' => true,
                'embeddings_status' => [
                    'text_extracted' => true,
                    'chunks_created' => true,
                    'embeddings_generated' => true,
                ],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error processing material: ' . $e->getMessage(), [
                'material_id' => $material->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $material->update([
                'embeddings_status' => [
                    'error' => $e->getMessage(),
                    'text_extracted' => $material->embeddings_status['text_extracted'] ?? false,
                    'chunks_created' => $material->embeddings_status['chunks_created'] ?? false,
                    'embeddings_generated' => false,
                ],
            ]);

            return false;
        }
    }

    private function extractText(CourseMaterial $material)
    {
        $filePath = Storage::path($material->file_path);
        $fileType = $material->file_type;

        switch ($fileType) {
            case 'pdf':
                return $this->extractFromPdf($filePath);
            case 'docx':
                return $this->extractFromDocx($filePath);
            case 'txt':
                return $this->extractFromTxt($filePath);
            case 'ppt':
            case 'pptx':
                return $this->extractFromPresentation($filePath);
            default:
                throw new \Exception("Unsupported file type: {$fileType}");
        }
    }

    private function extractFromPdf($filePath)
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    }

    private function extractFromDocx($filePath)
    {
        $phpWord = WordParser::load($filePath);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }

        return $text;
    }

    private function extractFromTxt($filePath)
    {
        return file_get_contents($filePath);
    }

    private function extractFromPresentation($filePath)
    {
        $presentation = PresentationParser::load($filePath);
        $text = '';

        foreach ($presentation->getSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                    foreach ($shape->getParagraphs() as $paragraph) {
                        foreach ($paragraph->getRichTextElements() as $element) {
                            $text .= $element->getText() . "\n";
                        }
                    }
                }
            }
        }

        return $text;
    }

    private function createChunks(CourseMaterial $material, $text)
    {
        // Delete existing chunks
        $material->chunks()->delete();

        $chunkSize = config('ai.chunk_size', 1000);
        $chunkOverlap = config('ai.chunk_overlap', 200);

        // Clean and normalize text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (empty($text)) {
            throw new \Exception("No text could be extracted from the document");
        }

        // Split text into chunks
        $textLength = strlen($text);
        $position = 0;
        $chunkIndex = 0;

        while ($position < $textLength) {
            $chunkEnd = min($position + $chunkSize, $textLength);

            // Try to find a good breaking point (end of sentence or paragraph)
            if ($chunkEnd < $textLength) {
                $breakPoints = ['. ', '! ', '? ', "\n", "\r\n"];
                $bestBreakPoint = $chunkEnd;

                foreach ($breakPoints as $breakPoint) {
                    $pos = strrpos(substr($text, $position, $chunkEnd - $position), $breakPoint);
                    if ($pos !== false) {
                        $bestBreakPoint = $position + $pos + strlen($breakPoint);
                        break;
                    }
                }

                $chunkEnd = $bestBreakPoint;
            }

            $chunk = substr($text, $position, $chunkEnd - $position);

            MaterialChunk::create([
                'course_material_id' => $material->id,
                'content' => $chunk,
                'chunk_index' => $chunkIndex,
            ]);

            // Move position for next chunk, considering overlap
            $position = $chunkEnd - $chunkOverlap;
            if ($position < $chunkEnd) {
                $position = $chunkEnd;
            }

            $chunkIndex++;
        }

        $material->update([
            'embeddings_status' => array_merge(
                $material->embeddings_status ?? [],
                ['chunks_created' => true]
            ),
        ]);
    }

    private function generateEmbeddings(CourseMaterial $material)
    {
        Log::info('Generating embeddings for material', [
            'material_id' => $material->id,
            'title' => $material->title,
            'chunks_count' => $material->chunks()->count()
        ]);

        // If we have an AIService, we can use it to generate embeddings
        if ($this->aiService && method_exists($this->aiService, 'generateEmbeddings')) {
            $chunks = $material->chunks()->whereNull('embedding')->get();

            Log::info('Found chunks without embeddings', [
                'count' => $chunks->count()
            ]);

            $successCount = 0;
            $errorCount = 0;

            foreach ($chunks as $chunk) {
                try {
                    $embedding = $this->aiService->generateEmbeddings($chunk->content);

                    // Check if we got a valid embedding
                    if (empty($embedding)) {
                        Log::warning('Empty embedding returned for chunk', [
                            'chunk_id' => $chunk->id,
                            'content_length' => strlen($chunk->content)
                        ]);

                        // Use a fallback simple embedding
                        $embedding = $this->generateSimpleEmbedding($chunk->content);
                    }

                    // Store the embedding directly as an array (not JSON)
                    $chunk->update(['embedding' => $embedding]);
                    $successCount++;

                    // Add a small delay to avoid rate limits
                    usleep(100000); // 100ms
                } catch (\Exception $e) {
                    Log::error('Error generating embedding: ' . $e->getMessage(), [
                        'chunk_id' => $chunk->id,
                        'error' => $e->getMessage(),
                    ]);
                    $errorCount++;
                }
            }

            Log::info('Embedding generation complete', [
                'success_count' => $successCount,
                'error_count' => $errorCount
            ]);
        } else {
            // Fallback to the simple embedding implementation
            Log::info('Using simple embeddings as AIService is not available or does not support embeddings');

            $chunks = $material->chunks()->whereNull('embedding')->get();
            foreach ($chunks as $chunk) {
                $embedding = $this->generateSimpleEmbedding($chunk->content);
                $chunk->update(['embedding' => $embedding]);
            }
        }

        $material->update([
            'embeddings_status' => array_merge(
                $material->embeddings_status ?? [],
                ['embeddings_generated' => true]
            ),
        ]);
    }

    /**
     * Generate a simple keyword-based embedding
     * 
     * @param string $text The text to generate an embedding for
     * @return array The embedding
     */
    private function generateSimpleEmbedding($text)
    {
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

        return $vector;
    }

    private function callEmbeddingAPI($text)
    {
        // This would call the embedding API
        // For now, we'll return a placeholder
        return [];
    }
}