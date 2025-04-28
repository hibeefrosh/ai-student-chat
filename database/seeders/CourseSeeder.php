<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'title' => 'Introduction to Computer Science',
                'code' => 'CS101',
                'description' => 'This course provides a broad introduction to computer science and programming. Students will learn basic programming concepts, algorithms, and problem-solving techniques.',
                'instructor' => 'Dr. Alan Turing',
                'is_active' => true,
            ],
            [
                'title' => 'Data Structures and Algorithms',
                'code' => 'CS201',
                'description' => 'This course covers fundamental data structures and algorithms. Topics include arrays, linked lists, stacks, queues, trees, graphs, sorting, searching, and algorithm analysis.',
                'instructor' => 'Dr. Ada Lovelace',
                'is_active' => true,
            ],
            [
                'title' => 'Web Development Fundamentals',
                'code' => 'WEB101',
                'description' => 'Learn the basics of web development including HTML, CSS, JavaScript, and responsive design principles. Students will build several web projects throughout the course.',
                'instructor' => 'Prof. Tim Berners-Lee',
                'is_active' => true,
            ],
            [
                'title' => 'Artificial Intelligence Principles',
                'code' => 'AI300',
                'description' => 'This course introduces the fundamental concepts and techniques of artificial intelligence. Topics include search algorithms, knowledge representation, machine learning, and neural networks.',
                'instructor' => 'Dr. Grace Hopper',
                'is_active' => true,
            ],
            [
                'title' => 'Database Systems',
                'code' => 'DB202',
                'description' => 'An introduction to database design and management systems. Students will learn about data modeling, SQL, normalization, and database administration.',
                'instructor' => 'Prof. Edgar Codd',
                'is_active' => true,
            ],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}