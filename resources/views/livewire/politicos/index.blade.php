<section style="padding:40px 0 80px;">

    {{-- Diretório (dentro do mc-page, margem 100px) --}}
    <div class="mc-page">
        <div style="text-align:center;margin-bottom:24px;">
            <h1 class="mc-h1" style="margin-bottom:8px;">Quem representa você</h1>
            <p class="mc-eyebrow" style="justify-content:center;margin:0;"><span class="mc-dot"></span>meu-candidato</p>
        </div>

        <div class="mc-toggle">
            <button wire:click="toggleTab('candidatos')" class="{{ $activeTab === 'candidatos' ? 'active' : '' }}">Candidatos</button>
            <button wire:click="toggleTab('eleitos')" class="{{ $activeTab === 'eleitos' ? 'active' : '' }}">Eleitos</button>
        </div>

        <div class="mc-filters" style="justify-content:center;">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nome"
            />
            <select wire:model.live="selectedState">
                <option value="">Todos os estados</option>
                @foreach ($this->states as $state)
                    <option value="{{ $state }}">{{ $state }}</option>
                @endforeach
            </select>
            <select wire:model.live="selectedParty">
                <option value="">Todos os partidos</option>
                @foreach ($this->parties as $party)
                    <option value="{{ $party }}">{{ $party }}</option>
                @endforeach
            </select>
            <select wire:model.live="selectedPosition">
                <option value="">Todos os cargos</option>
                @foreach ($this->positions as $position)
                    <option value="{{ $position }}">{{ $position }}</option>
                @endforeach
            </select>
        </div>

        @if ($this->hasFilters)
            <div style="text-align:center;">
                <button wire:click="clearFilters" style="font-size:12px;color:var(--seal);background:none;border:none;cursor:pointer;padding:0;margin-bottom:10px;font-family:'Inter',sans-serif;">limpar filtros</button>
            </div>
        @endif

        {{-- Trending / Em alta (após filtros, só na aba eleitos) --}}
        @if ($activeTab === 'eleitos' && !empty($trending) && count($trending) > 0)
            @include('livewire.politicos.partials.trending-carousel', ['trending' => $trending])
        @endif

        @if ($activeTab === 'candidatos')
            @if ($politicians->isEmpty())
                <div style="text-align:center;padding:60px 0;">
                    <p style="font-size:14px;color:var(--ink-faint);">Nenhum candidato encontrado com os filtros selecionados.</p>
                    <button wire:click="clearFilters" style="font-size:13px;color:var(--seal);background:none;border:none;cursor:pointer;padding:8px 0;font-family:'Inter',sans-serif;">limpar filtros</button>
                </div>
            @else
                <div class="mc-dir-grid">
                    @foreach ($politicians as $politician)
                        <a href="{{ route('politicos.show', $politician->id) }}" wire:navigate class="mc-dir-card" wire:key="{{ $politician->id }}">
                            <div class="mc-avatar-dir">
                                @if ($politician->photo_url)
                                    <img
                                        src="{{ $politician->photo_url }}"
                                        alt="{{ $politician->name }}"
                                        loading="lazy"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                    />
                                    <div style="display:none;align-items:center;justify-content:center;width:100%;height:100%;">
                                        {{ strtoupper(mb_substr($politician->name, 0, 2)) }}
                                    </div>
                                @else
                                    {{ strtoupper(mb_substr($politician->name, 0, 2)) }}
                                @endif
                            </div>
                            <div class="mc-dir-name">{{ $politician->nome_urna ?? $politician->name }}</div>
                            <div class="mc-dir-meta">{{ $politician->party?->acronym ?? 'S/' }} · {{ $politician->latestAddress?->uf ?? '—' }}</div>
                            <div class="mc-dir-position">{{ strtolower($politician->position ?? '—') }}</div>
                        </a>
                    @endforeach
                </div>

                <div style="margin-top:20px;">
                    {{ $politicians->links() }}
                </div>
            @endif
        @else
            @if ($politicians->isEmpty())
                <div style="text-align:center;padding:60px 0;">
                    <p style="font-size:14px;color:var(--ink-faint);">Nenhum político encontrado com os filtros selecionados.</p>
                    <button wire:click="clearFilters" style="font-size:13px;color:var(--seal);background:none;border:none;cursor:pointer;padding:8px 0;font-family:'Inter',sans-serif;">limpar filtros</button>
                </div>
            @else
                <div class="mc-dir-grid">
                    @foreach ($politicians as $politician)
                        <a href="{{ route('politicos.show', $politician->id) }}" wire:navigate class="mc-dir-card" wire:key="{{ $politician->id }}">
                            <div class="mc-avatar-dir">
                                @if ($politician->photo_url)
                                    <img
                                        src="{{ $politician->photo_url }}"
                                        alt="{{ $politician->name }}"
                                        loading="lazy"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                    />
                                    <div style="display:none;align-items:center;justify-content:center;width:100%;height:100%;">
                                        {{ strtoupper(mb_substr($politician->name, 0, 2)) }}
                                    </div>
                                @else
                                    {{ strtoupper(mb_substr($politician->name, 0, 2)) }}
                                @endif
                            </div>
                            <div class="mc-dir-name">{{ $politician->nome_urna ?? $politician->name }}</div>
                            <div class="mc-dir-meta">{{ $politician->party?->acronym ?? 'S/' }} · {{ $politician->latestAddress?->uf ?? '—' }}</div>
                            <div class="mc-dir-position">{{ strtolower($politician->position ?? '—') }}{{ $politician->mandates->firstWhere('ended_at', null) ? ' (em exercício)' : '' }}</div>
                        </a>
                    @endforeach
                </div>

                <div style="margin-top:20px;">
                    {{ $politicians->links() }}
                </div>
            @endif
        @endif
    </div>
</section>