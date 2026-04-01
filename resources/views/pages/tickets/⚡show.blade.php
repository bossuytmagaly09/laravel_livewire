<?php

use App\Models\Ticket; // nodig voor route model binding en updates
use App\Models\User;   // NIEUW: nodig om mogelijke assignees op te halen
use Livewire\Attributes\Computed; // NIEUW: nodig voor computed assignees lijst
use Livewire\Attributes\Layout;   // om deze page aan de layout te koppelen
use Livewire\Component;           // basis Livewire component

new
#[Layout('layouts.app')]
class extends Component
{
    public Ticket $ticket;           // het ticket dat via de route binnenkomt
    public string $subject = '';     // onderwerp van het ticket
    public string $description = ''; // beschrijving van het ticket
    public string $status = 'open';  // status van het ticket
    public string $priority = 'medium'; // prioriteit van het ticket
    public string $workflow_step = 'new';    // NIEUW: workflowstap van het ticket
    public string $assigned_user_id = '';    // NIEUW: toegewezen gebruiker als string voor select binding

    public function mount(Ticket $ticket): void
    {
        $this->ticket          = $ticket;                  // sla route-bound ticket op
        $this->subject         = $ticket->subject;         // laad onderwerp in component-state
        $this->description     = $ticket->description;     // laad beschrijving in component-state
        $this->status          = $ticket->status->value;          // laad status in component-state
        $this->priority        = $ticket->priority->value;        // laad prioriteit in component-state
        $this->workflow_step   = $ticket->workflow_step->value;   // NIEUW: laad workflow in component-state
        $this->assigned_user_id = $ticket->assigned_user_id ? (string) $ticket->assigned_user_id : ''; // NIEUW: laad assignee of lege string
    }

    #[Computed]
    public function assignees()
    {
        return User::query()
            ->orderBy('name') // NIEUW: alfabetisch sorteren
            ->get();          // NIEUW: mogelijke supportmedewerkers ophalen
    }

    public function save(): void
    {
        $validated = $this->validate(
            [
                'subject'          => 'required|min:3|max:255',                                              // onderwerp moet geldig zijn
                'description'      => 'required|min:10',                                                     // beschrijving moet voldoende lang zijn
                'status'           => 'required|in:open,in_progress,closed',                                  // alleen geldige statussen
                'priority'         => 'required|in:low,medium,high',                                         // alleen geldige prioriteiten
                'workflow_step'    => 'required|in:new,triage,investigating,waiting_customer,resolved',       // NIEUW: alleen geldige workflowstappen
                'assigned_user_id' => 'nullable|exists:users,id',                                            // NIEUW: assignment moet verwijzen naar bestaande user
            ],
            [
                'subject.required'          => 'Het onderwerp is verplicht.',                       // foutmelding voor leeg onderwerp
                'subject.min'               => 'Het onderwerp moet minstens 3 tekens bevatten.',    // foutmelding voor te kort onderwerp
                'subject.max'               => 'Het onderwerp mag maximaal 255 tekens bevatten.',   // foutmelding voor te lang onderwerp
                'description.required'      => 'De beschrijving is verplicht.',                     // foutmelding voor lege beschrijving
                'description.min'           => 'De beschrijving moet minstens 10 tekens bevatten.', // foutmelding voor te korte beschrijving
                'status.required'           => 'Kies een status.',                                  // foutmelding voor ontbrekende status
                'status.in'                 => 'De gekozen status is ongeldig.',                    // foutmelding voor ongeldige status
                'priority.required'         => 'Kies een prioriteit.',                              // foutmelding voor ontbrekende prioriteit
                'priority.in'               => 'De gekozen prioriteit is ongeldig.',                // foutmelding voor ongeldige prioriteit
                'workflow_step.required'    => 'Kies een workflowstap.',                            // NIEUW: foutmelding voor ontbrekende workflow
                'workflow_step.in'          => 'De gekozen workflowstap is ongeldig.',              // NIEUW: foutmelding voor ongeldige workflow
                'assigned_user_id.exists'   => 'De gekozen behandelaar bestaat niet.',             // NIEUW: foutmelding voor ongeldige assignee
            ]
        );

        $oldStatus       = $this->ticket->status;          // NIEUW: oude status bewaren voor logging
        $oldPriority     = $this->ticket->priority;        // NIEUW: oude prioriteit bewaren voor logging
        $oldWorkflow     = $this->ticket->workflow_step;   // NIEUW: oude workflow bewaren voor logging
        $oldAssigneeName = $this->ticket->assigneeName();  // NIEUW: oude assigneenaam bewaren voor logging

        $this->ticket->update([
            'subject'          => $validated['subject'],          // schrijf nieuw onderwerp weg
            'description'      => $validated['description'],      // schrijf nieuwe beschrijving weg
            'status'           => $validated['status'],           // schrijf nieuwe status weg
            'priority'         => $validated['priority'],         // schrijf nieuwe prioriteit weg
            'workflow_step'    => $validated['workflow_step'],    // NIEUW: schrijf nieuwe workflow weg
            'assigned_user_id' => $validated['assigned_user_id'] !== '' ? $validated['assigned_user_id'] : null, // NIEUW: zet lege string om naar null
        ]);

        $this->ticket->refresh(); // herlaad nieuwste ticketwaarden uit database

        if ($oldStatus !== $this->ticket->status) {
            $this->ticket->logActivity("Status gewijzigd van {$oldStatus} naar {$this->ticket->status}."); // NIEUW: log statuswijziging
        }

        if ($oldPriority !== $this->ticket->priority) {
            $this->ticket->logActivity("Prioriteit gewijzigd van {$oldPriority} naar {$this->ticket->priority}."); // NIEUW: log prioriteitswijziging
        }

        if ($oldWorkflow !== $this->ticket->workflow_step) {
            $this->ticket->logActivity("Workflow gewijzigd van {$oldWorkflow} naar {$this->ticket->workflow_step}."); // NIEUW: log workflowwijziging
        }

        if ($oldAssigneeName !== $this->ticket->assigneeName()) {
            $this->ticket->logActivity("Toegewezen behandelaar gewijzigd van {$oldAssigneeName} naar {$this->ticket->assigneeName()}."); // NIEUW: log assignmentwijziging
        }

        $this->dispatch('ticket-activity-created'); // NIEUW: laat activity log component mee verversen
        session()->flash('success', 'Het ticket werd succesvol bijgewerkt.'); // toon succesmelding
    }

