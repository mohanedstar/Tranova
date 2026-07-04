// database/migrations/2026_06_19_000007_create_weekly_reports_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('opportunity_id')->constrained('internship_opportunities')->onDelete('cascade');
            $table->date('report_date');
            $table->integer('week_number');
            $table->decimal('training_hours', 5, 2);
            $table->text('completed_tasks');
            $table->text('challenges')->nullable();
            $table->text('achievements')->nullable();
            $table->text('next_week_plan')->nullable();
            $table->json('attachments')->nullable();
            $table->enum('status', ['draft', 'submitted', 'reviewed', 'approved'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('supervisors')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('supervisor_comments')->nullable();
            $table->decimal('grade', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'week_number', 'opportunity_id']);
            $table->index('student_id');
            $table->index('report_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reports');
    }
};
