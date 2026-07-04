// database/migrations/2026_06_19_000004_create_internship_opportunities_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->string('required_major');
            $table->json('required_skills')->nullable();
            $table->integer('available_positions')->default(1);
            $table->integer('filled_positions')->default(0);
            $table->string('location');
            $table->boolean('is_remote')->default(false);
            $table->integer('duration_months');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->dateTime('application_deadline');
            $table->enum('status', ['open', 'closed', 'draft', 'archived'])->default('draft');
            $table->decimal('salary', 10, 2)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->index('provider_id');
            $table->index('status');
            $table->index('required_major');
            $table->index('application_deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_opportunities');
    }
};
