<div>
@if ($this->politician)
@livewire('politicos.ver-votacoes-modal', ['politicianId' => $this->politician['id']])
@livewire('politicos.ver-proposicoes-modal', ['politicianId' => $this->politician['id']])
@livewire('politicos.ver-despesas-modal', ['politicianId' => $this->politician['id']])
@livewire('politicos.ver-comissoes-modal', ['politicianId' => $this->politician['id']])
@livewire('politicos.ver-frentes-modal', ['politicianId' => $this->politician['id']])
@livewire('politicos.ver-discursos-modal', ['politicianId' => $this->politician['id']])
@livewire('politicos.ver-liderancas-modal', ['politicianId' => $this->politician['id']])
@livewire('politicos.ver-relatorias-modal', ['politicianId' => $this->politician['id']])
@livewire('politicos.ver-coautores-modal', ['politicianId' => $this->politician['id']])
@endif

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

        {{-- BLOCO 1: IDENTIDADE --}}
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
                <flux:heading size="xl" level="1">
                    {{ $p['nome_urna'] ?? $p['name'] }}
                </flux:heading>
                @if ($p['nome_urna'] && $p['nome_urna'] !== $p['name'])
                    <p class="text-xs text-zinc-400 mt-0.5">Nome civil: {{ $p['name'] }}</p>
                @endif
                <p class="text-sm text-zinc-500 mt-1 font-mono">
                    {{ $p['party'] }} — {{ $p['state'] }} · {{ $p['position'] }}
                </p>
                <p class="text-xs text-zinc-400 mt-0.5">
                    Situação: <span class="font-medium text-zinc-600">Em exercício</span>
                    · Mandato: 2023–2026
                </p>

                {{-- Redes sociais --}}
                <div class="flex items-center gap-3 mt-2 flex-wrap">
                    @if ($p['email'])
                        <a href="mailto:{{ $p['email'] }}" class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1" title="{{ $p['email'] }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Email
                        </a>
                    @endif
                    @foreach ($p['social_media'] as $social)
                        @if ($social['platform'] === 'twitter')
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                X
                            </a>
                        @endif
                        @if ($social['platform'] === 'facebook')
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook
                            </a>
                        @endif
                        @if ($social['platform'] === 'instagram')
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                Instagram
                            </a>
                        @endif
                        @if ($social['platform'] === 'tiktok')
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                TikTok
                            </a>
                        @endif
                        @if ($social['platform'] === 'youtube')
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                YouTube
                            </a>
                        @endif
                    @endforeach
                </div>
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

        {{-- BLOCO 2: LEITURA RÁPIDA --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-8">
            <div class="bg-zinc-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-zinc-900">{{ $p['bills_count'] }}</p>
                <p class="text-xs text-zinc-500 mt-0.5">Projetos apresentados</p>
            </div>
            <div class="bg-zinc-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-zinc-900">{{ $p['votes_count'] }}</p>
                <p class="text-xs text-zinc-500 mt-0.5">Votos registrados</p>
            </div>
            <div class="bg-zinc-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-zinc-900">
                    @if ($p['total_expenses'] > 0)
                        R$ {{ number_format($p['total_expenses'] / 1000, 0, ',', '.') }}k
                    @else
                        —
                    @endif
                </p>
                <p class="text-xs text-zinc-500 mt-0.5">Gastos CEAP</p>
            </div>
            <div class="bg-zinc-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-zinc-900">
                    @if ($p['votes_count'] > 0)
                        {{ $p['votes_count'] }}
                    @else
                        —
                    @endif
                </p>
                <p class="text-xs text-zinc-500 mt-0.5">Votos registrados</p>
            </div>
            <div class="bg-zinc-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-zinc-900">{{ $p['committees_count'] }}</p>
                <p class="text-xs text-zinc-500 mt-0.5">Comissões</p>
            </div>
            <div class="bg-zinc-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-zinc-900">{{ $p['badges_count'] }}</p>
                <p class="text-xs text-zinc-500 mt-0.5">Badges</p>
            </div>
        </div>

        {{-- BLOCO 3: BADGES --}}
        @if (count($p['badges']) > 0)
            <div class="mb-8">
                <div class="flex flex-wrap gap-2">
                    @foreach ($p['badges'] as $badge)
                        <div
                            class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full cursor-pointer hover:opacity-80 transition"
                            style="background-color: {{ $badge['color'] ? $badge['color'] . '20' : '#ecfdf5' }}; color: {{ $badge['color'] ?? '#047857' }}"
                            title="{{ $badge['description'] ?? $badge['name'] }}"
                        >
                            {{ $badge['name'] }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- BLOCO 4: TRAJETÓRIA --}}
        @if (count($p['mandates']) > 0)
            <div class="mb-4 border border-zinc-200 rounded-lg overflow-hidden">
                <button wire:click="toggleTrajetoria" class="w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-zinc-50 transition">
                    <span class="text-sm font-semibold text-zinc-900">Trajetória</span>
                    <svg class="w-4 h-4 text-zinc-500 transition-transform {{ $this->showTrajetoria ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                @if ($this->showTrajetoria)
                    <div class="px-4 pb-4 border-t border-zinc-100">
                        <div class="mt-3 space-y-3">
                            @foreach ($p['mandates'] as $mandate)
                                <div class="flex items-start gap-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0"></div>
                                    <div>
                                        <p class="text-sm text-zinc-900 font-medium">{{ $mandate['position'] }}</p>
                                        <p class="text-xs text-zinc-500">
                                            {{ $mandate['started_at'] }} — {{ $mandate['ended_at'] ?? 'Em exercício' }}
                                        </p>
                                        @if ($mandate['salary'])
                                            <p class="text-xs text-zinc-400 mt-0.5">
                                                Salário: R$ {{ number_format($mandate['salary'], 2, ',', '.') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-zinc-400 mt-3">Fonte: Câmara dos Deputados / Senado Federal</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- BLOCO 5: ATIVIDADE LEGISLATIVA --}}
        <div class="mb-4 border border-zinc-200 rounded-lg overflow-hidden">
            <button wire:click="toggleAtividade" class="w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-zinc-50 transition">
                <span class="text-sm font-semibold text-zinc-900">Atividade Legislativa</span>
                <svg class="w-4 h-4 text-zinc-500 transition-transform {{ $this->showAtividade ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            @if ($this->showAtividade)
                <div class="px-4 pb-4 border-t border-zinc-100">
                    {{-- Proposições --}}
                    <div class="mt-3">
                        <p class="text-xs font-medium text-zinc-500 uppercase mb-2">Proposições</p>
                        @if (count($p['bills']) > 0)
                            <div class="space-y-2">
                                @foreach ($p['bills'] as $bill)
                                    <a href="https://www.camara.leg.br/proposicoesWeb/fichadetramitacao?idProposicao={{ $bill['external_id'] }}" target="_blank" rel="noopener" class="block bg-white border border-zinc-200 rounded-lg px-3 py-2 hover:bg-zinc-50 transition">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="text-sm text-zinc-900 line-clamp-1">{{ $bill['title'] }}</p>
                                                @if ($bill['latest_progress'])
                                                    <p class="text-[11px] text-zinc-400 mt-0.5">{{ $bill['latest_progress'] }}</p>
                                                @endif
                                            </div>
                                            <span class="text-xs bg-zinc-100 text-zinc-600 px-2 py-0.5 rounded-full flex-shrink-0">{{ $bill['year'] }}</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                            @if ($p['bills_count'] > 3)
                                <button wire:click="$dispatch('openProposicoesModal')" class="text-xs text-blue-600 hover:text-blue-800 font-medium mt-2">
                                    Ver todas as proposições →
                                </button>
                            @endif
                        @else
                            <p class="text-sm text-zinc-400">Nenhuma proposição encontrada.</p>
                        @endif
                    </div>

                    {{-- Votos --}}
                    <div class="mt-4">
                        <p class="text-xs font-medium text-zinc-500 uppercase mb-2">Votos</p>
                        @if (count($p['votes']) > 0)
                            <div class="space-y-2">
                                @foreach ($p['votes'] as $vote)
                                    @php
                                        $extId = $vote['session_external_id'] ?? '';
                                        $votacaoId = explode('-', $extId)[0];
                                        $camaraUrl = $votacaoId
                                            ? "https://www.camara.leg.br/plenario/votacao/{$votacaoId}"
                                            : '#';
                                        $voteTitle = $vote['bill_title']
                                            ?? preg_replace('/\s*Sim:\s*\?.*$/u', '', $vote['session_description'] ?? '')
                                            ?? 'Votação';
                                    @endphp
                                    <a href="{{ $camaraUrl }}" target="_blank" rel="noopener" class="flex items-center gap-2 bg-white border border-zinc-200 rounded-lg px-3 py-2 hover:bg-zinc-50 transition">
                                        <div class="w-2 h-2 rounded-full flex-shrink-0
                                            {{ $vote['vote'] === 'Sim' ? 'bg-emerald-500' : ($vote['vote'] === 'Não' ? 'bg-red-500' : 'bg-zinc-400') }}">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-zinc-900 line-clamp-1">{{ $voteTitle }}</p>
                                            <p class="text-xs text-zinc-500">{{ $vote['date'] }}</p>
                                        </div>
                                        <span class="text-xs font-medium
                                            {{ $vote['vote'] === 'Sim' ? 'text-emerald-600' : ($vote['vote'] === 'Não' ? 'text-red-600' : 'text-zinc-500') }}">
                                            {{ $vote['vote'] }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                            @if ($p['votes_count'] > 3)
                                <button wire:click="$dispatch('openVotacoesModal')" class="text-xs text-blue-600 hover:text-blue-800 font-medium mt-2">
                                    Ver todos os votos →
                                </button>
                            @endif
                        @else
                            <p class="text-sm text-zinc-400">Nenhum voto registrado.</p>
                        @endif
                    </div>

                    <p class="text-[10px] text-zinc-400 mt-3">Fonte: Câmara dos Deputados</p>
                </div>
            @endif
        </div>

        {{-- BLOCO 6: DESPESAS --}}
        @if ($p['total_expenses'] > 0)
            <div class="mb-4 border border-zinc-200 rounded-lg overflow-hidden">
                <button wire:click="toggleDespesas" class="w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-zinc-50 transition">
                    <span class="text-sm font-semibold text-zinc-900">
                        Despesas (CEAP)
                        <span class="text-zinc-400 font-normal">· R$ {{ number_format($p['total_expenses'], 2, ',', '.') }}</span>
                    </span>
                    <svg class="w-4 h-4 text-zinc-500 transition-transform {{ $this->showDespesas ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                @if ($this->showDespesas)
                    <div class="px-4 pb-4 border-t border-zinc-100">
                        <div class="mt-3 bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-lg px-4 py-3">
                            <p class="text-xs font-medium text-emerald-700 uppercase">Total de despesas CEAP</p>
                            <p class="text-xl font-bold text-emerald-900 mt-1">
                                R$ {{ number_format($p['total_expenses'], 2, ',', '.') }}
                            </p>
                            @if ($p['bancada_avg'])
                                <p class="text-xs text-emerald-600 mt-1">
                                    Média da bancada: R$ {{ number_format($p['bancada_avg'], 2, ',', '.') }}
                                </p>
                            @endif
                        </div>

                        @if (count($p['expense_breakdown']) > 0)
                            <div class="mt-3 space-y-2">
                                @php $maxType = max(array_column($p['expense_breakdown'], 'total')); @endphp
                                @foreach (array_slice($p['expense_breakdown'], 0, 5) as $item)
                                    <div class="bg-white border border-zinc-200 rounded-lg px-3 py-2">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm text-zinc-900">{{ $item['type'] }}</span>
                                            <span class="text-sm font-mono font-semibold text-zinc-900">
                                                R$ {{ number_format($item['total'], 2, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="w-full bg-zinc-100 rounded-full h-1.5">
                                            <div
                                                class="bg-emerald-500 h-1.5 rounded-full transition-all"
                                                style="width: {{ $maxType > 0 ? ($item['total'] / $maxType * 100) : 0 }}%"
                                            ></div>
                                        </div>
                                        <p class="text-[11px] text-zinc-400 mt-1">{{ $item['count'] }} documentos</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (count($p['expenses']) > 0)
                            <div class="mt-3">
                                <p class="text-xs font-medium text-zinc-500 mb-2">Despesas recentes</p>
                                <div class="space-y-1">
                                    @foreach ($p['expenses'] as $expense)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-zinc-600">{{ $expense['date'] }} · {{ $expense['description'] ?: $expense['type'] }}</span>
                                            <span class="font-mono font-semibold text-zinc-900">R$ {{ number_format($expense['value'], 2, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($p['expenses_count'] > 3)
                                    <button wire:click="$dispatch('openDespesasModal')" class="text-xs text-blue-600 hover:text-blue-800 font-medium mt-2">
                                        Ver todas as despesas →
                                    </button>
                                @endif
                            </div>
                        @endif

                        <p class="text-[10px] text-zinc-400 mt-3">Fonte: Câmara dos Deputados — CEAP</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- BLOCO 7: FINANCIAMENTO DE CAMPANHA --}}
        @if ($p['total_campanha'] > 0)
            <div class="mb-4 border border-zinc-200 rounded-lg overflow-hidden">
                <button wire:click="toggleFinanciamento" class="w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-zinc-50 transition">
                    <span class="text-sm font-semibold text-zinc-900">Financiamento de Campanha</span>
                    <svg class="w-4 h-4 text-zinc-500 transition-transform {{ $this->showFinanciamento ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                @if ($this->showFinanciamento)
                    <div class="px-4 pb-4 border-t border-zinc-100">
                        <div class="mt-3">
                            <p class="text-xs text-zinc-500">Total arrecadado</p>
                            <p class="text-xl font-bold text-zinc-900">R$ {{ number_format($p['total_campanha'], 2, ',', '.') }}</p>
                        </div>

                        @if (count($p['campaign_financings']) > 0)
                            <div class="mt-3 space-y-1">
                                @foreach ($p['campaign_financings'] as $fonte)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-600">{{ $fonte['type'] }}</span>
                                        <span class="font-mono font-semibold text-zinc-900">R$ {{ number_format($fonte['total'], 2, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (count($p['doadores']) > 0)
                            <div class="mt-3">
                                <p class="text-xs font-medium text-zinc-500 mb-2">Principais doadores</p>
                                <div class="space-y-1">
                                    @foreach ($p['doadores'] as $doador)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-zinc-600">{{ $doador['name'] }} ({{ $doador['type'] }})</span>
                                            <span class="font-mono font-semibold text-zinc-900">R$ {{ number_format($doador['value'], 2, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <p class="text-[10px] text-zinc-400 mt-3">Fonte: TSE — Prestação de Contas</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- BLOCO 8: COMISSÕES E ATUAÇÃO --}}
        @if (count($p['committees']) > 0 || count($p['fronts']) > 0 || count($p['leaderships']) > 0)
            <div class="mb-4 border border-zinc-200 rounded-lg overflow-hidden">
                <button wire:click="toggleComissoes" class="w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-zinc-50 transition">
                    <span class="text-sm font-semibold text-zinc-900">Comissões e Atuação</span>
                    <svg class="w-4 h-4 text-zinc-500 transition-transform {{ $this->showComissoes ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                @if ($this->showComissoes)
                    <div class="px-4 pb-4 border-t border-zinc-100">
                        @if (count($p['committees']) > 0)
                            <div class="mt-3">
                                <p class="text-xs font-medium text-zinc-500 uppercase mb-2">Comissões</p>
                                <div class="space-y-2">
                                    @foreach (array_slice($p['committees'], 0, 5) as $committee)
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full bg-violet-500 flex-shrink-0"></div>
                                            <div>
                                                <p class="text-sm text-zinc-900">
                                                    {{ $committee['acronym'] ? $committee['acronym'].' — ' : '' }}{{ $committee['name'] }}
                                                </p>
                                                <p class="text-xs text-zinc-500">
                                                    {{ $committee['role'] ?? 'Membro' }}
                                                    @if ($committee['start_date']) · Desde {{ $committee['start_date'] }} @endif
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (count($p['fronts']) > 0)
                            <div class="mt-3">
                                <p class="text-xs font-medium text-zinc-500 uppercase mb-2">Frentes Parlamentares</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($p['fronts'] as $front)
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-violet-50 text-violet-700 border border-violet-200">
                                            {{ $front['title'] }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (count($p['leaderships']) > 0)
                            <div class="mt-3">
                                <p class="text-xs font-medium text-zinc-500 uppercase mb-2">Lideranças</p>
                                <div class="space-y-1">
                                    @foreach ($p['leaderships'] as $leadership)
                                        <div class="text-sm">
                                            <span class="text-zinc-900 font-medium">{{ $leadership['position'] }}</span>
                                            <span class="text-zinc-500">
                                                · {{ $leadership['house'] }}
                                                · {{ $leadership['start_date'] ?? '—' }} — {{ $leadership['end_date'] ?? 'Atual' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (count($p['rapporteurships']) > 0)
                            <div class="mt-3">
                                <p class="text-xs font-medium text-zinc-500 uppercase mb-2">Relatorias</p>
                                <div class="space-y-1">
                                    @foreach (array_slice($p['rapporteurships'], 0, 3) as $rapport)
                                        <div class="text-sm">
                                            <span class="text-zinc-900">{{ $rapport['bill_description'] ?? '—' }}</span>
                                            <span class="text-zinc-500"> · {{ $rapport['commission'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <p class="text-[10px] text-zinc-400 mt-3">Fonte: Câmara dos Deputados / Senado Federal</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- BLOCO 9: BENS DECLARADOS --}}
        @if (count($p['asset_declarations']) > 0)
            <div class="mb-4 border border-zinc-200 rounded-lg overflow-hidden">
                <button wire:click="toggleBens" class="w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-zinc-50 transition">
                    <span class="text-sm font-semibold text-zinc-900">Bens Declarados</span>
                    <svg class="w-4 h-4 text-zinc-500 transition-transform {{ $this->showBens ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                @if ($this->showBens)
                    <div class="px-4 pb-4 border-t border-zinc-100">
                        <div class="mt-3 space-y-1">
                            @foreach ($p['asset_declarations'] as $asset)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600">{{ $asset['year'] }} — {{ $asset['description'] }}</span>
                                    <span class="font-mono font-semibold text-zinc-900">R$ {{ number_format($asset['value'], 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-zinc-400 mt-3">
                            ⚠️ Dados referentes aos anos eleitorais. Não representa série contínua.
                        </p>
                        <p class="text-[10px] text-zinc-400">Fonte: TSE — Declaração de Bens</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- BLOCO 10: PROCESSOS E INTEGRIDADE --}}
        @if (count($p['legal_proceedings']) > 0)
            <div class="mb-4 border border-zinc-200 rounded-lg overflow-hidden">
                <button wire:click="toggleProcessos" class="w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-zinc-50 transition">
                    <span class="text-sm font-semibold text-zinc-900">Processos e Integridade</span>
                    <svg class="w-4 h-4 text-zinc-500 transition-transform {{ $this->showProcessos ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                @if ($this->showProcessos)
                    <div class="px-4 pb-4 border-t border-zinc-100">
                        <div class="mt-3 space-y-2">
                            @foreach ($p['legal_proceedings'] as $process)
                                <div class="bg-white border border-zinc-200 rounded-lg px-3 py-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-zinc-900 font-medium">{{ $process['process_number'] }}</span>
                                        <span class="text-xs bg-zinc-100 text-zinc-600 px-2 py-0.5 rounded-full">{{ $process['status'] }}</span>
                                    </div>
                                    <p class="text-xs text-zinc-500 mt-0.5">{{ $process['court'] }}</p>
                                    @if ($process['description'])
                                        <p class="text-xs text-zinc-400 mt-0.5">{{ $process['description'] }}</p>
                                    @endif
                                    @if ($process['source_url'])
                                        <a href="{{ $process['source_url'] }}" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:text-blue-800 mt-1 inline-flex items-center gap-1">
                                            Ver no tribunal →
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-zinc-400 mt-3">
                            ⚠️ Status, não veredito. Cada processo linka à fonte oficial para verificação.
                        </p>
                    </div>
                @endif
            </div>
        @endif

        {{-- BLOCO 11: DADOS PESSOAIS --}}
        <div class="mb-4 border border-zinc-200 rounded-lg overflow-hidden">
            <button wire:click="toggleDadosPessoais" class="w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-zinc-50 transition">
                <span class="text-sm font-semibold text-zinc-900">Dados Pessoais</span>
                <svg class="w-4 h-4 text-zinc-500 transition-transform {{ $this->showDadosPessoais ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            @if ($this->showDadosPessoais)
                <div class="px-4 pb-4 border-t border-zinc-100">
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        @if ($p['education'])
                            <div>
                                <p class="text-xs text-zinc-500">Escolaridade</p>
                                <p class="text-sm text-zinc-900">{{ $p['education'] }}</p>
                            </div>
                        @endif
                        @if ($p['birth_date'])
                            <div>
                                <p class="text-xs text-zinc-500">Nascimento</p>
                                <p class="text-sm text-zinc-900">{{ $p['birth_date'] }}</p>
                            </div>
                        @endif
                        @if ($p['declared_profession'])
                            <div>
                                <p class="text-xs text-zinc-500">Profissão</p>
                                <p class="text-sm text-zinc-900">{{ $p['declared_profession'] }}</p>
                            </div>
                        @endif
                        @if ($p['uf_birth'] || $p['municipality_birth'])
                            <div>
                                <p class="text-xs text-zinc-500">Naturalidade</p>
                                <p class="text-sm text-zinc-900">
                                    {{ $p['municipality_birth'] }}{{ $p['uf_birth'] ? ', '.$p['uf_birth'] : '' }}
                                </p>
                            </div>
                        @endif
                    </div>
                    <p class="text-[10px] text-zinc-400 mt-3">Fonte: Câmara dos Deputados</p>
                </div>
            @endif
        </div>

        {{-- BLOCO 12: FONTES --}}
        <div class="mt-8 pt-6 border-t border-zinc-200">
            <p class="text-center text-xs text-zinc-500">
                Dados públicos agregados para transparência política
            </p>
            <p class="text-center text-[10px] text-zinc-400 mt-1">
                Fontes: Câmara dos Deputados · Senado Federal · TSE · Diários Oficiais
            </p>
            <div class="flex justify-center mt-4">
                <flux:button wire:navigate href="{{ route('politicos') }}" variant="ghost">
                    ← Voltar à lista de políticos
                </flux:button>
            </div>
        </div>
    @endif
</section>
</div>