    public function delete(): mixed
    {
        $this->ticket->delete(); // verwijder ticket
        session()->flash('success', 'Het ticket werd succesvol verwijderd.'); // flash message
        return $this->redirect(route('tickets.index')); // redirect terug naar overzicht
    }
};
?>

<div class="min-h-screen bg-gray-100 py-10">
    <div class="mx-auto max-w-4xl px-4">
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Ticket #{{ $ticket->id }}
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    Bewerk dit support ticket via een Livewire 4 detailpagina.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('tickets.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                >
                    Terug naar overzicht
                </a>

                <button
                    type="button"
                    wire:click="delete"
                    wire:confirm="Weet je zeker dat je dit ticket wilt verwijderen?"
                    wire:loading.attr="disabled"
                    wire:target="delete"
                    class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Verwijderen
                </button>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <form wire:submit="save" class="space-y-6">
                <div>
                    <label for="subject" class="mb-2 block text-sm font-medium text-gray-700">
                        Onderwerp
                    </label>
                    <input
                        id="subject"
                        type="text"
                        wire:model="subject"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Bijv. Login lukt niet"
                    >
                    @error('subject')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="mb-2 block text-sm font-medium text-gray-700">
                        Beschrijving
                    </label>
                    <textarea
                        id="description"
                        rows="6"
                        wire:model="description"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Beschrijf het probleem zo duidelijk mogelijk..."
                    ></textarea>
                    @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="status" class="mb-2 block text-sm font-medium text-gray-700">
                            Status
                        </label>
                        <select
                            id="status"
                            wire:model="status"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="open">Open</option>
                            <option value="in_progress">In behandeling</option>
                            <option value="closed">Gesloten</option>
                        </select>
                        @error('status')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="priority" class="mb-2 block text-sm font-medium text-gray-700">
                            Prioriteit
                        </label>
                        <select
                            id="priority"
                            wire:model="priority"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="low">Laag</option>
                            <option value="medium">Normaal</option>
                            <option value="high">Hoog</option>
                        </select>
                        @error('priority')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="workflow_step" class="mb-2 block text-sm font-medium text-gray-700">
                            Workflow
                        </label>
                        <select
                            id="workflow_step"
                            wire:model="workflow_step"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="new">Nieuw</option>
                            <option value="triage">Triage</option>
                            <option value="investigating">Onderzoek</option>
                            <option value="waiting_customer">Wacht op klant</option>
                            <option value="resolved">Opgelost</option>
                        </select>
                        @error('workflow_step')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="assigned_user_id" class="mb-2 block text-sm font-medium text-gray-700">
                            Toegewezen aan
                        </label>
                        <select
                            id="assigned_user_id"
                            wire:model="assigned_user_id"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Niet toegewezen</option>
                            @foreach ($this->assignees as $assignee)
                                <option value="{{ $assignee->id }}">
                                    {{ $assignee->name }} {{-- NIEUW: mogelijke supportmedewerker --}}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_user_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <span class="font-semibold">Huidige workflow:</span>
                            {{ $ticket->workflowLabel() }} {{-- NIEUW: toon actuele workflow --}}
                        </div>
                        <div>
                            <span class="font-semibold">Huidige behandelaar:</span>
                            {{ $ticket->assigneeName() }} {{-- NIEUW: toon actuele toegewezen gebruiker --}}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Wijzigingen opslaan
                    </button>
                    <span wire:loading wire:target="save" class="text-sm text-gray-500">
                        Bezig met opslaan...
                    </span>
                </div>
            </form>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">
                Alleen lezen, metadata
            </h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <span class="block text-sm text-gray-500">Aangemaakt op</span>
                    <span class="text-sm font-medium text-gray-800">
                        {{ $ticket->created_at->format('d/m/Y H:i') }}
                    </span>
                </div>
                <div>
                    <span class="block text-sm text-gray-500">Laatst bijgewerkt op</span>
                    <span class="text-sm font-medium text-gray-800">
                        {{ $ticket->updated_at->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>
        </div>

        <livewire:ticket-overview-stats :ticket="$ticket" :key="'ticket-overview-stats-' . $ticket->id" /> {{-- bestaand statscomponent --}}
        <livewire:ticket-comments :ticket="$ticket" :key="'ticket-comments-' . $ticket->id" /> {{-- bestaand commentscomponent --}}
        <livewire:ticket-attachments :ticket="$ticket" :key="'ticket-attachments-' . $ticket->id" /> {{-- bestaand attachmentscomponent --}}
        <livewire:ticket-activity-log :ticket="$ticket" :key="'ticket-activity-log-' . $ticket->id" /> {{-- bestaand activity log component --}}
    </div>
</div>
