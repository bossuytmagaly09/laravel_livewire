<?php

use App\Models\Ticket;// nodig om tickets op te halen, updaten en verwijderen
use Livewire\Attributes\Computed;// om tickets() als computed property te gebruiken
use Livewire\Attributes\Layout;// om deze page aan de layout te koppelen
use Livewire\Attributes\Url;// om filters in de URL te bewaren
use Livewire\Component;// basis Livewire component
use Livewire\WithPagination;// nodig voor Livewire pagination

new
#[Layout('layouts.app')] // koppel deze page aan resources/views/layouts/app.blade.php

class extends Component {
    use WithPagination;

    // activeer Livewire pagination binnen deze component

    #[Url(as: 'q')]
    public string $search = ''; // zoekterm voor onderwerp en beschrijving

    #[Url]
    public string $status = ''; // filter op status

    #[Url]
    public string $priority = ''; // filter op prioriteit

    #[Url(as: 'sort')]
    public string $sortField = 'created_at'; // standaard sorteren op aanmaakdatum

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc'; // nieuwste tickets eerst tonen

    public int $perPage = 10; // aantal records per pagina

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

    public function sortBy(string $field): void
    {
        $allowedFields = ['id', 'subject', 'status', 'priority',
            'created_at']; // whitelist van toegelaten sorteervelden
        if (!in_array($field, $allowedFields, true)) {
            return; // stop als iemand een ongeldig sorteerveld probeert door te geven
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; // draai de sorteerrichting om op dezelfde kolom
        } else {
            $this->sortField = $field; // stel een nieuw sorteerveld in
            $this->sortDirection = 'asc'; // start nieuwe kolom standaard ascendant
        }
        $this->resetPage(); // zet pagination terug naar pagina 1 na sorteerwijziging
    }

    public function clearFilters(): void
    {
        $this->search = ''; // wis zoekterm
        $this->status = ''; // wis statusfilter
        $this->priority = ''; // wis prioriteitsfilter
        $this->sortField = 'created_at'; // herstel standaardsortering
        $this->sortDirection = 'desc'; // herstel standaard sorteerrichting
        $this->resetPage(); // ga terug naar pagina 1
    }

    public function changeStatus(int $ticketId, string $status): void
    {
        $allowedStatuses = ['open', 'in_progress', 'closed']; // whitelist van geldige statussen
        if (!in_array($status, $allowedStatuses, true)) {
            return; // stop als de status ongeldig is
        }

        $ticket = Ticket::find($ticketId); // zoek het ticket op basis van zijn id

        if (!$ticket) {
            return; // stop als het ticket niet meer bestaat
        }

        $ticket->update([
            'status' => $status, // schrijf de nieuwe status weg
        ]);

        session()->flash('success', 'De status van het ticket werd succesvol aangepast.'); // toon feedback bovenaan
    }

    public function delete(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId); // zoek het ticket op basis van zijn id
        if (!$ticket) {
            return; // stop als het ticket niet bestaat
        }
        $ticket->delete(); // verwijder het ticket uit de database

        session()->flash('success', 'Het ticket werd succesvol verwijderd.'); // toon feedback bovenaan

        $this->resetPage(); // blijf op een geldige pagina na delete
    }

    #[Computed]
    public function tickets()
    {
        return Ticket::query()->when($this->search !== '', function ($query) {
            $query->where(function ($subQuery) {
                $subQuery->where('subject', 'like', '%' . $this->search .
                    '%')->orWhere('description', 'like', '%' .
                    $this->search . '%'); // zoek in onderwerp of beschrijving
            });
        })
            ->when($this->status !== '', function ($query) {
                $query->where('status', $this->status); // filter op gekozen status
            })
            ->when($this->priority !== '', function ($query) {
                $query->where('priority', $this->priority); // filter op gekozen prioriteit
            })
            ->orderBy($this->sortField, $this->sortDirection) // passortering toe
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
                    Bekijk, filter en beheer support tickets rechtstreeks
                    vanuit het overzicht.
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
            <div class="grid gap-4 md:grid-cols-3">
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
            </div>
        </div>
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('id')"
                                    class="inline-flex items-center gap-2">
                                ID
                                @if ($sortField === 'id')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('subject')"
                                    class="inline-flex items-center gap-2">
                                Onderwerp
                                @if ($sortField === 'subject')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('status')"
                                    class="inline-flex items-center gap-2">
                                Status
                                @if ($sortField === 'status')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('priority')"
                                    class="inline-flex items-center gap-2">
                                Prioriteit
                                @if ($sortField === 'priority')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('created_at')"
                                    class="inline-flex items-center gap-2">
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
                        <tr wire:key="ticket-{{ $ticket->id }}"
                            class="align-top hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                #{{ $ticket->id }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900">
                                    <a href="{{ route('tickets.show', $ticket) }}"
                                       class="transition hover:text-blue-600 hover:underline"
                                    >
                                        {{ $ticket->subject }}
                                    </a>
                                </div>

                                <div class="mt-1 line-clamp-2 text-sm text-gray-500">
                                    {{ $ticket->description }}
                                </div>
                            </td>

                            <td class=" whitespace-nowrap px-6 py-4 text-sm">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->statusBadgeClasses() }}">
                                    {{ $ticket->statusLabel() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->priorityBadgeClasses() }}">
                                    {{ $ticket->priorityLabel() }}
                                </span>
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
                                        class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium
                                                text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Open
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="changeStatus({{ $ticket->id }}, 'in_progress')"
                                        wire:loading.attr="disabled"
                                        wire:target="changeStatus({{ $ticket->id }}, 'in_progress')"
                                        class="inline-flex items-center rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs font-medium
                                            text-yellow-700 transition hover:bg-yellow-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        In behandeling
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="changeStatus({{ $ticket->id }}, 'closed')"
                                        wire:loading.attr="disabled"
                                        wire:target="changeStatus({{ $ticket->id }}, 'closed')"
                                        class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium
                                                text-green-700 transition hover:bg-green-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Gesloten
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-3">
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
                                        class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm
                                                transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Verwijderen
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
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
