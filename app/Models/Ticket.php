<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'subject', // onderwerp van het ticket
        'description', // inhoud van het ticket
        'status', // open, in_progress, closed
        'priority', // low, medium, high
        'attachment_path', // optioneel uploadpad
    ];

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => 'Open', // label voor open tickets
            'in_progress' => 'In behandeling', // label voor tickets die bezig zijn
            'closed' => 'Gesloten', // label voor afgewerkte tickets

            default => 'Onbekend', // fallback voor onverwachte waarden
        };
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            'open' => 'bg-blue-100 text-blue-700', // blauwe badge voor open
            'in_progress' => 'bg-yellow-100 text-yellow-700', // gele badge voor in behandeling
            'closed' => 'bg-green-100 text-green-700', // groene badge voor gesloten
            default => 'bg-gray-100 text-gray-700', // neutrale fallback badge
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'low' => 'Laag', // label voor lage prioriteit
            'medium' => 'Normaal', // label voor normale prioriteit
            'high' => 'Hoog', // label voor hoge prioriteit
            default => 'Onbekend', // fallback voor onverwachte waarden
        };
    }
    public function priorityBadgeClasses(): string
    {
        return match ($this->priority) {
            'low' => 'bg-gray-100 text-gray-700', // neutrale badge voor lage prioriteit
            'medium' => 'bg-orange-100 text-orange-700', // oranje badge voor normale prioriteit
            'high' => 'bg-red-100 text-red-700', // rode badge voor hoge prioriteit

            default => 'bg-gray-100 text-gray-700', // fallback badge
        };
    }

    public function isOpen(): bool
    {
        return $this->status === 'open'; // helper om snel te checken of ticket open staat
    }
    public function isClosed(): bool
    {
        return $this->status === 'closed'; // helper om snel te checken of ticket gesloten is
    }
}
