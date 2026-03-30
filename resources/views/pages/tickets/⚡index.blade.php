<?php

use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = ''; // zoekterm voor onderwerp en beschrijving

    #[Url]
    public string $status = ''; // filter op status

    #[Url]
    public string $priority = ''; // filter op prioriteit

    #[Url(as: 'sort')]
    public string $sortField = 'created_at'; // standaard sorteren op aanmaakdatum

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc'; // nieuwste tickets eerst

    public int $perPage = 10; // aantal tickets per pagina

    public function updatingSearch(): void
    {
        $this->resetPage(); // ga terug naar pagina 1 zodra de zoekterm verandert
    }

    public function updatingStatus(): void
    {
        $this->resetPage(); // ga terug naar pagina 1 zodra statusfilter verandert
    }

    public function updatingPriority(): void
    {
        $this->resetPage(); // ga terug naar pagina 1 zodra prioriteitsfilter verandert
    }

    public function sortBy(string $field): void
    {
        $allowedFields = ['id', 'subject', 'status', 'priority', 'created_at']; // whitelist van toegelaten sorteervelden

        if (!in_array($field, $allowedFields, true)) {
            return; // negeer ongeldige sorteervelden
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; // toggle richting als je op dezelfde kolom klikt

        } else {
            $this->sortField = $field; // nieuw sorteerveld instellen
            $this->sortDirection = 'asc'; // bij een nieuwe kolom starten we ascendant
        }

        $this->resetPage(); // terug naar pagina 1 na wijziging van sortering
    }

    #[Computed]
    public function tickets()
    {
        return Ticket::query()->when($this->search !== '', function ($query) {
            $query->where(function ($subQuery) {
                $subQuery->where('subject', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        })
            ->when($this->status !== '', function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->priority !== '', function ($query) {
                $query->where('priority', $this->priority);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
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
                    Bekijk, zoek en filter support tickets via een Livewire 4
                    page component.
                </p>
            </div>
            <a
                href="{{ route('tickets.create') }}"
                class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                Nieuw ticket
            </a>
        </div>
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
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($this->tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}"
                            class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                #{{ $ticket->id }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ $ticket->subject }}
                                </div>
                                <div class=" mt-1 line-clamp-2 text-sm
                                     text-gray-500
                                ">
                                    {{ $ticket->description }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($ticket->status === 'open')
                                    <span class=" inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                        Open
                                    </span>
                                @elseif ($ticket->status === 'in_progress')
                                    <span class="inline-flex rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">
                                        In behandeling
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                        Gesloten
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($ticket->priority === 'low')
                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                        Laag
                                    </span>
                                @elseif ($ticket->priority === 'medium')
                                    <span class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                                        Normaal
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                        Hoog
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text sm text-gray-600">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
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
