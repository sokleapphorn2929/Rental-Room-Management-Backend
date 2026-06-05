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
        Schema::create('amenity', function (Blueprint $table) {
            $table->id("amenity_id");
            $table->foreignId('room_id')->constrained('room', 'room_id')->on('room')->onDelete('cascade');
            $table->string('amenity_name');
            $table->text('note')->nullable();
            $table->date('added_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenity');
    }
};
