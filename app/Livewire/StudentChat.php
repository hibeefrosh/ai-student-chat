<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Course;
use App\Services\AIService;
use Illuminate\Support\Str;
use Livewire\Component;

class StudentChat extends Component
{
    public $courseId;
    public $course;
    public $sessionId;
    public $nickname;
    public $message = '';
    public $chatSession;
    public $messages = [];
    public $isProcessing = false;

    public function mount(Course $course)
    {
        $this->course = $course;
        $this->courseId = $course->id;

        // Create or retrieve session
        $this->sessionId = session()->get('chat_session_id_' . $this->courseId);

        if (!$this->sessionId) {
            $this->sessionId = (string) Str::uuid(); // Convert UUID to string
            session()->put('chat_session_id_' . $this->courseId, $this->sessionId);
        }

        $this->chatSession = ChatSession::firstOrCreate(
            ['session_id' => $this->sessionId, 'course_id' => $this->courseId],
            ['user_nickname' => null]
        );

        $this->nickname = $this->chatSession->user_nickname;

        $this->loadMessages();
    }

    public function loadMessages()
    {
        $this->messages = $this->chatSession->messages()->orderBy('created_at')->get();
    }

    public function sendMessage()
    {
        if (empty(trim($this->message))) {
            return;
        }

        // Store message locally before clearing input
        $userMessage = trim($this->message);

        // Reset input field immediately
        $this->reset('message');

        $this->isProcessing = true;

        // Save user message to database
        $this->chatSession->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // Reload messages to show user message immediately
        $this->loadMessages();

        // Generate AI response
        $aiService = app(AIService::class);
        $response = $aiService->generateResponse($this->chatSession, $userMessage);

        // Save AI response
        $this->chatSession->messages()->create([
            'role' => 'assistant',
            'content' => $response['content'],
            'referenced_materials' => $response['sources'] ?? null,
            'metadata' => $response['metadata'] ?? null,
        ]);

        $this->isProcessing = false;
        $this->loadMessages();
    }

    public function updateNickname()
    {
        if (!empty(trim($this->nickname))) {
            $this->chatSession->update(['user_nickname' => $this->nickname]);
        }
    }

    public function clearChat()
    {
        // Delete all messages for this chat session
        $this->chatSession->messages()->delete();

        // Reload messages (should be empty now)
        $this->loadMessages();

        // Add a welcome message from the assistant
        $this->chatSession->messages()->create([
            'role' => 'assistant',
            'content' => 'Chat history has been cleared. How can I help you with the course material today?',
        ]);

        // Reload messages to show the welcome message
        $this->loadMessages();
    }

    public function render()
    {
        return view('livewire.student-chat')
            ->layout('layouts.app');
    }
}