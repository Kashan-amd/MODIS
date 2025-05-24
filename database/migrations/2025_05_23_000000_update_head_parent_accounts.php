<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Account;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // First modify the table to allow NULL for organization_id
        Schema::table('chart_of_accounts', function (Blueprint $table)
        {
            $table->unsignedBigInteger('organization_id')->nullable()->change();
        });

        // Then find all parent accounts that should be head parents
        // and set their organization_id to NULL
        DB::table('chart_of_accounts')
            ->where('is_parent', true)
            ->where('organization_id', 1) // Assuming 1 is the default organization ID
            ->update(['organization_id' => null]);
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // If needed, set organization_id back to 1 for all head parent accounts
        DB::table('chart_of_accounts')
            ->where('is_parent', true)
            ->whereNull('organization_id')
            ->update(['organization_id' => 1]);
    }
};
