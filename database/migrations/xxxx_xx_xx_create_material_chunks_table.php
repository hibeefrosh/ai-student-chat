<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('material_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_material_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->integer('chunk_index');
            $table->json('embedding')->nullable(); // Vector embedding for similarity search
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_chunks');
    }
};