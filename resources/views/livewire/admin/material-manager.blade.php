<div class="p-6 bg-white rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Course Materials Management</h2>
        <button wire:click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Upload New Material
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ session('message') }}</p>
        </div>
    @endif

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="courseFilter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Course</label>
            <select id="courseFilter" wire:model.live="courseId"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">All Courses</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="searchTerm" class="block text-sm font-medium text-gray-700 mb-1">Search Materials</label>
            <input type="text" id="searchTerm" wire:model.live.debounce.300ms="searchTerm"
                placeholder="Search by title..."
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>
    </div>

    <!-- Materials List -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Title
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Course
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($materials as $material)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $material->title }}</div>
                            <div class="text-sm text-gray-500">{{ Str::limit($material->description, 50) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $material->course ? $material->course->title : 'Unknown Course' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ strtoupper($material->file_type) }}
                            <span class="text-xs text-gray-400">
                                ({{ round($material->file_size / 1024 / 1024, 2) }} MB)
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($material->is_processed)
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Processed
                                </span>
                            @else
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    {{ $processingStatus[$material->id] ?? 'Pending' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button wire:click="openEditModal({{ $material->id }})"
                                class="text-indigo-600 hover:text-indigo-900 mr-2">
                                Edit
                            </button>
                            <button wire:click="openDeleteModal({{ $material->id }})"
                                class="text-red-600 hover:text-red-900 mr-2">
                                Delete
                            </button>
                            <button wire:click="reprocess({{ $material->id }})" class="text-green-600 hover:text-green-900">
                                Reprocess
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            No materials found. Upload your first material using the "Upload New Material" button.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $materials->links() }}
    </div>

    <!-- Material Form Modal -->
    @if($showFormModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:key="form-modal">
            <div class="bg-white rounded-lg p-6 max-w-2xl mx-auto w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ $isEditing ? 'Edit Material' : 'Upload New Material' }}
                    </h3>
                    <button wire:click="closeFormModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveMaterial">
                    <div class="mb-4">
                        <label for="courseId" class="block text-sm font-medium text-gray-700">Course</label>
                        <select id="courseId" wire:model="courseId"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">Select a course</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                            @endforeach
                        </select>
                        @error('courseId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" id="title" wire:model="title"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="description" wire:model="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    @if(!$isEditing)
                        <div class="mb-4">
                            <label for="file" class="block text-sm font-medium text-gray-700">File</label>
                            <input type="file" id="file" wire:model="file" class="mt-1 block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-indigo-50 file:text-indigo-700
                                        hover:file:bg-indigo-100">
                            <div wire:loading wire:target="file" class="text-sm text-indigo-500 mt-1">Uploading...</div>
                            @error('file') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                Accepted file types: PDF, DOCX, TXT, PPT, PPTX (max 50MB)
                            </p>
                        </div>
                    @endif

                    <div class="flex justify-end space-x-2 mt-6">
                        <button type="button" wire:click="closeFormModal"
                            class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            {{ $isEditing ? 'Update Material' : 'Upload Material' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:key="delete-modal">
            <div class="bg-white rounded-lg p-6 max-w-md mx-auto">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Deletion</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Are you sure you want to delete this material? This action cannot be undone.
                </p>
                <div class="flex justify-end space-x-2">
                    <button wire:click="closeDeleteModal"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button wire:click="deleteMaterial" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>