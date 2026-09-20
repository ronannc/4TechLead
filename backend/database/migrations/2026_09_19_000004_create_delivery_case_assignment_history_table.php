<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_case_assignment_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_webhook_id')->nullable()->constrained('integration_webhook_events')->nullOnDelete();
            $table->foreignId('assignee_person_id')->constrained('people')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['delivery_case_id', 'assignee_person_id', 'assigned_at'], 'delivery_case_assignment_history_interval_unique');
            $table->index(['tenant_id', 'delivery_case_id', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_case_assignment_history');
    }
};
