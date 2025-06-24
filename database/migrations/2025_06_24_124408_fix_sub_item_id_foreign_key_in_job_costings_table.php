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
            // Drop the incorrect foreign key constraint
            $table->dropForeign(['sub_item_id']);

            // Add the correct foreign key constraint pointing to items table
            $table->foreign('sub_item_id')->references('id')->on('items')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_costings', function (Blueprint $table)
        {
            // Drop the correct foreign key constraint
            $table->dropForeign(['sub_item_id']);

            // Re-add the incorrect foreign key constraint (for rollback purposes)
            $table->foreign('sub_item_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
        });
    }
};
