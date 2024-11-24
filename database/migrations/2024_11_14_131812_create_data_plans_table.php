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
        Schema::create('data_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('price');
            $table->foreignId('operator_card_id')->constrained('operator_cards');
            $table->timestamps();

            $table->foreign('operator_card_id')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_plans');
    }
};
