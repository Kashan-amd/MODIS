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
        Schema::create('client_accounts', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('account_number');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->timestamps();

            // Foreign key for account_number
            $table->foreign('account_number')->references('account_number')->on('chart_of_accounts')->onDelete('cascade');

            // Unique constraint to prevent duplicate client-account-organization combinations
            $table->unique(['client_id', 'account_number', 'organization_id']);

            // Index for better query performance
            $table->index(['client_id', 'organization_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_accounts');
    }
};
