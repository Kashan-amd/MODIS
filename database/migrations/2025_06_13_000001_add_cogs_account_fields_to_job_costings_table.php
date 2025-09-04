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
            // Make item_id nullable since COGS account items won't have an item_id
            $table->foreignId('item_id')->nullable()->change();

            // Add account-related fields
            $table->foreignId('account_id')->nullable()->after('item_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('account_number')->nullable()->after('account_id');
            $table->string('account_name')->nullable()->after('account_number');
            $table->boolean('is_cogs_account')->default(false)->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_costings', function (Blueprint $table)
        {
            // Revert changes
            $table->dropColumn(['account_id', 'account_number', 'account_name', 'is_cogs_account']);
            $table->foreignId('item_id')->nullable(false)->change();
        });
    }
};
