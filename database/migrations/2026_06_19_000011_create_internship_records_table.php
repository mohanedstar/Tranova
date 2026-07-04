// database/migrations/2026_06_19_000011_create_internship_records_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('opportunity_id')->constrained('internship_opportunities')->onDelete('cascade');
            $table->foreignId('supervisor_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_hours', 6, 2);
            $table->foreignId('provider_evaluation_id')->nullable()->constrained('evaluations')->onDelete('set null');
            $table->foreignId('supervisor_evaluation_id')->nullable()->constrained('evaluations')->onDelete('set null');
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->enum('status', ['in_progress', 'completed', 'approved', 'rejected'])->default('in_progress');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->string('completion_certificate_path')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_records');
    }
};
