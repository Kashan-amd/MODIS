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
        Schema::create('petty_cash', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();

            $table->decimal('balance', 15, 2)->default(0.00)->comment('Current balance of the petty cash account');
            $table->decimal('debit', 15, 2)->default(0.00)->comment('Total debit amount for the petty cash transaction');
            $table->decimal('credit', 15, 2)->default(0.00)->comment('Total credit amount for the petty cash transaction');

            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->date('transaction_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash');
    }
};
