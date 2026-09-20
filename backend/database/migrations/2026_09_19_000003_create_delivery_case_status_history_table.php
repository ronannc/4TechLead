<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_case_status_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_webhook_id')->nullable()->constrained('integration_webhook_events')->nullOnDelete();
            $table->foreignId('status_changed_by_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('assignee_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('from_stage')->nullable();
            $table->string('to_stage');
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['delivery_case_id', 'source_webhook_id', 'to_stage'], 'delivery_case_status_history_event_unique');
            $table->index(['tenant_id', 'delivery_case_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_case_status_history');
    }
};
