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
        Schema::create('match_records', function (Blueprint $table) {
            $table->id();
            $table->integer('match_id');
            $table->date('date');
            $table->integer('court')->nullable();
            $table->json('players'); // store player IDs or names
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_records');
    }
};
