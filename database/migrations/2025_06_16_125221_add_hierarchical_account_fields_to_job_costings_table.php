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
            // Add hierarchical account fields
            $table->foreignId('sub_account_id')->nullable()->after('account_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('sub_item_id')->nullable()->after('sub_account_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('sub_account_name')->nullable()->after('sub_item_id');
            $table->string('sub_item_name')->nullable()->after('sub_account_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_costings', function (Blueprint $table)
        {
            $table->dropForeign(['sub_account_id']);
            $table->dropForeign(['sub_item_id']);
            $table->dropColumn(['sub_account_id', 'sub_item_id', 'sub_account_name', 'sub_item_name']);
        });
    }
};
