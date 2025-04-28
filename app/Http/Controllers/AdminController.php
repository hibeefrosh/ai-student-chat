<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\Course;
use App\Models\CourseMaterial;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'courses' => Course::count(),
            'materials' => CourseMaterial::count(),
            'chat_sessions' => ChatSession::count(),
            'messages' => ChatSession::withCount('messages')->get()->sum('messages_count'),
            'recent_chats' => ChatSession::with('course')
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}