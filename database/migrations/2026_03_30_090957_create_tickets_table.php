<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('subject'); // kort onderwerp van het ticket
            $table->text('description'); // beschrijving van het probleem
            $table->string('status')->default('open'); // status van het ticket

            $table->string('priority')->default('medium'); // prioriteit van het ticket
            $table->string('attachment_path')->nullable(); // voor latere uploadmodule
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
