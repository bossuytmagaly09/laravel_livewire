<?php // start van het PHP-bestand

use Illuminate\Database\Migrations\Migration; // basis migratieklasse
use Illuminate\Database\Schema\Blueprint; // nodig om kolommen te definiëren
use Illuminate\Support\Facades\Schema; // facade voor schema-acties

return new class extends Migration // anonieme migratieklasse
{
    public function up(): void // wordt uitgevoerd bij migrate
    {
        Schema::create('comments', function (Blueprint $table) { // maak de comments tabel aan
            $table->id(); // primaire sleutel
            $table->foreignId('ticket_id') // foreign key naar tickets
                ->constrained() // verwijst standaard naar de tickets tabel
                ->cascadeOnDelete(); // verwijder comments mee als ticket verwijderd wordt
            $table->text('content'); // inhoud van de comment of interne notitie
            $table->string('type')->default('comment'); // type: comment of note
            $table->timestamps(); // created_at en updated_at
        });
    }

    public function down(): void // wordt uitgevoerd bij rollback
    {
        Schema::dropIfExists('comments'); // verwijder de comments tabel opnieuw
    }
};
