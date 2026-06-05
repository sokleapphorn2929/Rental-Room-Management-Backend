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
        Schema::create('invoice', function (Blueprint $table) {
            $table->id("invoice_id");
            $table->foreignId("contract_id")->constrained("contract", "contract_id")->on("contract")->onDelete("cascade");
            $table->date("billing_month");
            $table->decimal("room_charge", 10, 2);
            $table->decimal("electricity_charge", 10, 2);
            $table->decimal("water_charge", 10, 2);
            $table->decimal("total_amount", 10, 2);
            $table->enum("status", ["pending", "paid", "overdue"])->default("pending");
            $table->date("issue_date");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice');
    }
};
