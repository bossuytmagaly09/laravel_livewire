<?php

namespace App\Actions\Tickets;

use App\Models\Ticket; // nodig om bestaand ticket te updaten

class UpdateTicketAction
{
    public function __construct(
        protected LogTicketActivityAction $logTicketActivityAction, // centrale logger
    ) {
    }

    public function execute(Ticket $ticket, array $data): Ticket
    {
        $oldSubject      = $ticket->subject;        // oude onderwerpwaarde bewaren
        $oldDescription  = $ticket->description;    // oude beschrijving bewaren
        $oldStatus       = $ticket->status;         // oude status bewaren
        $oldPriority     = $ticket->priority;       // oude prioriteit bewaren
        $oldWorkflow     = $ticket->workflow_step;  // oude workflow bewaren
        $oldAssigneeName = $ticket->assigneeName(); // oude assigneenaam bewaren

        $ticket->update([
            'subject'          => $data['subject'],      // nieuw onderwerp wegschrijven
            'description'      => $data['description'],  // nieuwe beschrijving wegschrijven
            'status'           => $data['status'],       // nieuwe status wegschrijven
            'priority'         => $data['priority'],     // nieuwe prioriteit wegschrijven
            'workflow_step'    => $data['workflow_step'], // nieuwe workflow wegschrijven
            'assigned_user_id' => $data['assigned_user_id'] !== '' ? $data['assigned_user_id'] : null, // lege string terug omzetten naar null
        ]);

        $ticket->refresh(); // herlaad ticket zodat enums en relaties up-to-date zijn

        if ($oldSubject !== $ticket->subject) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Onderwerp gewijzigd van '{$oldSubject}' naar '{$ticket->subject}'." // log onderwerpwijziging
            );
        }

        if ($oldDescription !== $ticket->description) {
            $this->logTicketActivityAction->execute(
                $ticket,
                'Beschrijving bijgewerkt.' // log beschrijvingswijziging zonder oude tekst te dumpen
            );
        }

        if ($oldStatus !== $ticket->status) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Status gewijzigd van {$oldStatus->value} naar {$ticket->status->value}." // log statuswijziging
            );
        }

        if ($oldPriority !== $ticket->priority) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Prioriteit gewijzigd van {$oldPriority->value} naar {$ticket->priority->value}." // log prioriteitswijziging
            );
        }

        if ($oldWorkflow !== $ticket->workflow_step) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Workflow gewijzigd van {$oldWorkflow->value} naar {$ticket->workflow_step->value}." // log workflowwijziging
            );
        }

        if ($oldAssigneeName !== $ticket->assigneeName()) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Toegewezen behandelaar gewijzigd van {$oldAssigneeName} naar {$ticket->assigneeName()}." // log assignmentwijziging
            );
        }

        return $ticket->fresh(['assignee']); // geef verse ticketversie terug
    }
}
