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
        Schema::create('tenant', function (Blueprint $table) {
            $table->id("tenant_id");
            $table->string("full_name");
            $table->string("phone");
            $table->string("email")->unique();
            $table->string("national_id")->unique();
            $table->enum("gender", ["male", "female", "other"]);
            $table->text("current_address");
            $table->date("move_in_date");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant');
    }
};
