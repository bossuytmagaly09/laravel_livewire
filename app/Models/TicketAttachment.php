<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    protected $fillable = [ // velden die via mass assignment ingevuld mogen worden
        'ticket_id', // gekoppeld ticket
        'original_name', // originele bestandsnaam
        'file_path', // pad van het bestand op disk
        'mime_type', // mime type van het bestand
        'file_size', // grootte van het bestand in bytes
    ];

    public function ticket(): BelongsTo // relatie naar het ticket
    {
        return $this->belongsTo(Ticket::class); // elk attachment hoort bij één ticket
    }

    public function url(): string // helper om de publieke URL van het bestand op te halen
    {
        return Storage::disk('public')->url($this->file_path); // maak publieke URL op de public disk
    }

    public function formattedFileSize(): string // helper om bestandsgrootte leesbaar te tonen
    {
        if (!$this->file_size) { // als er geen grootte bekend is
            return 'Onbekend'; // toon fallback
        }

        $bytes = $this->file_size; // sla ruwe grootte op in variabele

        if ($bytes < 1024) { // kleiner dan 1 KB
            return $bytes . ' B'; // toon in bytes
        }

        if ($bytes < 1024 * 1024) { // kleiner dan 1 MB
            return round($bytes / 1024, 2) . ' KB'; // toon in kilobytes
        }

        return round($bytes / 1024 / 1024, 2) . ' MB'; // toon in megabytes
    }

    public function isImage(): bool // helper om te checken of het bestand een afbeelding is
    {
        return str_starts_with($this->mime_type ?? '', 'image/'); // true als mime type met image/ start
    }
}
