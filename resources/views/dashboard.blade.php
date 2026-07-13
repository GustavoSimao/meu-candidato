<x-layouts::app :title="__('Dashboard')">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold mb-6">Meu painel</h1>

        @php
            $followedIds = auth()->user()->follows()->pluck('politician_id');
            $followed = \MeuCandidato\Candidate\Models\Politician::with(['party', 'latestAddress', 'badges'])
                ->whereIn('id', $followedIds)
                ->get();
        @endphp

        @if ($followed->isEmpty())
            <div class="bg-zinc-50 rounded-xl p-10 text-center">
                <p class="text-zinc-500">Você ainda não segue nenhum político.</p>
                <a href="{{ route('politicos') }}" class="inline-block mt-3 text-sm text-blue-600 hover:text-blue-700 font-medium">
                    Explorar políticos →
                </a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($followed as $p)
                    <a href="{{ route('politicos.show', $p->id) }}" class="block bg-white border border-zinc-200 rounded-xl p-4 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 bg-zinc-100">
                                @if ($p->photo_url)
                                    <img src="{{ $p->photo_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-zinc-200 text-zinc-500 text-xs font-bold">
                                        {{ strtoupper(mb_substr($p->name, 0, 2)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 truncate">{{ $p->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $p->party?->acronym ?? 'S/' }} · {{ $p->latestAddress?->uf ?? '—' }}</p>
                                <p class="text-xs text-zinc-500">{{ $p->position }}</p>
                            </div>
                            @if ($p->badges->count() > 0)
                                <div class="flex gap-1 flex-shrink-0">
                                    @foreach ($p->badges->take(3) as $badge)
                                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $badge->color }}"></span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Stats --}}
        <div class="mt-8 grid grid-cols-3 gap-4">
            <div class="bg-zinc-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900">{{ $followed->count() }}</p>
                <p class="text-xs text-zinc-500 mt-1">Políticos seguidos</p>
            </div>
            <div class="bg-zinc-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900">
                    {{ number_format($followed->sum(fn ($p) => $p->badges->count()), 0, ',', '.') }}
                </p>
                <p class="text-xs text-zinc-500 mt-1">Badges acumuladas</p>
            </div>
            <div class="bg-zinc-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900">
                    {{ number_format(\MeuCandidato\Candidate\Models\Politician::whereHas('mandates', function ($q) {
                        $q->whereNull('ended_at')->orWhere('ended_at', '>=', now());
                    })->count(), 0, ',', '.') }}
                </p>
                <p class="text-xs text-zinc-500 mt-1">Políticos em exercício</p>
            </div>
        </div>
    </div>
</x-layouts::app>
