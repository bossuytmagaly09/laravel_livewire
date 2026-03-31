<?php

use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Component;

new
class extends Component
{
    public Ticket $ticket;

    public string $content = '';

    public string $type = 'comment';

    public function save(): void
    {
        $validated = $this->validate(
            [
                'content' => 'required|min:3',
                'type' => 'required|in:comment,note',
            ],
            [
                'content.required' => 'De inhoud is verplicht.',
                'content.min' => 'De inhoud moet minstens 3 tekens bevatten.',
                'type.required' => 'Kies een type.',
                'type.in' => 'Het gekozen type is ongeldig.',
            ]
        );

        $comment = $this->ticket->comments()->create([
            'content' => $validated['content'],
            'type' => $validated['type'],
        ]);

        $label = $validated['type'] === 'note' ? 'Interne notitie toegevoegd' : 'Comment toegevoegd'; // NIEUW: we maken het label afhankelijk van het type zodat de activity log menselijker leesbaar wordt

        $description = $validated['type'] === 'note'
            ? 'Een interne notitie werd toegevoegd aan dit ticket.'
            : 'Een nieuwe comment werd toegevoegd aan dit ticket.'; // NIEUW: ook de beschrijving maken we bewust leesbaar en contextueel

        $this->ticket->logActivity(
            'comment_created',
            $label,
            $description
        ); // NIEUW: na het bewaren van een comment of notitie schrijven we meteen een activity logregel weg

        $this->reset('content');

        $this->type = 'comment';

        $this->dispatch('comment-created', commentId: $comment->id, ticketId: $this->ticket->id);

        session()->flash('comments_success', 'De reactie werd succesvol toegevoegd.');
    }

    public function delete(int $commentId): void
    {
        $comment = $this->ticket->comments()->find($commentId);

        if (! $comment) {
            return;
        }

        $label = $comment->type === 'note' ? 'Interne notitie verwijderd' : 'Comment verwijderd'; // NIEUW: ook bij verwijderen willen we in de historiek duidelijk blijven zien wat voor type item verdwenen is

        $description = $comment->type === 'note'
            ? 'Een interne notitie werd verwijderd van dit ticket.'
            : 'Een comment werd verwijderd van dit ticket.'; // NIEUW: korte maar leesbare beschrijving voor de activity log

        $comment->delete();

        $this->ticket->logActivity(
            'comment_deleted',
            $label,
            $description
        ); // NIEUW: na delete registreren we ook deze actie in de historiek van het ticket

        $this->dispatch('comment-deleted', commentId: $commentId, ticketId: $this->ticket->id);

        session()->flash('comments_success', 'De reactie werd succesvol verwijderd.');
    }

    #[Computed]
    public function comments()
    {
        return $this->ticket->comments()
            ->latest()
            ->get();
    }
};
?>

<div class="mt-6 space-y-6">

    @if (session()->has('comments_success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('comments_success') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">
            Reacties en interne notities
        </h2>
        <form wire:submit="save" class="space-y-4">

            <div>
                <label for="type" class="mb-2 block text-sm font-medium text-gray-700">
                    Type
                </label>
                <select
                    id="type"
                    wire:model="type"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="comment">Comment</option>
                    <option value="note">Interne notitie</option>
                </select>
                @error('type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="content" class="mb-2 block text-sm font-medium text-gray-700">
                    Inhoud
                </label>
                <textarea
                    id="content"
                    rows="4"
                    wire:model="content"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Schrijf hier je reactie of interne notitie..."
                ></textarea>
                @error('content')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Reactie opslaan
                </button>
                <span wire:loading wire:target="save" class="text-sm text-gray-500">
                    Bezig met opslaan...
                </span>
            </div>

        </form>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">
                Historiek van reacties
            </h2>
            <span class="text-sm text-gray-500">
                {{ $this->comments->count() }} item(s)
            </span>
        </div>

        <div class="space-y-4">
            @forelse ($this->comments as $comment)
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $comment->typeBadgeClasses() }}">
                                {{ $comment->typeLabel() }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $comment->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        <button
                            type="button"
                            wire:click="delete({{ $comment->id }})"
                            wire:confirm="Weet je zeker dat je deze reactie wilt verwijderen?"
                            wire:loading.attr="disabled"
                            wire:target="delete({{ $comment->id }})"
                            class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Verwijderen
                        </button>
                    </div>
                    <div class="text-sm leading-6 text-gray-700">
                        {{ $comment->content }}
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500">
                    Er zijn nog geen reacties of interne notities toegevoegd voor dit ticket.
                </div>
            @endforelse
        </div>
    </div>

</div>
