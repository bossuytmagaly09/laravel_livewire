<?php // start van de single-file component

use App\Models\Ticket; // nodig om het ticket als property te ontvangen
use Livewire\Attributes\Computed; // nodig voor computed comments lijst
use Livewire\Component; // basis Livewire component

new
class extends Component
{
    public Ticket $ticket; // het ticket dat vanuit de parent wordt doorgegeven

    public string $content = ''; // inhoud van de nieuwe reactie of notitie

    public string $type = 'comment'; // standaardtype is een gewone comment

    public function save(): void // sla een nieuwe reactie op
    {
        $validated = $this->validate( // valideer de invoer
            [
                'content' => 'required|min:3', // inhoud is verplicht en moet lang genoeg zijn
                'type' => 'required|in:comment,note', // alleen geldige types toelaten
            ],
            [
                'content.required' => 'De inhoud is verplicht.', // foutmelding voor lege inhoud
                'content.min' => 'De inhoud moet minstens 3 tekens bevatten.', // foutmelding voor te korte inhoud
                'type.required' => 'Kies een type.', // foutmelding voor ontbrekend type
                'type.in' => 'Het gekozen type is ongeldig.', // foutmelding voor ongeldig type
            ]
        );

        $comment = $this->ticket->comments()->create([ // maak via de relatie een nieuw comment aan
            'content' => $validated['content'], // sla inhoud op
            'type' => $validated['type'], // sla type op
        ]);

        $this->reset('content'); // maak textarea opnieuw leeg

        $this->type = 'comment'; // zet type terug op de standaardwaarde

        $this->dispatch('comment-created', commentId: $comment->id, ticketId: $this->ticket->id); // dispatch event voor andere componenten op de pagina

        session()->flash('comments_success', 'De reactie werd succesvol toegevoegd.'); // toon succesfeedback binnen commentscomponent
    }

    public function delete(int $commentId): void // verwijder een bestaand comment
    {
        $comment = $this->ticket->comments()->find($commentId); // zoek het comment binnen dit ticket

        if (! $comment) { // stop als het comment niet bestaat of niet bij dit ticket hoort
            return; // defensieve controle
        }

        $comment->delete(); // verwijder het gevonden comment

        $this->dispatch('comment-deleted', commentId: $commentId, ticketId: $this->ticket->id); // dispatch event na delete

        session()->flash('comments_success', 'De reactie werd succesvol verwijderd.'); // toon succesfeedback binnen commentscomponent
    }

    #[Computed] // maak van deze methode een computed property
    public function comments() // lijst van comments voor dit ticket
    {
        return $this->ticket->comments() // start vanuit de relatie van het ticket
        ->latest() // toon nieuwste reacties eerst
        ->get(); // haal alle comments op
    }
};
?>

<div class="mt-6 space-y-6"> {{-- wrapper rond het volledige comments blok --}}

    @if (session()->has('comments_success')) {{-- toon succesfeedback na save of delete --}}
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"> {{-- succesmelding --}}
        {{ session('comments_success') }} {{-- flash message tonen --}}
    </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200"> {{-- kaart met formulier voor nieuwe reactie --}}

        <h2 class="mb-4 text-lg font-semibold text-gray-900"> {{-- titel van het formulier --}}
            Reacties en interne notities {{-- titeltekst --}}
        </h2>

        <form wire:submit="save" class="space-y-4"> {{-- formulier dat save() uitvoert zonder reload --}}

            <div> {{-- blok voor type --}}
                <label for="type" class="mb-2 block text-sm font-medium text-gray-700"> {{-- label voor type --}}
                    Type {{-- labeltekst --}}
                </label>
                <select
                    id="type"
                    wire:model="type"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                > {{-- dropdown gekoppeld aan type property --}}
                    <option value="comment">Comment</option> {{-- gewone reactie --}}
                    <option value="note">Interne notitie</option> {{-- interne notitie --}}
                </select>
                @error('type') {{-- foutmelding voor type --}}
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p> {{-- fouttekst --}}
                @enderror
            </div>

            <div> {{-- blok voor inhoud --}}
                <label for="content" class="mb-2 block text-sm font-medium text-gray-700"> {{-- label voor inhoud --}}
                    Inhoud {{-- labeltekst --}}
                </label>
                <textarea
                    id="content"
                    rows="4"
                    wire:model="content"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Schrijf hier je reactie of interne notitie..."
                ></textarea> {{-- textarea gekoppeld aan content property --}}
                @error('content') {{-- foutmelding voor inhoud --}}
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p> {{-- fouttekst --}}
                @enderror
            </div>

            <div class="flex items-center gap-4"> {{-- acties onder het formulier --}}
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                > {{-- submitknop --}}
                    Reactie opslaan {{-- knoptekst --}}
                </button>
                <span wire:loading wire:target="save" class="text-sm text-gray-500"> {{-- loading feedback tijdens save --}}
                    Bezig met opslaan... {{-- loading tekst --}}
                </span>
            </div>

        </form>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200"> {{-- kaart met historiek van reacties --}}

        <div class="mb-4 flex items-center justify-between"> {{-- bovenste rij van het overzicht --}}
            <h2 class="text-lg font-semibold text-gray-900"> {{-- titel van de lijst --}}
                Historiek van reacties {{-- titeltekst --}}
            </h2>
            <span class="text-sm text-gray-500"> {{-- teller rechts --}}
                {{ $this->comments->count() }} item(s) {{-- aantal comments --}}
            </span>
        </div>

        <div class="space-y-4"> {{-- lijst met comments --}}
            @forelse ($this->comments as $comment) {{-- loop over alle comments --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4"> {{-- kaart van één comment --}}

                <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-start md:justify-between"> {{-- bovenste rij per comment --}}

                    <div class="flex flex-wrap items-center gap-3"> {{-- badges en datum --}}
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $comment->typeBadgeClasses() }}"> {{-- type badge --}}
                            {{ $comment->typeLabel() }} {{-- leesbaar type --}}
                            </span>
                        <span class="text-xs text-gray-500"> {{-- created_at --}}
                            {{ $comment->created_at->format('d/m/Y H:i') }} {{-- datum van de reactie --}}
                            </span>
                    </div>

                    <button
                        type="button"
                        wire:click="delete({{ $comment->id }})"
                        wire:confirm="Weet je zeker dat je deze reactie wilt verwijderen?"
                        wire:loading.attr="disabled"
                        wire:target="delete({{ $comment->id }})"
                        class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    > {{-- deleteknop per reactie --}}
                        Verwijderen {{-- knoptekst --}}
                    </button>

                </div>

                <div class="text-sm leading-6 text-gray-700"> {{-- inhoud van de reactie --}}
                    {{ $comment->content }} {{-- eigenlijke commenttekst --}}
                </div>

            </div>
            @empty {{-- als er nog geen comments zijn --}}
            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500"> {{-- empty state --}}
                Er zijn nog geen reacties of interne notities toegevoegd voor dit ticket. {{-- lege melding --}}
            </div>
            @endforelse
        </div>

    </div>

</div>
