<div>
@livewire('politicos.ver-votacoes-modal', ['politicianId' => $this->politician->id ?? ''])
@livewire('politicos.ver-proposicoes-modal', ['politicianId' => $this->politician->id ?? ''])
@livewire('politicos.ver-despesas-modal', ['politicianId' => $this->politician->id ?? ''])

<section class="w-full max-w-4xl mx-auto py-8 px-4">
    @if ($this->politician === null)
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <flux:heading size="xl" level="1">Político não encontrado</flux:heading>
            <flux:subheading size="lg">O ID informado não corresponde a nenhum político cadastrado.</flux:subheading>
            <flux:button wire:navigate href="{{ route('politicos') }}" variant="primary" class="mt-6">
                Voltar à lista
            </flux:button>
        </div>
    @else
        @php $p = $this->politician; @endphp

        {{-- Header --}}
        <div class="flex items-start gap-5 mb-8">
            <div class="w-24 h-24 rounded-xl overflow-hidden flex-shrink-0 bg-zinc-100">
                @if ($p['photo'])
                    <img
                        src="{{ $p['photo'] }}"
                        alt="{{ $p['name'] }}"
                        class="w-full h-full object-cover"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    />
                    <div class="w-full h-full flex items-center justify-center bg-zinc-200 text-zinc-500 text-2xl font-bold" style="display: none;">
                        {{ strtoupper(mb_substr($p['name'], 0, 2)) }}
                    </div>
                @else
                    <div class="w-full h-full flex items-center justify-center bg-zinc-200 text-zinc-500 text-2xl font-bold">
                        {{ strtoupper(mb_substr($p['name'], 0, 2)) }}
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <flux:heading size="xl" level="1">{{ $p['name'] }}</flux:heading>
                <p class="text-sm text-zinc-500 mt-1 font-mono">
                    {{ $p['party'] }} · {{ $p['party_name'] }} · {{ $p['state'] }}
                </p>
                <p class="text-sm text-zinc-600 mt-0.5">{{ $p['position'] }}</p>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <span class="text-xs text-zinc-500">{{ $this->followersCount }} seguidores</span>
                <button
                    wire:click="toggleFollow"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg border transition
                        {{ $this->isFollowing
                            ? 'bg-red-50 border-red-200 text-red-700 hover:bg-red-100'
                            : 'bg-blue-50 border-blue-200 text-blue-700 hover:bg-blue-100' }}"
                >
                    @if ($this->isFollowing)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Seguindo
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Seguir
                    @endif
                </button>
            </div>
        </div>

        {{-- Dados pessoais --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            @if ($p['education'])
                <div class="bg-zinc-50 rounded-lg p-3">
                    <p class="text-xs font-medium text-zinc-500 uppercase">Escolaridade</p>
                    <p class="text-sm text-zinc-900 mt-0.5">{{ $p['education'] }}</p>
                </div>
            @endif
            @if ($p['birth_date'])
                <div class="bg-zinc-50 rounded-lg p-3">
                    <p class="text-xs font-medium text-zinc-500 uppercase">Nascimento</p>
                    <p class="text-sm text-zinc-900 mt-0.5">{{ $p['birth_date'] }}</p>
                </div>
            @endif
            @if ($p['declared_profession'])
                <div class="bg-zinc-50 rounded-lg p-3">
                    <p class="text-xs font-medium text-zinc-500 uppercase">Profissão</p>
                    <p class="text-sm text-zinc-900 mt-0.5">{{ $p['declared_profession'] }}</p>
                </div>
            @endif
            @if ($p['total_expenses'] > 0)
                <div class="bg-zinc-50 rounded-lg p-3">
                    <p class="text-xs font-medium text-zinc-500 uppercase">Despesas CEAP</p>
                    <p class="text-sm text-zinc-900 mt-0.5 font-semibold">
                        R$ {{ number_format($p['total_expenses'], 2, ',', '.') }}
                    </p>
                </div>
            @endif
        </div>

        {{-- O que defende --}}
        @if ($p['defends'])
            <div class="mb-8">
                <flux:heading size="lg" level="2">O que defende</flux:heading>
                <p class="text-sm text-zinc-700 mt-2 leading-relaxed">{{ $p['defends'] }}</p>
            </div>
        @endif

        {{-- Mandatos --}}
        @if (count($p['mandates']) > 0)
            <div class="mb-8">
                <flux:heading size="lg" level="2">Mandatos</flux:heading>
                <div class="mt-3 space-y-2">
                    @foreach ($p['mandates'] as $mandate)
                        <div class="flex items-center gap-3 bg-white border border-zinc-200 rounded-lg px-4 py-3">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-zinc-900 font-medium">{{ $mandate['position'] }}</p>
                                <p class="text-xs text-zinc-500">
                                    {{ $mandate['started_at'] }} — {{ $mandate['ended_at'] ?? 'Em exercício' }}
                                </p>
                            </div>
                            @if ($mandate['salary'])
                                <span class="text-xs text-zinc-500 font-mono">
                                    R$ {{ number_format($mandate['salary'], 2, ',', '.') }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Badges --}}
        @if (count($p['badges']) > 0)
            <div class="mb-8">
                <flux:heading size="lg" level="2">Distintivos</flux:heading>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($p['badges'] as $badge)
                        <div
                            class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full"
                            style="background-color: {{ $badge['color'] ? $badge['color'] . '20' : '#ecfdf5' }}; color: {{ $badge['color'] ?? '#047857' }}"
                        >
                            {{ $badge['name'] }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Proposições --}}
        @if (count($p['bills']) > 0)
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" level="2">Proposições</flux:heading>
                    @if ($p['bills_count'] > 3)
                        <button wire:click="$dispatch('openProposicoesModal')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            Ver todas →
                        </button>
                    @endif
                </div>
                <div class="mt-3 space-y-2">
                    @foreach (array_slice($p['bills'], 0, 3) as $bill)
                        <a href="https://www.camara.leg.br/proposicoesWeb/fichadetramitacao?idProposicao={{ $bill['external_id'] }}" target="_blank" rel="noopener" class="block bg-white border border-zinc-200 rounded-lg px-4 py-3 hover:bg-zinc-50 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm text-zinc-900 font-medium line-clamp-2">{{ $bill['title'] }}</p>
                                    @if ($bill['description'])
                                        <p class="text-xs text-zinc-500 mt-1 line-clamp-2">{{ $bill['description'] }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                    @if ($bill['status'])
                                        <span class="text-xs bg-zinc-100 text-zinc-600 px-2 py-0.5 rounded-full">{{ $bill['status'] }}</span>
                                    @endif
                                    <span class="text-xs text-zinc-400">{{ $bill['year'] }}</span>
                                    <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Votações --}}
        @if (count($p['votes']) > 0)
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" level="2">Votos</flux:heading>
                    @if ($p['votes_count'] > 3)
                        <button wire:click="$dispatch('openVotacoesModal')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            Ver todas →
                        </button>
                    @endif
                </div>
                <div class="mt-3 space-y-2">
                    @foreach (array_slice($p['votes'], 0, 3) as $vote)
                        @php
                            $camaraUrl = ($vote['session_external_id'] ?? null)
                                ? "https://www.camara.leg.br/plenario/votacao/{$vote['session_external_id']}"
                                : '#';
                        @endphp
                        <a href="{{ $camaraUrl }}" target="_blank" rel="noopener" class="flex items-center gap-3 bg-white border border-zinc-200 rounded-lg px-4 py-3 hover:bg-zinc-50 transition">
                            <div class="w-2 h-2 rounded-full flex-shrink-0
                                {{ $vote['vote'] === 'Sim' ? 'bg-emerald-500' : ($vote['vote'] === 'Não' ? 'bg-red-500' : 'bg-zinc-400') }}">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-zinc-900 line-clamp-1">{{ $vote['bill_title'] }}</p>
                                <p class="text-xs text-zinc-500">{{ $vote['date'] }}</p>
                            </div>
                            <span class="text-xs font-medium
                                {{ $vote['vote'] === 'Sim' ? 'text-emerald-600' : ($vote['vote'] === 'Não' ? 'text-red-600' : 'text-zinc-500') }}">
                                {{ $vote['vote'] }}
                            </span>
                            <svg class="w-4 h-4 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Despesas --}}
        @if ($p['total_expenses'] > 0)
            <div class="mb-8">
                <flux:heading size="lg" level="2">Despesas CEAP</flux:heading>

                {{-- Total --}}
                <div class="mt-3 bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-lg px-5 py-4">
                    <p class="text-xs font-medium text-emerald-700 uppercase">Total de despesas CEAP</p>
                    <p class="text-2xl font-bold text-emerald-900 mt-1">
                        R$ {{ number_format($p['total_expenses'], 2, ',', '.') }}
                    </p>
                </div>

                {{-- Breakdown by type --}}
                @if (count($p['expense_breakdown']) > 0)
                    <div class="mt-4 space-y-2">
                        @php $maxType = max(array_column($p['expense_breakdown'], 'total')); @endphp
                        @foreach ($p['expense_breakdown'] as $item)
                            <div class="bg-white border border-zinc-200 rounded-lg px-4 py-3">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-sm text-zinc-900 font-medium">{{ $item['type'] }}</span>
                                    <span class="text-sm font-mono font-semibold text-zinc-900">
                                        R$ {{ number_format($item['total'], 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="w-full bg-zinc-100 rounded-full h-2">
                                    <div
                                        class="bg-emerald-500 h-2 rounded-full transition-all"
                                        style="width: {{ $maxType > 0 ? ($item['total'] / $maxType * 100) : 0 }}%"
                                    ></div>
                                </div>
                                <p class="text-xs text-zinc-500 mt-1">{{ $item['count'] }} documentos</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Recent expenses --}}
                @if (count($p['expenses']) > 0)
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-zinc-600">Despesas recentes</h3>
                            @if ($p['expenses_count'] > 3)
                                <button wire:click="$dispatch('openDespesasModal')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    Ver todas →
                                </button>
                            @endif
                        </div>
                        <div class="space-y-2">
                            @foreach (array_slice($p['expenses'], 0, 3) as $expense)
                                <div class="flex items-center gap-3 bg-white border border-zinc-200 rounded-lg px-4 py-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-zinc-900 line-clamp-1">
                                            {{ $expense['description'] ?: $expense['type'] }}
                                        </p>
                                        <p class="text-xs text-zinc-500">{{ $expense['date'] }} · {{ $expense['type'] }}</p>
                                    </div>
                                    <span class="text-sm font-mono font-semibold text-zinc-900 flex-shrink-0">
                                        R$ {{ number_format($expense['value'], 2, ',', '.') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Comissões e Órgãos --}}
        @if (count($p['committees']) > 0)
            <div class="mb-8">
                <flux:heading size="lg" level="2">Comissões e Órgãos</flux:heading>
                <div class="mt-3 space-y-2">
                    @foreach (array_slice($p['committees'], 0, 5) as $committee)
                        <div class="flex items-center gap-3 bg-white border border-zinc-200 rounded-lg px-4 py-3">
                            <div class="w-2 h-2 rounded-full bg-violet-500 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-zinc-900 font-medium">
                                    {{ $committee['acronym'] ? $committee['acronym'].' — ' : '' }}{{ $committee['name'] }}
                                </p>
                                <p class="text-xs text-zinc-500">
                                    {{ $committee['role'] ?? 'Membro' }}
                                    @if ($committee['start_date'])
                                        · Desde {{ $committee['start_date'] }}
                                    @endif
                                    · {{ ucfirst($committee['source']) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                    @if (count($p['committees']) > 5)
                        <p class="text-xs text-zinc-500 text-center">+ {{ count($p['committees']) - 5 }} comissões</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Frentes Parlamentares --}}
        @if (count($p['fronts']) > 0)
            <div class="mb-8">
                <flux:heading size="lg" level="2">Frentes Parlamentares</flux:heading>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($p['fronts'] as $front)
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-violet-50 text-violet-700 border border-violet-200">
                            {{ $front['title'] }}
                            @if ($front['legislature'])
                                <span class="text-violet-400">· {{ $front['legislature'] }}ª</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Discursos --}}
        @if (count($p['speeches']) > 0)
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" level="2">Discursos</flux:heading>
                    <span class="text-xs text-zinc-500">{{ $p['speeches_count'] }} no total</span>
                </div>
                <div class="mt-3 space-y-2">
                    @foreach ($p['speeches'] as $speech)
                        <div class="bg-white border border-zinc-200 rounded-lg px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm text-zinc-900 font-medium line-clamp-2">{{ $speech['title'] }}</p>
                                    <p class="text-xs text-zinc-500 mt-1">
                                        {{ $speech['date'] }}
                                        @if ($speech['session_name']) · {{ $speech['session_name'] }} @endif
                                        · {{ $speech['source'] === 'camara' ? 'Câmara' : 'Senado' }}
                                    </p>
                                </div>
                                @if ($speech['uri'])
                                    <a href="{{ $speech['uri'] }}" target="_blank" rel="noopener" class="flex-shrink-0">
                                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Presença / Eventos --}}
        @if (count($p['events']) > 0)
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" level="2">Presença em Eventos</flux:heading>
                    <span class="text-xs text-zinc-500">{{ $p['events_count'] }} no total</span>
                </div>
                <div class="mt-3 space-y-2">
                    @foreach ($p['events'] as $event)
                        <div class="flex items-center gap-3 bg-white border border-zinc-200 rounded-lg px-4 py-3">
                            <div class="w-2 h-2 rounded-full bg-sky-500 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-zinc-900 line-clamp-1">{{ $event['title'] }}</p>
                                <p class="text-xs text-zinc-500">
                                    {{ $event['date'] }}
                                    @if ($event['type']) · {{ $event['type'] }} @endif
                                    @if ($event['location']) · {{ $event['location'] }} @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Lideranças --}}
        @if (count($p['leaderships']) > 0)
            <div class="mb-8">
                <flux:heading size="lg" level="2">Lideranças</flux:heading>
                <div class="mt-3 space-y-2">
                    @foreach ($p['leaderships'] as $leadership)
                        <div class="flex items-center gap-3 bg-white border border-zinc-200 rounded-lg px-4 py-3">
                            <div class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-zinc-900 font-medium">{{ $leadership['position'] }}</p>
                                <p class="text-xs text-zinc-500">
                                    @if ($leadership['party']) {{ $leadership['party'] }} · @endif
                                    @if ($leadership['house']) {{ $leadership['house'] }} · @endif
                                    {{ $leadership['start_date'] ?? '—' }} — {{ $leadership['end_date'] ?? 'Atual' }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Relatorias --}}
        @if (count($p['rapporteurships']) > 0)
            <div class="mb-8">
                <flux:heading size="lg" level="2">Relatorias</flux:heading>
                <div class="mt-3 space-y-2">
                    @foreach (array_slice($p['rapporteurships'], 0, 5) as $rapport)
                        <div class="bg-white border border-zinc-200 rounded-lg px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-sm text-zinc-900 font-medium line-clamp-1">{{ $rapport['bill_description'] ?? '—' }}</p>
                                @if ($rapport['bill_ementa'])
                                    <p class="text-xs text-zinc-500 mt-1 line-clamp-2">{{ $rapport['bill_ementa'] }}</p>
                                @endif
                                <p class="text-xs text-zinc-500 mt-1">
                                    @if ($rapport['commission']) {{ $rapport['commission'] }} · @endif
                                    {{ $rapport['start_date'] ?? '—' }} — {{ $rapport['end_date'] ?? 'Atual' }}
                                    @if ($rapport['removal_reason']) · {{ $rapport['removal_reason'] }} @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                    @if (count($p['rapporteurships']) > 5)
                        <p class="text-xs text-zinc-500 text-center">+ {{ count($p['rapporteurships']) - 5 }} relatorias</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Co-autores --}}
        @if (count($p['coauthors']) > 0)
            <div class="mb-8">
                <flux:heading size="lg" level="2">Co-autores de Proposições</flux:heading>
                <div class="mt-3 space-y-2">
                    @foreach (array_slice($p['coauthors'], 0, 5) as $coauthor)
                        <div class="flex items-center gap-3 bg-white border border-zinc-200 rounded-lg px-4 py-3">
                            <div class="w-2 h-2 rounded-full bg-teal-500 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-zinc-900 line-clamp-1">{{ $coauthor['author_name'] }}</p>
                                <p class="text-xs text-zinc-500">{{ $coauthor['bill_title'] }}</p>
                            </div>
                        </div>
                    @endforeach
                    @if (count($p['coauthors']) > 5)
                        <p class="text-xs text-zinc-500 text-center">+ {{ count($p['coauthors']) - 5 }} co-autores</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Blocos Parlamentares --}}
        @if (count($p['blocs']) > 0)
            <div class="mb-8">
                <flux:heading size="lg" level="2">Blocos Parlamentares</flux:heading>
                <div class="mt-3 space-y-2">
                    @foreach ($p['blocs'] as $bloc)
                        <div class="flex items-center gap-3 bg-white border border-zinc-200 rounded-lg px-4 py-3">
                            <div class="w-2 h-2 rounded-full bg-rose-500 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-zinc-900 font-medium">{{ $bloc['name'] }}</p>
                                <p class="text-xs text-zinc-500">
                                    {{ $bloc['is_federation'] ? 'Federação' : 'Bloco' }}
                                    @if ($bloc['legislature']) · {{ $bloc['legislature'] }}ª Legislatura @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Voltar --}}
        <div class="mt-10 pt-6 border-t border-zinc-200">
            <flux:button wire:navigate href="{{ route('politicos') }}" variant="ghost">
                ← Voltar à lista de políticos
            </flux:button>
        </div>
    @endif

    <div class="border-t border-zinc-200 mt-8 py-6">
        <p class="text-center text-xs text-zinc-500">
            Dados públicos agregados para transparência política · Fontes: Câmara, Senado e TSE
        </p>
    </div>
</section>
</div>
