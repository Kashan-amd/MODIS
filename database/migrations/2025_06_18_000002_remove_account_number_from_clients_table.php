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
        Schema::table('clients', function (Blueprint $table)
        {
            // Drop the foreign key constraint first
            $table->dropForeign(['account_number']);
            // Then drop the column
            $table->dropColumn('account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table)
        {
            $table->string('account_number')->nullable()->after('bm_official');
            $table->foreign('account_number')->references('account_number')->on('chart_of_accounts');
        });
    }
};
