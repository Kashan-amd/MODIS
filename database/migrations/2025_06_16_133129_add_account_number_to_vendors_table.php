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
        Schema::table('vendors', function (Blueprint $table)
        {
            $table->string('account_number')->nullable()->after('address');
            $table->foreign('account_number')->references('account_number')->on('chart_of_accounts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table)
        {
            $table->dropForeign(['account_number']);
            $table->dropColumn('account_number');
        });
    }
};
