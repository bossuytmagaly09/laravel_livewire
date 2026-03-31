<?php // start van het modelbestand

namespace App\Models;
// namespace van het model

use Illuminate\Database\Eloquent\Model;

// basis Eloquent model
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// typehint voor belongsTo relatie
class Comment extends Model // Comment model
{
    protected $fillable = [ // velden die via mass assignment ingevuld mogen worden
        'ticket_id', // gekoppeld ticket
        'content', // tekst van de reactie of notitie
        'type', // comment of note
    ];

    public function ticket(): BelongsTo // relatie naar het ticket
    {
        return $this->belongsTo(Ticket::class); // elk comment hoort bij één ticket
    }

    public function isNote(): bool // helper om te checken of dit een interne notitie is
    {
        return $this->type === 'note'; // true als type note is
    }

    public function typeLabel(): string // leesbaar label voor het type
    {
        return match ($this->type) { // zet technische waarde om naar leesbare tekst
            'comment' => 'Comment', // label voor gewone reactie
            'note' => 'Interne notitie', // label voor interne notitie
            default => 'Onbekend', // fallback voor onverwachte waarde
        };
    }

    public function typeBadgeClasses(): string // helper voor badgekleuren
    {
        return match ($this->type) { // kies classes op basis van type
            'comment' => 'bg-blue-100 text-blue-700', // blauwe badge voor gewone comment
            'note' => 'bg-yellow-100 text-yellow-700', // gele badge voor interne notitie
            default => 'bg-gray-100 text-gray-700', // fallback badge
        };
    }

}
