// database/migrations/2026_06_19_000003_create_supervisors_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('employee_id')->unique();
            $table->string('department');
            $table->enum('academic_title', ['professor', 'assistant_professor', 'lecturer', 'instructor']);
            $table->string('specialization')->nullable();
            $table->string('office_location')->nullable();
            $table->integer('max_students')->default(10);
            $table->timestamps();

            $table->index('employee_id');
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisors');
    }
};
