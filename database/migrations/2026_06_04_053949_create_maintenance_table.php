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
        Schema::create('maintenance', function (Blueprint $table) {
            $table->id("maintenance_id");
            $table->foreignId("room_id")->constrained("room", "room_id")->on('room')->onDelete('cascade');
            $table->string("issue_type");
            $table->text("description")->nullable();
            $table->date("reported_date");
            $table->date("resolved_date")->nullable();
            $table->enum("status", ["open", "in_progress", "close"])->default("open");
            $table->decimal("repair_cost", 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance');
    }
};
