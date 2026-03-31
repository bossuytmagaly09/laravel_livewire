<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // basis Eloquent model
use Illuminate\Database\Eloquent\Relations\BelongsTo; // nodig voor belongsTo relatie
use Illuminate\Database\Eloquent\Relations\HasMany; // nodig voor hasMany relaties

class Ticket extends Model
{
    protected $fillable = [
        'subject',          // onderwerp van het ticket
        'description',      // inhoud van het ticket
        'status',           // open, in_progress, closed
        'priority',         // low, medium, high
        'assigned_user_id', // NIEUW: toegewezen supportmedewerker
        'workflow_step',    // NIEUW: huidige workflowstap
        'attachment_path',  // oudere optionele uploadkolom uit eerdere modules
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class); // één ticket heeft meerdere comments
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class); // één ticket heeft meerdere attachments
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class); // één ticket heeft meerdere activity log regels
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id'); // NIEUW: toegewezen gebruiker van dit ticket
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open'        => 'Open',             // leesbaar label voor open
            'in_progress' => 'In behandeling',   // leesbaar label voor in behandeling
            'closed'      => 'Gesloten',         // leesbaar label voor gesloten
            default       => 'Onbekend',         // fallback
        };
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            'open'        => 'bg-blue-100 text-blue-700',     // badgekleur voor open
            'in_progress' => 'bg-yellow-100 text-yellow-700', // badgekleur voor in behandeling
            'closed'      => 'bg-green-100 text-green-700',   // badgekleur voor gesloten
            default       => 'bg-gray-100 text-gray-700',     // fallback badge
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'low'    => 'Laag',      // label voor lage prioriteit
            'medium' => 'Normaal',   // label voor normale prioriteit
            'high'   => 'Hoog',      // label voor hoge prioriteit
            default  => 'Onbekend',  // fallback
        };
    }

    public function priorityBadgeClasses(): string
    {
        return match ($this->priority) {
            'low'    => 'bg-gray-100 text-gray-700',     // badgekleur voor lage prioriteit
            'medium' => 'bg-orange-100 text-orange-700', // badgekleur voor normale prioriteit
            'high'   => 'bg-red-100 text-red-700',       // badgekleur voor hoge prioriteit
            default  => 'bg-gray-100 text-gray-700',     // fallback badge
        };
    }

    public function workflowLabel(): string
    {
        return match ($this->workflow_step) {
            'new'              => 'Nieuw',          // NIEUW: ticket is net aangemaakt
            'triage'           => 'Triage',         // NIEUW: ticket zit in eerste beoordeling
            'investigating'    => 'Onderzoek',      // NIEUW: ticket zit in analyse
            'waiting_customer' => 'Wacht op klant', // NIEUW: extra info van klant nodig
            'resolved'         => 'Opgelost',       // NIEUW: inhoudelijk opgelost
            default            => 'Onbekend',       // fallback
        };
    }

    public function workflowBadgeClasses(): string
    {
        return match ($this->workflow_step) {
            'new'              => 'bg-slate-100 text-slate-700',   // NIEUW: badgekleur voor new
            'triage'           => 'bg-indigo-100 text-indigo-700', // NIEUW: badgekleur voor triage
            'investigating'    => 'bg-purple-100 text-purple-700', // NIEUW: badgekleur voor investigating
            'waiting_customer' => 'bg-amber-100 text-amber-700',   // NIEUW: badgekleur voor waiting_customer
            'resolved'         => 'bg-emerald-100 text-emerald-700', // NIEUW: badgekleur voor resolved
            default            => 'bg-gray-100 text-gray-700',    // fallback badge
        };
    }

    public function assigneeName(): string
    {
        return $this->assignee?->name ?? 'Niet toegewezen'; // NIEUW: leesbare assigneenaam of fallback
    }

    public function isOpen(): bool
    {
        return $this->status === 'open'; // helper om te checken of ticket open staat
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed'; // helper om te checken of ticket gesloten staat
    }

    public function logActivity(string $description): void
    {
        $this->activities()->create([
            'event'       => 'ticket_event',    // activiteitstype
            'label'       => 'Ticket activiteit', // leesbaar label
            'description' => $description,      // activiteitstekst
        ]);
    }
}
