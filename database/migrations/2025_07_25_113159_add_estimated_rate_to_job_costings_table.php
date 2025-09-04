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
        Schema::table('job_costings', function (Blueprint $table) {
            $table->decimal('estimated_rate', 12, 2)->nullable()->after('rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_costings', function (Blueprint $table) {
            $table->dropColumn('estimated_rate');
        });
    }
};
