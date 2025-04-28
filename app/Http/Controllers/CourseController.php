<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::where('is_active', true)
            ->orderBy('title')
            ->get();

        return view('courses.index', compact('courses'));
    }

    public function show(Course $course)
    {
        if (!$course->is_active) {
            abort(404);
        }

        return view('courses.show', compact('course'));
    }
}