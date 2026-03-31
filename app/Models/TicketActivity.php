<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    protected $fillable = [
        'ticket_id', // gekoppeld ticket
        'event', // technische sleutel van de actie
        'label', // korte leesbare titel
        'description', // extra context over wat er precies veranderd is
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class); // elk activity item hoort bij één ticket
    }

    public function badgeClasses(): string // helper voor badgekleuren per eventtype
    {
        return match ($this->event) {
            'ticket_created' => 'bg-blue-100 text-blue-700', // blauw voor aanmaken
            'ticket_updated' => 'bg-indigo-100 text-indigo-700', // indigo voor wijzigen
            'comment_created' => 'bg-green-100 text-green-700', // groen voor nieuwe comment
            'comment_deleted' => 'bg-red-100 text-red-700', // rood voor verwijderde comment
            'attachment_created' => 'bg-purple-100 text-purple-700', // paars voor upload
            'attachment_deleted' => 'bg-orange-100 text-orange-700', // oranje voor verwijderd bestand
            default => 'bg-gray-100 text-gray-700', // fallback badge
        };
    }
}
