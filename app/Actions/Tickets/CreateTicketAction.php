<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;          // nodig om ticket aan te maken
use App\TicketWorkflowStep;     // nodig voor standaard workflow

class CreateTicketAction
{
    public function __construct(
        protected LogTicketActivityAction $logTicketActivityAction, // centrale logger
    ) {
    }

    public function execute(array $data): Ticket
    {
        $ticket = Ticket::create([
            'subject'          => $data['subject'],      // onderwerp uit gevalideerde input
            'description'      => $data['description'],  // beschrijving uit gevalideerde input
            'priority'         => $data['priority'],     // prioriteit uit gevalideerde input
            'status'           => $data['status'],       // status uit gevalideerde input
            'assigned_user_id' => null,                  // nieuwe tickets starten zonder assignee
            'workflow_step'    => TicketWorkflowStep::New, // nieuwe tickets starten op workflow new
        ]);

        $this->logTicketActivityAction->execute(
            $ticket,
            'Ticket aangemaakt.' // log ticketcreatie centraal
        );

        return $ticket->fresh(['assignee']); // geef verse ticketversie terug
    }
}
