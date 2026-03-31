<?php

use App\Models\Ticket; // nodig om tickets op te halen, updaten en verwijderen
use App\Models\User;   // NIEUW: nodig om mogelijke assignees op te halen
use Livewire\Attributes\Computed; // nodig om computed properties te gebruiken
use Livewire\Attributes\Layout;   // om de page aan de layout te koppelen
use Livewire\Attributes\Url;      // om filters in de URL te bewaren
use Livewire\Component;           // basis Livewire component
use Livewire\WithPagination;      // nodig voor Livewire pagination

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = ''; // zoekterm voor onderwerp en beschrijving

    #[Url]
    public string $status = ''; // filter op status

    #[Url]
    public string $priority = ''; // filter op prioriteit

    #[Url]
    public string $workflow = ''; // NIEUW: filter op workflowstap

    #[Url(as: 'assignee')]
    public string $assignedUser = ''; // NIEUW: filter op toegewezen gebruiker

    #[Url(as: 'sort')]
    public string $sortField = 'created_at'; // standaard sorteren op aanmaakdatum

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc'; // nieuwste tickets eerst tonen

    public int $perPage = 10;   // aantal records per pagina
    public array $selected = []; // ids van geselecteerde tickets voor bulk-acties
    public ?int $editingId = null; // welk ticket momenteel inline bewerkt wordt

    public string $editSubject = '';          // tijdelijke onderwerpwaarde tijdens inline edit
    public string $editStatus = 'open';       // tijdelijke statuswaarde tijdens inline edit
    public string $editPriority = 'medium';   // tijdelijke prioriteitswaarde tijdens inline edit
    public string $editWorkflow = 'new';      // NIEUW: tijdelijke workflowwaarde tijdens inline edit
    public string $editAssignedUser = '';     // NIEUW: tijdelijke assignee tijdens inline edit

    public function updatingSearch(): void
    {
        $this->resetPage(); // ga terug naar pagina 1 zodra de zoekterm verandert
    }

    public function updatingStatus(): void
    {
        $this->resetPage(); // ga terug naar pagina 1 zodra de statusfilter verandert
    }

    public function updatingPriority(): void
    {
        $this->resetPage(); // ga terug naar pagina 1 zodra de prioriteitsfilter verandert
    }

    public function updatingWorkflow(): void
    {
        $this->resetPage(); // NIEUW: ga terug naar pagina 1 zodra workflowfilter verandert
    }

    public function updatingAssignedUser(): void
    {
        $this->resetPage(); // NIEUW: ga terug naar pagina 1 zodra assigneefilter verandert
    }

    public function updatingPerPage(): void
    {
        $this->resetPage(); // ga terug naar pagina 1 zodra het aantal per pagina verandert
    }

    public function sortBy(string $field): void
    {
        $allowedFields = ['id', 'subject', 'status', 'priority', 'workflow_step', 'created_at']; // NIEUW: workflow_step toegevoegd

        if (! in_array($field, $allowedFields, true)) {
            return; // stop als iemand een ongeldig sorteerveld probeert door te geven
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; // draai de sorteerrichting om op dezelfde kolom
        } else {
            $this->sortField = $field;       // stel een nieuw sorteerveld in
            $this->sortDirection = 'asc';    // start nieuwe kolom standaard ascendant
        }

        $this->resetPage(); // zet pagination terug naar pagina 1 na sorteerwijziging
    }

    public function clearFilters(): void
    {
        $this->search = '';              // wis zoekterm
        $this->status = '';              // wis statusfilter
        $this->priority = '';            // wis prioriteitsfilter
        $this->workflow = '';            // NIEUW: wis workflowfilter
        $this->assignedUser = '';        // NIEUW: wis assigneefilter
        $this->sortField = 'created_at'; // herstel standaardsortering
        $this->sortDirection = 'desc';   // herstel standaard sorteerrichting
        $this->perPage = 10;             // herstel standaard aantal per pagina
        $this->resetPage();              // ga terug naar pagina 1
    }

    public function startEdit(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId); // haal het ticket op dat we inline willen bewerken

        if (! $ticket) {
            return; // stop als het ticket niet bestaat
        }

        $this->editingId       = $ticket->id;       // zet deze rij in edit mode
        $this->editSubject     = $ticket->subject;   // vul tijdelijk onderwerp in
        $this->editStatus      = $ticket->status;    // vul tijdelijke status in
        $this->editPriority    = $ticket->priority;  // vul tijdelijke prioriteit in
        $this->editWorkflow    = $ticket->workflow_step; // NIEUW: vul tijdelijke workflow in
        $this->editAssignedUser = $ticket->assigned_user_id ? (string) $ticket->assigned_user_id : ''; // NIEUW: vul tijdelijke assignee in
    }

    public function cancelEdit(): void
    {
        $this->editingId        = null;     // verlaat edit mode
        $this->editSubject      = '';       // reset tijdelijk onderwerp
        $this->editStatus       = 'open';   // reset tijdelijke status
        $this->editPriority     = 'medium'; // reset tijdelijke prioriteit
        $this->editWorkflow     = 'new';    // NIEUW: reset tijdelijke workflow
        $this->editAssignedUser = '';       // NIEUW: reset tijdelijke assignee
    }

    public function saveInline(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId); // haal het ticket op dat inline bewerkt wordt

        if (! $ticket) {
            return; // stop als het ticket niet bestaat
        }

        $validated = $this->validate(
            [
                'editSubject'      => 'required|min:3|max:255',                                                    // onderwerp moet geldig zijn
                'editStatus'       => 'required|in:open,in_progress,closed',                                        // alleen geldige statussen
                'editPriority'     => 'required|in:low,medium,high',                                               // alleen geldige prioriteiten
                'editWorkflow'     => 'required|in:new,triage,investigating,waiting_customer,resolved',             // NIEUW: alleen geldige workflowstappen
                'editAssignedUser' => 'nullable|exists:users,id',                                                   // NIEUW: assignment moet naar bestaande user verwijzen
            ],
            [
                'editSubject.required'      => 'Het onderwerp is verplicht.',                       // foutmelding voor leeg onderwerp
                'editSubject.min'           => 'Het onderwerp moet minstens 3 tekens bevatten.',    // foutmelding voor te kort onderwerp
                'editSubject.max'           => 'Het onderwerp mag maximaal 255 tekens bevatten.',   // foutmelding voor te lang onderwerp
                'editStatus.required'       => 'Kies een status.',                                  // foutmelding voor ontbrekende status
                'editStatus.in'             => 'De gekozen status is ongeldig.',                    // foutmelding voor ongeldige status
                'editPriority.required'     => 'Kies een prioriteit.',                              // foutmelding voor ontbrekende prioriteit
                'editPriority.in'           => 'De gekozen prioriteit is ongeldig.',                // foutmelding voor ongeldige prioriteit
                'editWorkflow.required'     => 'Kies een workflow.',                                // NIEUW: foutmelding voor ontbrekende workflow
                'editWorkflow.in'           => 'De gekozen workflow is ongeldig.',                  // NIEUW: foutmelding voor ongeldige workflow
                'editAssignedUser.exists'   => 'De gekozen behandelaar bestaat niet.',             // NIEUW: foutmelding voor ongeldige assignee
            ]
        );

        $oldWorkflow = $ticket->workflow_step;  // NIEUW: oude workflow bewaren voor logging
        $oldAssignee = $ticket->assigneeName(); // NIEUW: oude assignee bewaren voor logging

        $ticket->update([
            'subject'          => $validated['editSubject'],   // schrijf nieuw onderwerp weg
            'status'           => $validated['editStatus'],    // schrijf nieuwe status weg
            'priority'         => $validated['editPriority'],  // schrijf nieuwe prioriteit weg
            'workflow_step'    => $validated['editWorkflow'],  // NIEUW: schrijf nieuwe workflow weg
            'assigned_user_id' => $validated['editAssignedUser'] !== '' ? $validated['editAssignedUser'] : null, // NIEUW: zet lege string om naar null
        ]);

        if ($oldWorkflow !== $ticket->workflow_step) {
            $ticket->logActivity("Workflow inline gewijzigd van {$oldWorkflow} naar {$ticket->workflow_step}."); // NIEUW: log workflowwijziging
        }

        if ($oldAssignee !== $ticket->assigneeName()) {
            $ticket->logActivity("Behandelaar inline gewijzigd van {$oldAssignee} naar {$ticket->assigneeName()}."); // NIEUW: log assignmentwijziging
        }

        $this->cancelEdit(); // sluit edit mode opnieuw af
        session()->flash('success', 'Het ticket werd inline bijgewerkt.'); // toon succesfeedback
    }

    public function changeStatus(int $ticketId, string $status): void
    {
        $allowedStatuses = ['open', 'in_progress', 'closed']; // whitelist van geldige statussen

        if (! in_array($status, $allowedStatuses, true)) {
            return; // stop als de status ongeldig is
        }

        $ticket = Ticket::find($ticketId); // zoek het ticket op basis van zijn id

        if (! $ticket) {
            return; // stop als het ticket niet meer bestaat
        }

        $ticket->update([
            'status' => $status, // schrijf de nieuwe status weg
        ]);

        $ticket->logActivity("Status snel gewijzigd naar {$status}."); // NIEUW: log snelle statuswijziging
        session()->flash('success', 'De status van het ticket werd succesvol aangepast.'); // toon feedback bovenaan
    }

    public function delete(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId); // zoek het ticket op basis van zijn id

        if (! $ticket) {
            return; // stop als het ticket niet bestaat
        }

        $ticket->delete(); // verwijder het ticket uit de database

        $this->selected = array_values(array_filter(
            $this->selected,
            fn ($id) => (int) $id !== $ticketId // haal verwijderd ticket ook uit bulkselectie
        ));

        if ($this->editingId === $ticketId) {
            $this->cancelEdit(); // sluit edit mode als net die rij verwijderd werd
        }

        $this->resetPage(); // blijf op een geldige pagina na delete
        session()->flash('success', 'Het ticket werd succesvol verwijderd.'); // toon feedback bovenaan
    }

    public function bulkClose(): void
    {
        if (empty($this->selected)) {
            return; // stop als er niets geselecteerd is
        }

        Ticket::query()
            ->whereIn('id', $this->selected) // neem alleen geselecteerde tickets
            ->update([
                'status' => 'closed', // zet alle geselecteerde tickets op gesloten
            ]);

        $count = count($this->selected); // tel hoeveel records aangepast werden
        $this->selected = []; // maak selectie leeg na bulk-actie
        session()->flash('success', "{$count} ticket(s) werden op gesloten gezet."); // toon succesfeedback
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            return; // stop als er niets geselecteerd is
        }

        Ticket::query()
            ->whereIn('id', $this->selected) // neem alleen geselecteerde tickets
            ->delete(); // verwijder alle geselecteerde tickets

        $count = count($this->selected); // tel hoeveel records verwijderd werden
        $this->selected = []; // reset selectie na bulk delete

        if ($this->editingId !== null) {
            $this->cancelEdit(); // sluit eventuele open edit mode
        }

        $this->resetPage(); // zorg dat pagination geldig blijft
        session()->flash('success', "{$count} ticket(s) werden verwijderd."); // toon succesfeedback
    }

    public function selectCurrentPage(): void
    {
        $this->selected = $this->tickets->pluck('id')->map(fn ($id) => (string) $id)->toArray(); // selecteer alle tickets van huidige pagina
    }

    public function clearSelection(): void
    {
        $this->selected = []; // wis alle geselecteerde ids
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        $count = 0; // start teller op nul

        if ($this->search !== '')       { $count++; } // tel zoekterm mee
        if ($this->status !== '')       { $count++; } // tel statusfilter mee
        if ($this->priority !== '')     { $count++; } // tel prioriteitsfilter mee
        if ($this->workflow !== '')     { $count++; } // NIEUW: tel workflowfilter mee
        if ($this->assignedUser !== '') { $count++; } // NIEUW: tel assigneefilter mee

        return $count; // geef aantal actieve filters terug
    }

    #[Computed]
    public function assignees()
    {
        return User::query()
            ->orderBy('name') // NIEUW: alfabetisch sorteren
            ->get();          // NIEUW: mogelijke assignees ophalen
    }

    #[Computed]
    public function tickets()
    {
        return Ticket::query()
            ->with('assignee') // NIEUW: eager load toegewezen gebruiker
            ->when($this->search !== '', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('subject', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%'); // zoek in onderwerp of beschrijving
                });
            })
            ->when($this->status !== '', function ($query) {
                $query->where('status', $this->status); // filter op gekozen status
            })
            ->when($this->priority !== '', function ($query) {
                $query->where('priority', $this->priority); // filter op gekozen prioriteit
            })
            ->when($this->workflow !== '', function ($query) {
                $query->where('workflow_step', $this->workflow); // NIEUW: filter op workflow
            })
            ->when($this->assignedUser !== '', function ($query) {
                $query->where('assigned_user_id', $this->assignedUser); // NIEUW: filter op assignee
            })
            ->orderBy($this->sortField, $this->sortDirection) // pas sortering toe
            ->paginate($this->perPage); // geef paginated resultaat terug
    }
};
?>

