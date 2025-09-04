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
        Schema::create('chart_of_accounts', function (Blueprint $table)
        {
            $table->id();
            $table->string('account_number')->unique();
            $table->string('name');
            $table->string('type'); // asset, liability, equity, income, expense
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->decimal('debit_balance', 15, 2)->default(0.00);
            $table->decimal('credit_balance', 15, 2)->default(0.00);
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->date('balance_date')->nullable();

            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(0); // Hierarchy level
            $table->boolean('is_parent')->default(false); // Indicates if this account is a parent account

            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
