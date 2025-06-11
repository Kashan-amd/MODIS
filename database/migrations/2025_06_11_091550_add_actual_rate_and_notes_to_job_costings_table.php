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
            $table->decimal('actual_rate', 12, 2)->nullable()->after('total_amount');
            $table->text('notes')->nullable()->after('actual_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_costings', function (Blueprint $table)
        {
            $table->dropColumn(['actual_rate', 'notes']);
        });
    }
};
