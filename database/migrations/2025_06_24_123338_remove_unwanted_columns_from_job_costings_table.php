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
        Schema::table('job_costings', function (Blueprint $table)
        {
            // Drop foreign key constraints first
            $table->dropForeign(['item_id']);
            $table->dropForeign(['account_id']);

            // Drop the unwanted columns
            $table->dropColumn(['item_id', 'account_id', 'account_name', 'account_number', 'is_cogs_account']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_costings', function (Blueprint $table)
        {
            // Re-add the columns
            $table->foreignId('item_id')->nullable()->after('vendor_id')->constrained('items')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->after('item_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('account_number')->nullable()->after('account_id');
            $table->string('account_name')->nullable()->after('account_number');
            $table->boolean('is_cogs_account')->default(false)->after('total_amount');
        });
    }
};
