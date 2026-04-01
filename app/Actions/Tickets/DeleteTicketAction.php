<?php

namespace App\Actions\Tickets;

use App\Models\Ticket; // nodig om ticket te verwijderen

class DeleteTicketAction
{
    public function execute(Ticket $ticket): void
    {
        $ticket->delete(); // verwijder ticket uit database
    }
}
