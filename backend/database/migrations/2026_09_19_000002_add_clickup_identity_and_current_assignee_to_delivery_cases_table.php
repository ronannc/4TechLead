<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_cases', function (Blueprint $table): void {
            $table->string('clickup_task_id')->nullable()->after('task_ref');
            $table->foreignId('current_assignee_id')->nullable()->after('current_stage')
                ->constrained('people')->nullOnDelete();
            $table->index(['tenant_id', 'clickup_task_id']);
        });
    }

    public function down(): void
    {
        Schema::table('delivery_cases', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'clickup_task_id']);
            $table->dropConstrainedForeignId('current_assignee_id');
            $table->dropColumn('clickup_task_id');
        });
    }
};
