<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-3xl font-bold text-gray-800 mb-6">Available Courses</h1>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse ($courses as $course)
                            <div class="bg-white rounded-lg border border-gray-200 shadow-md overflow-hidden">
                                <div class="p-5">
                                    <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900">{{ $course->title }}
                                    </h5>

                                    @if ($course->code)
                                        <p class="text-sm text-gray-500 mb-2">Course Code: {{ $course->code }}</p>
                                    @endif

                                    @if ($course->instructor)
                                        <p class="text-sm text-gray-500 mb-4">Instructor: {{ $course->instructor }}</p>
                                    @endif

                                    <p class="mb-3 font-normal text-gray-700">
                                        {{ Str::limit($course->description, 150) }}
                                    </p>

                                    <div class="flex justify-between mt-4">
                                        <a href="{{ route('courses.show', $course) }}"
                                            class="inline-flex items-center py-2 px-3 text-sm font-medium text-center text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300">
                                            View Details
                                            <svg class="ml-2 -mr-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd"
                                                    d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </a>

                                        <a href="{{ route('student.chat', $course) }}"
                                            class="inline-flex items-center py-2 px-3 text-sm font-medium text-center text-indigo-600 bg-indigo-100 rounded-md hover:bg-indigo-200 focus:ring-4 focus:ring-indigo-300">
                                            Chat with AI Tutor
                                            <svg class="ml-2 -mr-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z">
                                                </path>
                                                <path
                                                    d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z">
                                                </path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-3 text-center py-12">
                                <p class="text-gray-500 text-lg">No courses available at the moment.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>