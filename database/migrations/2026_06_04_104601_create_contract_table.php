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
        Schema::create('contract', function (Blueprint $table) {
            $table->id("contract_id");
            $table->foreignId('room_id')->constrained('room', 'room_id')->on('room')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenant', 'tenant_id')->on('tenant')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('deposit_amount', 10, 2);
            $table->enum('status', ['active', 'terminated', 'expired'])->default('active');
            $table->text('notes')->nullable();
            $table->date('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract');
    }
};
