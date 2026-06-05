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
        Schema::create('room', function (Blueprint $table) {
            $table->id("room_id");
            $table->foreignId("building_id")->constrained("building", "building_id")->on('building')->onDelete('cascade');
            $table->string("room_number", 10);
            $table->enum("room_type", ["single", "double", "studio"]);
            $table->integer("floor_number");
            $table->decimal("monthly_price", 10, 2);
            $table->enum("status", ["available", "occupied", "maintenance"])->default("available");
            $table->decimal("area_sqm", 10, 2);
            $table->text("description")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room');
    }
};
