<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_webhook_events', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'event_type', 'received_at'],
                'integration_webhook_tenant_type_received_index',
            );
        });

        Schema::table('delivery_case_participants', function (Blueprint $table): void {
            $table->index(
                ['delivery_case_id', 'role', 'valid_from', 'valid_to'],
                'delivery_case_participant_interval_lookup_index',
            );
        });

        Schema::table('delivery_case_assignment_history', function (Blueprint $table): void {
            $table->index(
                ['delivery_case_id', 'assigned_at', 'unassigned_at'],
                'delivery_case_assignment_interval_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('delivery_case_assignment_history', function (Blueprint $table): void {
            $table->dropIndex('delivery_case_assignment_interval_lookup_index');
        });

        Schema::table('delivery_case_participants', function (Blueprint $table): void {
            $table->dropIndex('delivery_case_participant_interval_lookup_index');
        });

        Schema::table('integration_webhook_events', function (Blueprint $table): void {
            $table->dropIndex('integration_webhook_tenant_type_received_index');
        });
    }
};
