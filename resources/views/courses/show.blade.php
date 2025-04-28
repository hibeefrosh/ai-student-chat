<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6">
                        <a href="{{ route('courses.index') }}" class="text-indigo-600 hover:text-indigo-900">
                            &larr; Back to Courses
                        </a>
                    </div>

                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $course->title }}</h1>

                        <div class="flex flex-wrap gap-4 mb-4">
                            @if ($course->code)
                                <div class="bg-gray-100 px-3 py-1 rounded-full text-sm text-gray-700">
                                    Course Code: {{ $course->code }}
                                </div>
                            @endif

                            @if ($course->instructor)
                                <div class="bg-gray-100 px-3 py-1 rounded-full text-sm text-gray-700">
                                    Instructor: {{ $course->instructor }}
                                </div>
                            @endif
                        </div>

                        <div class="prose max-w-none">
                            <p>{{ $course->description }}</p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <a href="{{ route('student.chat', $course) }}"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="mr-2 -ml-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z">
                                </path>
                                <path
                                    d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z">
                                </path>
                            </svg>
                            Chat with AI Tutor
                        </a>
                    </div>

                    <!-- Course Materials Section -->
                    @if ($course->materials->count() > 0)
                        <div class="mt-12">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Course Materials</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($course->materials as $material)
                                    <div class="border rounded-lg p-4 bg-gray-50">
                                        <div class="flex items-start">
                                            <div class="mr-3 text-indigo-500">
                                                @if ($material->file_type == 'pdf')
                                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd"
                                                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif (in_array($material->file_type, ['docx', 'doc']))
                                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd"
                                                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif (in_array($material->file_type, ['ppt', 'pptx']))
                                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd"
                                                            d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd"
                                                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-800">{{ $material->title }}</h3>
                                                @if ($material->description)
                                                    <p class="text-sm text-gray-600 mt-1">{{ $material->description }}</p>
                                                @endif
                                                <div class="mt-2 text-xs text-gray-500">
                                                    <span class="uppercase">{{ $material->file_type }}</span> •
                                                    <span>{{ round($material->file_size / 1024) }} KB</span> •
                                                    <span>Added {{ $material->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>