<div class="min-h-screen bg-gray-100 py-10">
    <div class="mx-auto max-w-7xl px-4">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Tickets overzicht
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Beheer support tickets rechtstreeks vanuit één interactieve Livewire werkpagina.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                >
                    Filters resetten
                </button>
                <a
                    href="{{ route('tickets.create') }}"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >
                    Nieuw ticket
                </a>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="grid gap-4 md:grid-cols-4 xl:grid-cols-6">
                <div>
                    <label for="search" class="mb-2 block text-sm font-medium text-gray-700">
                        Zoeken
                    </label>
                    <input
                        id="search"
                        type="text"
                        wire:model.live="search"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Zoek op onderwerp of beschrijving"
                    >
                </div>

                <div>
                    <label for="status" class="mb-2 block text-sm font-medium text-gray-700">
                        Status
                    </label>
                    <select
                        id="status"
                        wire:model.live="status"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Alle statussen</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In behandeling</option>
                        <option value="closed">Gesloten</option>
                    </select>
                </div>

                <div>
                    <label for="priority" class="mb-2 block text-sm font-medium text-gray-700">
                        Prioriteit
                    </label>
                    <select
                        id="priority"
                        wire:model.live="priority"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Alle prioriteiten</option>
                        <option value="low">Laag</option>
                        <option value="medium">Normaal</option>
                        <option value="high">Hoog</option>
                    </select>
                </div>

                <div>
                    <label for="workflow" class="mb-2 block text-sm font-medium text-gray-700">
                        Workflow
                    </label>
                    <select
                        id="workflow"
                        wire:model.live="workflow"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Alle stappen</option>
                        <option value="new">Nieuw</option>
                        <option value="triage">Triage</option>
                        <option value="investigating">Onderzoek</option>
                        <option value="waiting_customer">Wacht op klant</option>
                        <option value="resolved">Opgelost</option>
                    </select>
                </div>

                <div>
                    <label for="assignedUser" class="mb-2 block text-sm font-medium text-gray-700">
                        Toegewezen aan
                    </label>
                    <select
                        id="assignedUser"
                        wire:model.live="assignedUser"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Iedereen</option>
                        @foreach ($this->assignees as $assignee)
                            <option value="{{ $assignee->id }}">
                                {{ $assignee->name }} {{-- NIEUW: mogelijke behandelaar --}}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="perPage" class="mb-2 block text-sm font-medium text-gray-700">
                        Per pagina
                    </label>
                    <select
                        id="perPage"
                        wire:model.live="perPage"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-600">
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700">
                    Actieve filters: {{ $this->activeFilterCount }}
                </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700">
                    Geselecteerd: {{ count($selected) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700">
                    Totaal op deze pagina: {{ $this->tickets->count() }}
                </span>
            </div>
        </div>

        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">
                        Bulk-acties
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Selecteer meerdere tickets en voer één actie uit op de hele selectie.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        wire:click="selectCurrentPage"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        Selecteer huidige pagina
                    </button>
                    <button
                        type="button"
                        wire:click="clearSelection"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        Wis selectie
                    </button>
                    <button
                        type="button"
                        wire:click="bulkClose"
                        wire:loading.attr="disabled"
                        wire:target="bulkClose"
                        class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Zet selectie op gesloten
                    </button>
                    <button
                        type="button"
                        wire:click="bulkDelete"
                        wire:confirm="Weet je zeker dat je alle geselecteerde tickets wilt verwijderen?"
                        wire:loading.attr="disabled"
                        wire:target="bulkDelete"
                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Verwijder selectie
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Selectie
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('id')" class="inline-flex items-center gap-2">
                                ID
                                @if ($sortField === 'id')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('subject')" class="inline-flex items-center gap-2">
                                Onderwerp
                                @if ($sortField === 'subject')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('status')" class="inline-flex items-center gap-2">
                                Status
                                @if ($sortField === 'status')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('priority')" class="inline-flex items-center gap-2">
                                Prioriteit
                                @if ($sortField === 'priority')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('workflow_step')" class="inline-flex items-center gap-2">
                                Workflow
                                @if ($sortField === 'workflow_step')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Toegewezen aan
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('created_at')" class="inline-flex items-center gap-2">
                                Aangemaakt op
                                @if ($sortField === 'created_at')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Snelle acties
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Beheer
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($this->tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}" class="align-top hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    value="{{ $ticket->id }}"
                                    wire:model.live="selected"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                #{{ $ticket->id }}
                            </td>

                            <td class="px-6 py-4">
                                @if ($editingId === $ticket->id)
                                    <div class="space-y-3">
                                        <div>
                                            <input
                                                type="text"
                                                wire:model="editSubject"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                            @error('editSubject')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <select wire:model="editStatus" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="open">Open</option>
                                                    <option value="in_progress">In behandeling</option>
                                                    <option value="closed">Gesloten</option>
                                                </select>
                                                @error('editStatus')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <select wire:model="editPriority" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="low">Laag</option>
                                                    <option value="medium">Normaal</option>
                                                    <option value="high">Hoog</option>
                                                </select>
                                                @error('editPriority')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <select wire:model="editWorkflow" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="new">Nieuw</option>
                                                    <option value="triage">Triage</option>
                                                    <option value="investigating">Onderzoek</option>
                                                    <option value="waiting_customer">Wacht op klant</option>
                                                    <option value="resolved">Opgelost</option>
                                                </select>
                                                @error('editWorkflow')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <select wire:model="editAssignedUser" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="">Niet toegewezen</option>
                                                    @foreach ($this->assignees as $assignee)
                                                        <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('editAssignedUser')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <button
                                                type="button"
                                                wire:click="saveInline({{ $ticket->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="saveInline({{ $ticket->id }})"
                                                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Opslaan
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="cancelEdit"
                                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                                            >
                                                Annuleren
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-sm font-semibold text-gray-900">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="transition hover:text-blue-600 hover:underline">
                                            {{ $ticket->subject }}
                                        </a>
                                    </div>
                                    <div class="mt-1 line-clamp-2 text-sm text-gray-500">
                                        {{ $ticket->description }}
                                    </div>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($editingId === $ticket->id)
                                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                            Inline edit actief
                                        </span>
                                @else
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->statusBadgeClasses() }}">
                                            {{ $ticket->statusLabel() }}
                                        </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($editingId === $ticket->id)
                                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                            Bewerken
                                        </span>
                                @else
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->priorityBadgeClasses() }}">
                                            {{ $ticket->priorityLabel() }}
                                        </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($editingId === $ticket->id)
                                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                            Inline edit actief
                                        </span>
                                @else
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->workflowBadgeClasses() }}">
                                            {{ $ticket->workflowLabel() }} {{-- NIEUW: workflowbadge --}}
                                        </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                @if ($editingId === $ticket->id)
                                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                            {{ $editAssignedUser !== '' ? 'Assignee gekozen' : 'Niet toegewezen' }} {{-- NIEUW: tijdelijke assignee feedback --}}
                                        </span>
                                @else
                                    {{ $ticket->assigneeName() }} {{-- NIEUW: toegewezen medewerker --}}
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        wire:click="changeStatus({{ $ticket->id }}, 'open')"
                                        wire:loading.attr="disabled"
                                        wire:target="changeStatus({{ $ticket->id }}, 'open')"
                                        class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Open
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="changeStatus({{ $ticket->id }}, 'in_progress')"
                                        wire:loading.attr="disabled"
                                        wire:target="changeStatus({{ $ticket->id }}, 'in_progress')"
                                        class="inline-flex items-center rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs font-medium text-yellow-700 transition hover:bg-yellow-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        In behandeling
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="changeStatus({{ $ticket->id }}, 'closed')"
                                        wire:loading.attr="disabled"
                                        wire:target="changeStatus({{ $ticket->id }}, 'closed')"
                                        class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700 transition hover:bg-green-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Gesloten
                                    </button>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-3">
                                    @if ($editingId === $ticket->id)
                                        <button
                                            type="button"
                                            wire:click="cancelEdit"
                                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                                        >
                                            Stop edit
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="startEdit({{ $ticket->id }})"
                                            class="inline-flex items-center rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-700 shadow-sm transition hover:bg-indigo-100"
                                        >
                                            Inline edit
                                        </button>
                                    @endif
                                    <a
                                        href="{{ route('tickets.show', $ticket) }}"
                                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                                    >
                                        Openen
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="delete({{ $ticket->id }})"
                                        wire:confirm="Weet je zeker dat je dit ticket wilt verwijderen?"
                                        wire:loading.attr="disabled"
                                        wire:target="delete({{ $ticket->id }})"
                                        class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Verwijderen
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-10 text-center text-sm text-gray-500">
                                Geen tickets gevonden voor de huidige filters.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $this->tickets->links(data: ['scrollTo' => false]) }}
            </div>
        </div>
    </div>
</div>
