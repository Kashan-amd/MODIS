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
        Schema::create('clients', function (Blueprint $table)
        {
            $table->id();
            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('address')->nullable();
            $table->string('bm_official')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
