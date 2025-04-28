<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use App\Models\CourseMaterial;
use App\Services\MaterialProcessingService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Jobs\ProcessMaterialJob;
use Illuminate\Support\Facades\Log;

class MaterialManager extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'tailwind';

    // Form properties
    public $courseId;
    public $title = '';
    public $description = '';
    public $file;
    public $materialId;

    // UI state properties
    public $showFormModal = false;
    public $isEditing = false;
    public $searchTerm = '';
    public $processingStatus = [];
    public $showDeleteModal = false;
    public $materialToDelete = null;

    protected $rules = [
        'courseId' => 'required|exists:courses,id',
        'title' => 'required|min:3|max:255',
        'description' => 'nullable|max:1000',
    ];

    protected $listeners = ['refreshMaterials' => '$refresh'];

    public function mount($courseId = null)
    {
        $this->courseId = $courseId;
    }

    public function render()
    {
        $courses = Course::orderBy('title')->get();

        $query = CourseMaterial::query();

        if ($this->courseId) {
            $query->where('course_id', $this->courseId);
        }

        if ($this->searchTerm) {
            $query->where('title', 'like', '%' . $this->searchTerm . '%');
        }

        $materials = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.admin.material-manager', [
            'courses' => $courses,
            'materials' => $materials,
        ])->layout('layouts.app');
    }

    public function updatedCourseId()
    {
        $this->resetPage();
    }

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showFormModal = true;
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $this->resetForm();

        $material = CourseMaterial::findOrFail($id);
        $this->materialId = $material->id;
        $this->courseId = $material->course_id;
        $this->title = $material->title;
        $this->description = $material->description;

        $this->isEditing = true;
        $this->showFormModal = true;

        Log::info('Opening edit modal for material: ' . $id);
    }

    public function closeFormModal()
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function saveMaterial()
    {
        if ($this->isEditing) {
            $this->validate([
                'courseId' => 'required|exists:courses,id',
                'title' => 'required|min:3|max:255',
                'description' => 'nullable|max:1000',
            ]);

            $material = CourseMaterial::findOrFail($this->materialId);
            $material->update([
                'course_id' => $this->courseId,
                'title' => $this->title,
                'description' => $this->description,
            ]);

            Log::info('Updated material: ' . $this->materialId);
            session()->flash('message', 'Material updated successfully!');
        } else {
            $this->validate([
                'courseId' => 'required|exists:courses,id',
                'title' => 'required|min:3|max:255',
                'description' => 'nullable|max:1000',
                'file' => 'required|file|max:50000|mimes:pdf,docx,txt,ppt,pptx',
            ]);

            // Store file
            $path = $this->file->store('course-materials');
            $fileType = $this->file->getClientOriginalExtension();
            $fileSize = $this->file->getSize();

            // Create material record
            $material = CourseMaterial::create([
                'course_id' => $this->courseId,
                'title' => $this->title,
                'description' => $this->description,
                'file_path' => $path,
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'is_processed' => false,
            ]);

            // Process material in background
            $processingService = app(MaterialProcessingService::class);
            $processingService->processMaterial($material);

            ProcessMaterialJob::dispatch($material);

            Log::info('Created new material');
            session()->flash('message', 'Material uploaded successfully and is being processed!');
        }

        $this->closeFormModal();

        // Redirect to refresh the page
        return redirect(request()->header('Referer'));
    }

    public function openDeleteModal($id)
    {
        $this->materialToDelete = $id;
        $this->showDeleteModal = true;

        Log::info('Opening delete modal for material: ' . $id);
    }

    public function closeDeleteModal()
    {
        $this->materialToDelete = null;
        $this->showDeleteModal = false;

        Log::info('Closing delete modal');
    }

    public function deleteMaterial()
    {
        if ($this->materialToDelete) {
            $material = CourseMaterial::findOrFail($this->materialToDelete);
            $material->delete();

            Log::info('Deleted material: ' . $this->materialToDelete);

            $this->materialToDelete = null;
            $this->showDeleteModal = false;

            session()->flash('message', 'Material deleted successfully!');

            // Redirect to refresh the page
            return redirect(request()->header('Referer'));
        }
    }

    public function reprocess($id)
    {
        $material = CourseMaterial::findOrFail($id);
        $material->update(['is_processed' => false]);

        $processingService = app(MaterialProcessingService::class);
        $processingService->processMaterial($material);

        ProcessMaterialJob::dispatch($material);

        $this->processingStatus[$id] = 'Processing started';

        session()->flash('message', 'Material reprocessing started!');
    }

    private function resetForm()
    {
        $this->title = '';
        $this->description = '';
        $this->file = null;
        $this->materialId = null;
        $this->resetValidation();

        Log::info('Form reset');
    }
}