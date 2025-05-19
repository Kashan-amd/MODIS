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
        Schema::create('job_bookings', function (Blueprint $table)
        {
            $table->id();
            $table->string('job_number')->unique();
            $table->string('campaign');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('sale_by');
            $table->string('po_number');
            $table->decimal('approved_budget', 12, 2)->nullable();
            $table->boolean('gst')->default(false);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_bookings');
    }
};
