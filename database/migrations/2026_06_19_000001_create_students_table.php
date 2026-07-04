// database/migrations/2026_06_19_000001_create_students_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('student_id')->unique();
            $table->string('major');
            $table->string('university');
            $table->enum('year_of_study', ['1', '2', '3', '4', '5']);
            $table->decimal('gpa', 3, 2)->nullable();
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('major');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
