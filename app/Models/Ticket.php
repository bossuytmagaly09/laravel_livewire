<?php // start van het modelbestand

namespace App\Models;
// namespace van het model

use Illuminate\Database\Eloquent\Model;

// basis Eloquent model
use Illuminate\Database\Eloquent\Relations\HasMany;

// typehint voor hasMany relatie

class Ticket extends Model // Ticket model
{
    protected $fillable = [ // velden die via mass assignment ingevuld mogen worden
        'subject', // onderwerp van het ticket
        'description', // inhoud van het ticket
        'status', // open, in_progress, closed
        'priority', // low, medium, high
        'attachment_path', // optioneel uploadpad
        'content',
        'type',
    ];

    public function comments(): HasMany // relatie naar comments
    {
        return $this->hasMany(Comment::class); // één ticket heeft meerdere comments
    }

    public function statusLabel(): string // leesbaar label voor status
    {
        return match ($this->status) { // zet technische status om naar leesbare tekst
            'open' => 'Open', // label voor open tickets
            'in_progress' => 'In behandeling', // label voor tickets in behandeling
            'closed' => 'Gesloten', // label voor gesloten tickets
            default => 'Onbekend', // fallback
        };
    }

    public function statusBadgeClasses(): string // badgeclasses voor status
    {
        return match ($this->status) { // kies classes op basis van status
            'open' => 'bg-blue-100 text-blue-700', // blauw voor open
            'in_progress' => 'bg-yellow-100 text-yellow-700', // geel voor in behandeling
            'closed' => 'bg-green-100 text-green-700', // groen voor gesloten
            default => 'bg-gray-100 text-gray-700', // fallback badge
        };
    }

    public function priorityLabel(): string // leesbaar label voor prioriteit
    {
        return match ($this->priority) { // zet technische prioriteit om naar leesbare tekst
            'low' => 'Laag', // label voor lage prioriteit
            'medium' => 'Normaal', // label voor normale prioriteit
            'high' => 'Hoog', // label voor hoge prioriteit
            default => 'Onbekend', // fallback
        };
    }

    public function priorityBadgeClasses(): string // badgeclasses voor prioriteit
    {
        return match ($this->priority) { // kies classes op basis van prioriteit
            'low' => 'bg-gray-100 text-gray-700', // neutraal voor laag
            'medium' => 'bg-orange-100 text-orange-700', // oranje voor normaal
            'high' => 'bg-red-100 text-red-700', // rood voor hoog
            default => 'bg-gray-100 text-gray-700', // fallback badge
        };
    }

    public function isOpen(): bool // helper om te checken of ticket open is
    {
        return $this->status === 'open'; // true als status open is
    }

    public function isClosed(): bool // helper om te checken of ticket gesloten is
    {
        return $this->status === 'closed'; // true als status gesloten is
    }
}

