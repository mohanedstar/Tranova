// database/migrations/2026_06_19_000005_create_applications_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('opportunity_id')->constrained('internship_opportunities')->onDelete('cascade');
            $table->text('cover_letter')->nullable();
            $table->string('cv_path');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('providers')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->text('provider_notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'opportunity_id']);
            $table->index('student_id');
            $table->index('opportunity_id');
            $table->index('status');
            $table->index('applied_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
