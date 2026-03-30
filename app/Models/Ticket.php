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
}
