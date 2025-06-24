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
        Schema::table('items', function (Blueprint $table)
        {
            $table->unsignedBigInteger('cogs_account_id')->nullable()->after('description');
            $table->foreign('cogs_account_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table)
        {
            $table->dropForeign(['cogs_account_id']);
            $table->dropColumn('cogs_account_id');
        });
    }
};
