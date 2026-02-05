<div class="flex flex-col h-screen bg-gray-100" id="student-chat-root">
    <!-- Course Header -->
    <div class="bg-white shadow-sm p-2 border-b">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <h1 class="text-xl font-bold text-gray-800">{{ $course->title }}</h1>

                @if (!$chatSession->user_nickname)
                    <div class="ml-4">
                        <form wire:submit.prevent="updateNickname" class="flex items-center space-x-2">
                            <input type="text" wire:model="nickname" placeholder="Enter nickname"
                                class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 py-1">
                            <button type="submit"
                                class="px-2 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                Save
                            </button>
                        </form>
                    </div>
                @else
                    <p class="text-sm text-gray-500 ml-4">Chatting as: {{ $chatSession->user_nickname }}</p>
                @endif
            </div>

            <!-- Clear Chat Button -->
            <button wire:click="clearChat"
                class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
                onclick="return confirm('Are you sure you want to clear the chat history?')">
                Clear Chat
            </button>
        </div>
    </div>

    <!-- Chat Messages -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
        @if (count($messages) === 0)
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-center">
                    Welcome to the AI tutor for this course! Ask any question about the course materials.
                </p>
            </div>
        @endif

        @foreach ($messages as $message)
            <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }} animate-fade-in">
                <div
                    class="flex items-start space-x-2 {{ $message->role === 'user' ? 'flex-row-reverse space-x-reverse' : 'flex-row' }}">
                    <!-- Avatar -->
                    <div
                        class="w-8 h-8 rounded-full flex items-center justify-center {{ $message->role === 'user' ? 'bg-indigo-500' : 'bg-gray-700' }} text-white flex-shrink-0">
                        @if ($message->role === 'user')
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                    clip-rule="evenodd" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M9.504 1.132a1 1 0 01.992 0l1.75 1a1 1 0 11-.992 1.736L10 3.152l-1.254.716a1 1 0 11-.992-1.736l1.75-1zM5.618 4.504a1 1 0 01-.372 1.364L5.016 6l.23.132a1 1 0 11-.992 1.736L4 7.723V8a1 1 0 01-2 0V6a.996.996 0 01.52-.878l1.734-.99a1 1 0 011.364.372zm8.764 0a1 1 0 011.364-.372l1.733.99A1.002 1.002 0 0118 6v2a1 1 0 11-2 0v-.277l-.254.145a1 1 0 11-.992-1.736l.23-.132-.23-.132a1 1 0 01-.372-1.364zm-7 4a1 1 0 011.364-.372L10 8.848l1.254-.716a1 1 0 11.992 1.736L11 10.58V12a1 1 0 11-2 0v-1.42l-1.246-.712a1 1 0 01-.372-1.364zM3 11a1 1 0 011 1v1.42l1.246.712a1 1 0 11-.992 1.736l-1.75-1A1 1 0 012 14v-2a1 1 0 011-1zm14 0a1 1 0 011 1v2a1 1 0 01-.504.868l-1.75 1a1 1 0 11-.992-1.736L16 13.42V12a1 1 0 011-1zm-9.618 5.504a1 1 0 011.364-.372l.254.145V16a1 1 0 112 0v.277l.254-.145a1 1 0 11.992 1.736l-1.735.992a.995.995 0 01-1.022 0l-1.735-.992a1 1 0 01-.372-1.364z"
                                    clip-rule="evenodd" />
                            </svg>
                        @endif
                    </div>

                    <!-- Message Content -->
                    <div
                        class="{{ $message->role === 'user' ? 'bg-indigo-100 text-gray-800' : 'bg-white text-gray-800' }} rounded-lg shadow p-3 max-w-3xl">
                        @if ($message->role === 'assistant')
                            <div class="prose prose-sm max-w-none">
                                {!! nl2br(e($message->content)) !!}
                            </div>

                            @if (!empty($message->referenced_materials))
                                <div class="mt-2 pt-2 border-t border-gray-200">
                                    <p class="text-xs text-gray-500 font-semibold">Sources:</p>
                                    <ul class="text-xs text-gray-500 list-disc pl-4">
                                        @foreach ($message->referenced_materials as $source)
                                            <li>{{ $source['title'] }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @else
                            <p>{!! nl2br(e($message->content)) !!}</p>
                        @endif
                        <div class="text-xs text-gray-400 mt-1">
                            {{ $message->created_at->format('g:i A') }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @if ($isProcessing)
            <div class="flex justify-start animate-fade-in">
                <div class="flex items-start space-x-2">
                    <!-- AI Avatar -->
                    <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-700 text-white flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M9.504 1.132a1 1 0 01.992 0l1.75 1a1 1 0 11-.992 1.736L10 3.152l-1.254.716a1 1 0 11-.992-1.736l1.75-1zM5.618 4.504a1 1 0 01-.372 1.364L5.016 6l.23.132a1 1 0 11-.992 1.736L4 7.723V8a1 1 0 01-2 0V6a.996.996 0 01.52-.878l1.734-.99a1 1 0 011.364.372zm8.764 0a1 1 0 011.364-.372l1.733.99A1.002 1.002 0 0118 6v2a1 1 0 11-2 0v-.277l-.254.145a1 1 0 11-.992-1.736l.23-.132-.23-.132a1 1 0 01-.372-1.364zm-7 4a1 1 0 011.364-.372L10 8.848l1.254-.716a1 1 0 11.992 1.736L11 10.58V12a1 1 0 11-2 0v-1.42l-1.246-.712a1 1 0 01-.372-1.364zM3 11a1 1 0 011 1v1.42l1.246.712a1 1 0 11-.992 1.736l-1.75-1A1 1 0 012 14v-2a1 1 0 011-1zm14 0a1 1 0 011 1v2a1 1 0 01-.504.868l-1.75 1a1 1 0 11-.992-1.736L16 13.42V12a1 1 0 011-1zm-9.618 5.504a1 1 0 011.364-.372l.254.145V16a1 1 0 112 0v.277l.254-.145a1 1 0 11.992 1.736l-1.735.992a.995.995 0 01-1.022 0l-1.735-.992a1 1 0 01-.372-1.364z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                    <div class="bg-white rounded-lg shadow p-3">
                        <div class="flex items-center gap-1.5 typing-dots">
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Message Input -->
    <div class="bg-white border-t p-3">
        <div class="max-w-7xl mx-auto">
            <form wire:submit.prevent="sendMessage" class="flex space-x-2" id="message-form">
                <textarea wire:model="message" placeholder="Ask a question about the course..."
                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    rows="2" id="message-input"
                    @keydown.enter.prevent="if(!event.shiftKey) { $event.target.form.dispatchEvent(new Event('submit')); }"></textarea>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 self-end transition"
                    @if($isProcessing) disabled @endif>
                    <span class="flex items-center">
                        @if($isProcessing)
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        @endif
                        Send
                    </span>
                </button>
            </form>
        </div>
    </div>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Typing indicator: 3 bouncing dots */
        .typing-dots {
            min-height: 20px;
        }

        .typing-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #4f46e5;
            animation: typingBounce 1.4s ease-in-out infinite both;
        }

        .typing-dot:nth-child(1) { animation-delay: 0s; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typingBounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-6px); }
        }
    </style>

    <script>
        // Auto-scroll to bottom of chat so the latest message is always visible
        document.addEventListener('livewire:initialized', function () {
            const chatContainer = document.getElementById('chat-messages');
            const messageInput = document.getElementById('message-input');
            const messageForm = document.getElementById('message-form');

            function scrollToBottom() {
                if (chatContainer) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            }

            // Initial scroll
            scrollToBottom();

            // Always scroll after AI response is received (DOM has been updated)
            Livewire.on('chat-response-received', () => {
                setTimeout(scrollToBottom, 50);
            });

            // Clear input after form submission
            messageForm.addEventListener('submit', function () {
                setTimeout(() => {
                    messageInput.value = '';
                    scrollToBottom();
                }, 0);
            });

            // Scroll when Livewire re-renders (e.g. new user message)
            Livewire.hook('morph.updated', ({ el, component }) => {
                if (component && component.name === 'student-chat') {
                    scrollToBottom();
                }
            });

            // After we show the user message + typing dots, run the AI in a second request
            Livewire.on('start-ai-response', () => {
                setTimeout(() => {
                    const root = document.getElementById('student-chat-root');
                    if (!root) return;
                    const id = root.getAttribute('wire:id');
                    if (id) {
                        const comp = Livewire.find(id);
                        if (comp) comp.call('generateAIResponse');
                    }
                }, 0);
            });
        });
    </script>
</div>