<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['active', 'pending_review', 'rejected', 'suspended'])
                  ->default('active')
                  ->after('email_verified_at');

            $table->text('rejection_reason')->nullable()->after('account_status');
            $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');

            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['account_status', 'rejection_reason', 'reviewed_at', 'reviewed_by']);
        });
    }
};
