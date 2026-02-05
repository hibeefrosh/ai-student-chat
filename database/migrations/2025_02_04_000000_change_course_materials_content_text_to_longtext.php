<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE course_materials MODIFY content_text LONGTEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE course_materials MODIFY content_text TEXT NULL');
    }
};
