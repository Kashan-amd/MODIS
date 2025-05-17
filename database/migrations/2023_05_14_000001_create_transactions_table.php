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
        Schema::create('transactions', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('from_organization_id')->constrained('organizations');
            $table->foreignId('to_organization_id')->constrained('organizations');
            $table->decimal('amount', 10, 2);
            // Using string instead of ENUM for SQLite compatibility
            $table->string('transaction_type');
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
