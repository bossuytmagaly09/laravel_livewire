<?php // start van het PHP-bestand

use Illuminate\Database\Migrations\Migration; // basis migratieklasse
use Illuminate\Database\Schema\Blueprint; // nodig om kolommen te definiëren
use Illuminate\Support\Facades\Schema; // facade voor schema-acties

return new class extends Migration // anonieme migratieklasse
{
    public function up(): void // wordt uitgevoerd bij migrate
    {
        Schema::create('ticket_attachments', function (Blueprint $table) { //maak de tabel aan
            $table->id(); // primaire sleutel
            $table->foreignId('ticket_id') // foreign key naar tickets
                ->constrained() // verwijst standaard naar de tickets tabel
                ->cascadeOnDelete(); // verwijder attachments mee als ticket verwijderd wordt
            $table->string('original_name'); // originele bestandsnaam zoals gebruiker die uploadde
            $table->string('file_path'); // opslagpad op disk
            $table->string('mime_type')->nullable(); // mime type van het bestand
            $table->unsignedBigInteger('file_size')->nullable(); // bestandsgrootte in bytes
            $table->timestamps(); // created_at en updated_at
        });
    }
    public function down(): void // wordt uitgevoerd bij rollback
    {
        Schema::dropIfExists('ticket_attachments'); // verwijder de tabel opnieuw
    }
};
