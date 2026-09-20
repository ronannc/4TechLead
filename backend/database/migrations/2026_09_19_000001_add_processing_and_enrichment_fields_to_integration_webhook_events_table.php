<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_webhook_events', function (Blueprint $table): void {
            $table->string('task_ref')->nullable()->after('event_type');
            $table->string('processing_reason')->nullable()->after('status');
            $table->string('projection_status')->default('pending')->after('processing_reason');
            $table->timestamp('processed_at')->nullable()->after('received_at');
            $table->timestamp('occurred_at')->nullable()->after('processed_at');
            $table->string('enrichment_status')->default('not_required')->after('occurred_at');
            $table->unsignedInteger('enrichment_attempts')->default(0)->after('enrichment_status');
            $table->timestamp('enriched_at')->nullable()->after('enrichment_attempts');
            $table->text('enrichment_error')->nullable()->after('enriched_at');
            $table->timestamp('retention_until')->nullable()->after('enrichment_error');

            $table->index(['tenant_id', 'task_ref', 'occurred_at']);
            $table->index(['status', 'projection_status', 'enrichment_status'], 'integration_webhook_processing_index');
        });
    }

    public function down(): void
    {
        Schema::table('integration_webhook_events', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'task_ref', 'occurred_at']);
            $table->dropIndex('integration_webhook_processing_index');
            $table->dropColumn([
                'task_ref',
                'processing_reason',
                'projection_status',
                'processed_at',
                'occurred_at',
                'enrichment_status',
                'enrichment_attempts',
                'enriched_at',
                'enrichment_error',
                'retention_until',
            ]);
        });
    }
};
