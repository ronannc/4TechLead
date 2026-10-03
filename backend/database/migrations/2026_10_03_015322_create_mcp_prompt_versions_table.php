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
        Schema::create('mcp_prompt_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('prompt_key');
            $table->unsignedInteger('version');
            $table->longText('content');
            $table->string('status')->default('draft');
            $table->foreignId('created_by_admin_user_id')->constrained('admin_users')->restrictOnDelete();
            $table->foreignId('published_by_admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['prompt_key', 'version']);
            $table->index(['prompt_key', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcp_prompt_versions');
    }
};
