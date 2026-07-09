<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl" level="1">Políticos</flux:heading>
        <flux:subheading size="lg">Busque e filtre políticos por partido, cargo e estado</flux:subheading>
        <flux:separator variant="subtle" class="mt-4" />
    </div>

    <div class="flex gap-6 max-lg:flex-col">
        {{-- Sidebar de filtros --}}
        <aside class="w-full lg:w-60 xl:w-64 flex-shrink-0">
            <div class="space-y-5 lg:sticky lg:top-20">
                {{-- Busca --}}
                <div>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por nome..."
                        icon="magnifying-glass"
                    />
                </div>

                @if ($this->hasFilters)
                    <flux:button variant="ghost" size="sm" wire:click="clearFilters" class="w-full">
                        <flux:icon.x-mark class="w-3 h-3" />
                        Limpar filtros
                    </flux:button>
                @endif

                {{-- Cargo --}}
                <div>
                    <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Cargo</p>
                    <div class="space-y-1.5">
                        @foreach ($this->positions as $position)
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    wire:change="togglePosition(@js($position))"
                                    @checked(in_array($position, $this->selectedPositions))
                                    class="w-3.5 h-3.5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span class="text-xs text-zinc-700 group-hover:text-emerald-600 transition-colors">
                                    {{ $position }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Partido --}}
                <div>
                    <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Partido</p>
                    <div class="space-y-1.5">
                        @foreach ($this->parties as $party)
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    wire:change="toggleParty(@js($party))"
                                    @checked(in_array($party, $this->selectedParties))
                                    class="w-3.5 h-3.5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span class="text-xs text-zinc-700 group-hover:text-emerald-600 transition-colors">
                                    {{ $party }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Estado --}}
                <div>
                    <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Estado</p>
                    <flux:select wire:model.live="selectedState">
                        <flux:select.option value="">Todos os estados</flux:select.option>
                        @foreach ($this->states as $state)
                            <flux:select.option value="{{ $state }}">{{ $state }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </aside>

        {{-- Conteúdo principal --}}
        <main class="flex-1 min-w-0">
            <div class="mb-5">
                <p class="text-sm text-zinc-500">
                    <span class="font-semibold text-zinc-900">{{ $politicians->total() }}</span>
                    político{{ $politicians->total() !== 1 ? 's' : '' }}
                    encontrado{{ $politicians->total() !== 1 ? 's' : '' }}
                </p>
            </div>

            @if ($politicians->isEmpty())
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <p class="text-zinc-500 text-sm">Nenhum político encontrado com os filtros selecionados.</p>
                    <flux:button variant="ghost" size="sm" wire:click="clearFilters" class="mt-3">
                        Limpar filtros
                    </flux:button>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($politicians as $politician)
                        <div wire:key="{{ $politician->id }}" class="bg-white border border-zinc-200 rounded-lg flex flex-col transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                            {{-- Header: foto + identidade --}}
                            <div class="p-4 flex items-start gap-3">
                                <div class="w-14 h-14 rounded-lg overflow-hidden flex-shrink-0 bg-zinc-100">
                                    @if ($politician->photo_url)
                                        <img
                                            src="{{ $politician->photo_url }}"
                                            alt="{{ $politician->name }}"
                                            class="w-full h-full object-cover"
                                            loading="lazy"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                        />
                                        <div class="w-full h-full flex items-center justify-center bg-zinc-200 text-zinc-500 text-lg font-bold" style="display: none;">
                                            {{ strtoupper(mb_substr($politician->name, 0, 2)) }}
                                        </div>
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-zinc-200 text-zinc-500 text-lg font-bold">
                                            {{ strtoupper(mb_substr($politician->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-sm font-semibold text-zinc-900 leading-snug line-clamp-2">
                                        {{ $politician->name }}
                                    </h2>
                                    <p class="text-xs text-zinc-500 mt-0.5 font-mono">
                                        {{ $politician->party?->acronym ?? 'S/' }} · {{ $politician->latestAddress?->uf ?? '—' }}
                                    </p>
                                    <p class="text-xs text-zinc-500">{{ $politician->position ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="border-t border-zinc-200"></div>

                            {{-- Conteúdo --}}
                            <div class="flex-1 flex flex-col p-4 gap-3">
                                @php
                                    $ultimoMandato = $politician->mandates->sortByDesc('started_at')->first();
                                    $ultimoBill = $politician->bills->sortByDesc('year')->first();
                                @endphp

                                {{-- Mandato --}}
                                @if ($ultimoMandato)
                                    <div>
                                        <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">Mandato</span>
                                        <p class="text-xs text-zinc-700 mt-0.5">
                                            {{ $ultimoMandato->started_at?->format('d/m/Y') }} — {{ $ultimoMandato->ended_at?->format('d/m/Y') ?? 'Em exercício' }}
                                        </p>
                                    </div>
                                @endif

                                {{-- Escolaridade --}}
                                @if ($politician->education)
                                    <div>
                                        <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">Escolaridade</span>
                                        <p class="text-xs text-zinc-700 mt-0.5">
                                            {{ $politician->education }}
                                        </p>
                                    </div>
                                @endif

                                {{-- PL recente --}}
                                @if ($ultimoBill)
                                    <div>
                                        <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">Proposição recente</span>
                                        <p class="text-xs text-zinc-700 mt-0.5 leading-relaxed">
                                            {{ $ultimoBill->title }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            {{-- Botão --}}
                            <div class="p-4 pt-0">
                                <flux:button variant="primary" class="w-full" size="sm" wire:navigate href="{{ route('politicos.show', $politician->id) }}">
                                    VER PERFIL
                                </flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $politicians->links() }}
                </div>
            @endif
        </main>
    </div>

    <div class="border-t border-zinc-200 mt-12 py-6">
        <p class="text-center text-xs text-zinc-500">
            Dados públicos agregados para transparência política · Fontes: Câmara, Senado e TSE
        </p>
    </div>
</section>
