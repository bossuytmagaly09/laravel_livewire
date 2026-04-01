<?php

namespace App\Models;

use App\Actions\Tickets\LogTicketActivityAction; // compatibiliteitsbrug naar centrale activity logger
use App\TicketPriority;                          // enum voor prioriteit
use App\TicketStatus;                            // enum voor status
use App\TicketWorkflowStep;                      // enum voor workflow
use Illuminate\Database\Eloquent\Model;          // basis Eloquent model
use Illuminate\Database\Eloquent\Relations\BelongsTo; // relatie naar assignee
use Illuminate\Database\Eloquent\Relations\HasMany;   // relaties naar child records

class Ticket extends Model
{
    protected $fillable = [
        'subject',          // onderwerp van het ticket
        'description',      // beschrijving van het ticket
        'status',           // status van het ticket
        'priority',         // prioriteit van het ticket
        'assigned_user_id', // optionele toegewezen supportmedewerker
        'workflow_step',    // workflowstap van het ticket
        'attachment_path',  // oudere optionele uploadkolom uit eerdere modules
    ];

    protected function casts(): array
    {
        return [
            'status'        => TicketStatus::class,       // cast status naar enum
            'priority'      => TicketPriority::class,     // cast prioriteit naar enum
            'workflow_step' => TicketWorkflowStep::class, // cast workflow naar enum
        ];
    }

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
        return $this->belongsTo(User::class, 'assigned_user_id'); // optionele toegewezen behandelaar
    }

    public function statusLabel(): string
    {
        return $this->status->label(); // leesbaar statuslabel via enum
    }

    public function statusBadgeClasses(): string
    {
        return $this->status->badgeClasses(); // badge classes via enum
    }

    public function priorityLabel(): string
    {
        return $this->priority->label(); // leesbaar prioriteitslabel via enum
    }

    public function priorityBadgeClasses(): string
    {
        return $this->priority->badgeClasses(); // badge classes via enum
    }

    public function workflowLabel(): string
    {
        return $this->workflow_step->label(); // leesbaar workflowlabel via enum
    }

    public function workflowBadgeClasses(): string
    {
        return $this->workflow_step->badgeClasses(); // badge classes via enum
    }

    public function assigneeName(): string
    {
        return $this->assignee?->name ?? 'Niet toegewezen'; // leesbare assigneenaam of fallback
    }

    public function isOpen(): bool
    {
        return $this->status === TicketStatus::Open; // helper om te checken of ticket open is
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::Closed; // helper om te checken of ticket gesloten is
    }

    public function logActivity(
        string $eventOrDescription,
        ?string $label = null,
        ?string $description = null,
    ): void {
        // OUDE stijl 1:
        // $ticket->logActivity('Ticket aangemaakt.')
        if ($label === null && $description === null) {
            app(LogTicketActivityAction::class)->execute(
                $this,
                $eventOrDescription, // beschrijving
                'ticket_event',      // standaard technisch event
                'Ticket activiteit', // standaard leesbaar label
            );

            return;
        }

        // OUDE stijl 2:
        // $ticket->logActivity('comment_created', 'Comment toegevoegd', '...')
        app(LogTicketActivityAction::class)->execute(
            $this,
            $description ?? '',              // uiteindelijke beschrijving
            $eventOrDescription,             // technisch event
            $label ?? 'Ticket activiteit',   // leesbaar label
        );
    }
}
