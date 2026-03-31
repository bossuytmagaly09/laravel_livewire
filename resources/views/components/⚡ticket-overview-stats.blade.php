<?php // start van de single-file component

use App\Models\Ticket; // nodig om het ticket als property te ontvangen
use Livewire\Attributes\Computed; // nodig voor computed statistieken
use Livewire\Attributes\On; // nodig om naar events te luisteren
use Livewire\Component; // basis Livewire component

new
class extends Component
{
    public Ticket $ticket; // het ticket dat vanuit de parent wordt doorgegeven

    #[Computed] // maak van deze methode een computed property
    public function stats(): array // verzamel alle statistieken voor dit ticket
    {
        $commentsCount = $this->ticket->comments() // start vanuit de comments relatie
        ->where('type', 'comment') // tel alleen gewone comments
        ->count(); // tel het aantal records

        $notesCount = $this->ticket->comments() // start opnieuw vanuit de comments relatie
        ->where('type', 'note') // tel alleen interne notities
        ->count(); // tel het aantal records

        $attachmentsCount = $this->ticket->attachments() // start vanuit de attachments relatie
        ->count(); // tel het aantal bestanden

        return [ // geef alle statistieken samen terug
            'comments' => $commentsCount, // totaal comments
            'notes' => $notesCount, // totaal interne notities
            'attachments' => $attachmentsCount, // totaal bestanden
            'total' => $commentsCount + $notesCount + $attachmentsCount, // alles samen
        ];
    }

    #[On('comment-created')] // luister naar event na nieuwe comment
    #[On('comment-deleted')] // luister naar event na verwijderen van comment
    #[On('attachment-created')] // luister naar event na nieuwe upload
    #[On('attachment-deleted')] // luister naar event na verwijderen van upload
    public function refreshStats(): void // forceer een refresh van de computed statistieken
    {
        unset($this->stats); // maak de cached computed property leeg zodat ze opnieuw berekend wordt

        $this->ticket->refresh(); // refresh het ticket model voor een propere state
    }
};
?>

<div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200"> {{-- wrapper rond het statistiekenblok --}}

    <div class="mb-4"> {{-- titelblok --}}
        <h2 class="text-lg font-semibold text-gray-900"> {{-- titel --}}
            Ticketstatistieken {{-- titeltekst --}}
        </h2>
        <p class="mt-1 text-sm text-gray-600"> {{-- subtitel --}}
            Deze cijfers verversen automatisch wanneer comments, notities of bestanden wijzigen. {{-- subtiteltekst --}}
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-4"> {{-- grid met vier statistiekkaarten --}}

        <div class="rounded-xl bg-blue-50 p-4 ring-1 ring-blue-100"> {{-- kaart voor comments --}}
            <div class="text-xs font-semibold uppercase tracking-wide text-blue-700"> {{-- label --}}
                Comments {{-- labeltekst --}}
            </div>
            <div class="mt-2 text-3xl font-bold text-blue-900"> {{-- waarde --}}
                {{ $this->stats['comments'] }} {{-- aantal comments --}}
            </div>
        </div>

        <div class="rounded-xl bg-yellow-50 p-4 ring-1 ring-yellow-100"> {{-- kaart voor notities --}}
            <div class="text-xs font-semibold uppercase tracking-wide text-yellow-700"> {{-- label --}}
                Interne notities {{-- labeltekst --}}
            </div>
            <div class="mt-2 text-3xl font-bold text-yellow-900"> {{-- waarde --}}
                {{ $this->stats['notes'] }} {{-- aantal interne notities --}}
            </div>
        </div>

        <div class="rounded-xl bg-purple-50 p-4 ring-1 ring-purple-100"> {{-- kaart voor attachments --}}
            <div class="text-xs font-semibold uppercase tracking-wide text-purple-700"> {{-- label --}}
                Bestanden {{-- labeltekst --}}
            </div>
            <div class="mt-2 text-3xl font-bold text-purple-900"> {{-- waarde --}}
                {{ $this->stats['attachments'] }} {{-- aantal bestanden --}}
            </div>
        </div>

        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-200"> {{-- kaart voor totaal --}}
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-700"> {{-- label --}}
                Totaal items {{-- labeltekst --}}
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900"> {{-- waarde --}}
                {{ $this->stats['total'] }} {{-- totaal van alles samen --}}
            </div>
        </div>

    </div>

</div>
