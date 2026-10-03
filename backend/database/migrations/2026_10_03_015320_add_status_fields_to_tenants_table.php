<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('status')->default('active')->index();
            $table->timestamp('blocked_at')->nullable();
            $table->foreignId('blocked_by_admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->text('blocked_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('blocked_by_admin_user_id');
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'blocked_at', 'blocked_reason']);
        });
    }
};
