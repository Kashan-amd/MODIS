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
        Schema::table('transactions', function (Blueprint $table)
        {
            // Make the existing organization_id fields nullable
            $table->foreignId('from_organization_id')->nullable()->change();
            $table->foreignId('to_organization_id')->nullable()->change();

            // Add accounting fields
            $table->date('date')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('organization_id')->nullable()->constrained();
            $table->foreignId('created_by')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table)
        {
            // Make the organization_id fields required again
            $table->foreignId('from_organization_id')->change();
            $table->foreignId('to_organization_id')->change();

            // Remove accounting fields
            $table->dropColumn([
                'date',
                'reference',
                'description',
                'status',
                'organization_id',
                'created_by',
            ]);
        });
    }
};
