<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class CourseManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Form properties
    public $title = '';
    public $description = '';
    public $code = '';
    public $instructor = '';
    public $isActive = true;
    public $courseId = null;

    // UI state properties
    public $showFormModal = false;
    public $isEditing = false;
    public $searchTerm = '';
    public $showDeleteModal = false;
    public $courseToDelete = null;

    protected $rules = [
        'title' => 'required|min:3|max:255',
        'description' => 'nullable|max:1000',
        'code' => 'nullable|max:50',
        'instructor' => 'nullable|max:255',
        'isActive' => 'boolean',
    ];

    // Reset pagination when search term changes
    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function render()
    {
        $courses = Course::where(function ($query) {
            $query->where('title', 'like', '%' . $this->searchTerm . '%')
                ->orWhere('code', 'like', '%' . $this->searchTerm . '%');
        })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.course-manager', [
            'courses' => $courses,
        ])->layout('layouts.app');
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

        $course = Course::findOrFail($id);
        $this->courseId = $course->id;
        $this->title = $course->title;
        $this->description = $course->description;
        $this->code = $course->code;
        $this->instructor = $course->instructor;
        $this->isActive = $course->is_active;

        $this->isEditing = true;
        $this->showFormModal = true;

        Log::info('Opening edit modal for course: ' . $id);
    }

    public function closeFormModal()
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function saveCourse()
    {
        $this->validate();

        if ($this->isEditing) {
            // Update existing course
            $course = Course::findOrFail($this->courseId);
            $course->update([
                'title' => $this->title,
                'description' => $this->description,
                'code' => $this->code,
                'instructor' => $this->instructor,
                'is_active' => $this->isActive,
            ]);

            Log::info('Updated course: ' . $this->courseId);
            session()->flash('message', 'Course updated successfully!');
        } else {
            // Create new course
            Course::create([
                'title' => $this->title,
                'description' => $this->description,
                'code' => $this->code,
                'instructor' => $this->instructor,
                'is_active' => $this->isActive,
            ]);

            Log::info('Created new course');
            session()->flash('message', 'Course created successfully!');
        }

        $this->closeFormModal();

        // Use the current URL for redirection
        return redirect(request()->header('Referer'));
    }

    public function openDeleteModal($id)
    {
        $this->courseToDelete = $id;
        $this->showDeleteModal = true;

        Log::info('Opening delete modal for course: ' . $id);
    }

    public function closeDeleteModal()
    {
        $this->courseToDelete = null;
        $this->showDeleteModal = false;

        Log::info('Closing delete modal');
    }

    public function deleteCourse()
    {
        if ($this->courseToDelete) {
            $course = Course::findOrFail($this->courseToDelete);
            $course->delete();

            Log::info('Deleted course: ' . $this->courseToDelete);

            $this->courseToDelete = null;
            $this->showDeleteModal = false;

            session()->flash('message', 'Course deleted successfully!');

            // Use the current URL for redirection
            return redirect(request()->header('Referer'));
        }
    }

    private function resetForm()
    {
        $this->title = '';
        $this->description = '';
        $this->code = '';
        $this->instructor = '';
        $this->isActive = true;
        $this->courseId = null;
        $this->resetValidation();

        Log::info('Form reset');
    }
}