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
        Schema::table('chart_of_accounts', function (Blueprint $table)
        {

            // Add level column to track hierarchy depth
            $table->unsignedTinyInteger('level')->default(0)->after('parent_id');

            // Add is_parent column to easily identify parent accounts
            $table->boolean('is_parent')->default(false)->after('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table)
        {
            $table->dropColumn('parent_id');
            $table->dropColumn('level');
            $table->dropColumn('is_parent');
        });
    }
};
