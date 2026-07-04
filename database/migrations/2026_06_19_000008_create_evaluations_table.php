// database/migrations/2026_06_19_000008_create_evaluations_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('opportunity_id')->constrained('internship_opportunities')->onDelete('cascade');
            $table->enum('evaluator_type', ['provider', 'supervisor']);
            $table->unsignedBigInteger('evaluator_id');
            $table->decimal('attendance_grade', 5, 2)->nullable();
            $table->decimal('commitment_grade', 5, 2)->nullable();
            $table->decimal('technical_skills_grade', 5, 2)->nullable();
            $table->decimal('teamwork_grade', 5, 2)->nullable();
            $table->decimal('communication_grade', 5, 2)->nullable();
            $table->decimal('overall_grade', 5, 2)->nullable();
            $table->text('evaluation_notes')->nullable();
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->date('evaluation_date');
            $table->boolean('is_final')->default(false);
            $table->timestamps();

            $table->index('student_id');
            $table->index('opportunity_id');
            $table->index('evaluator_type');
            $table->index('evaluation_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
