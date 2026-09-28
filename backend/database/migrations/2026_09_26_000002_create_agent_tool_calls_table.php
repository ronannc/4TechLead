<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_tool_calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('approval_id')->nullable();
            $table->string('tool_name');
            $table->string('mode')->nullable();
            $table->string('status');
            $table->json('arguments')->nullable();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'tool_name', 'created_at']);
            $table->index('approval_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_tool_calls');
    }
};
