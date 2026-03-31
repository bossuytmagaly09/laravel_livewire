<?php

use Illuminate\Database\Migrations\Migration; // basis migratieklasse
use Illuminate\Database\Schema\Blueprint; // nodig om kolommen toe te voegen
use Illuminate\Support\Facades\Schema; // schema facade

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('assigned_user_id') // //NIEUW: optioneletoegewezen supportmedewerker
                ->nullable() // //NIEUW: ticket mag nog niet toegewezen zijn
                ->after('priority') // //NIEUW: logisch na priority plaatsen
                ->constrained('users') // //NIEUW: foreign key naar users tabel
                ->nullOnDelete(); // //NIEUW: als user verdwijnt, assignment op null zetten

            $table->string('workflow_step') // //NIEUW: aparte workflowstap naast status
                ->default('new') // //NIEUW: nieuwe tickets starten standaard als new
                ->after('assigned_user_id'); // //NIEUW: logisch na assigned user plaatsen
        });
    }
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_user_id'); // //NIEUW: verwijder foreign key + kolom
            $table->dropColumn('workflow_step'); // //NIEUW: verwijder workflow kolom
        });
    }
};
