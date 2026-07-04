// database/migrations/2026_06_19_000002_create_providers_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('organization_name');
            $table->enum('organization_type', ['company', 'hospital', 'government', 'nonprofit', 'other']);
            $table->text('address');
            $table->string('city');
            $table->string('country')->default('Palestine');
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index('organization_name');
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
