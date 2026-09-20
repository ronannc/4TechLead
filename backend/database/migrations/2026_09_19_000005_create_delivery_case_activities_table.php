<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_case_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_webhook_id')->nullable()->constrained('integration_webhook_events')->nullOnDelete();
            $table->foreignId('assignee_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('author_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('reviewer_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('activity_type');
            $table->string('repository')->nullable();
            $table->string('branch')->nullable();
            $table->string('pull_request_number')->nullable();
            $table->string('commit_sha')->nullable();
            $table->string('workflow_run_id')->nullable();
            $table->decimal('numeric_value', 12, 2)->nullable();
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['source_webhook_id', 'activity_type'], 'delivery_case_activity_webhook_type_unique');
            $table->index(['tenant_id', 'delivery_case_id', 'activity_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_case_activities');
    }
};
