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

<section style="min-height:100vh;padding:48px 20px 80px;">
    @if ($this->politician === null)
        <div class="mc-page-center" style="text-align:center;padding:96px 0;">
            <h1 class="mc-h1">Político não encontrado</h1>
            <p style="font-size:14px;color:var(--ink-soft);margin-top:8px;">O ID informado não corresponde a nenhum político cadastrado.</p>
            <a href="{{ route('politicos') }}" style="display:inline-block;margin-top:24px;padding:8px 16px;background:var(--ink);color:white;border-radius:6px;text-decoration:none;font-size:13px;">Voltar à lista</a>
        </div>
    @else
        @php $p = $this->politician; $cargo = $this->cargo; @endphp

        <div class="mc-page-center">

            {{-- EYEBROW --}}
            <p class="mc-eyebrow"><span class="mc-dot"></span>meu-candidato · perfil verificado</p>

            {{-- HEADER --}}
            <div class="mc-header">
                <div class="mc-header-top">
                    <div class="mc-avatar">
                        @if ($p['photo'])
                            <img src="{{ $p['photo'] }}" alt="{{ $p['name'] }}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
                            <div style="display:none;align-items:center;justify-content:center;width:100%;height:100%;">
                                {{ strtoupper(mb_substr($p['nome_urna'] ?? $p['name'], 0, 2)) }}
                            </div>
                        @else
                            {{ strtoupper(mb_substr($p['nome_urna'] ?? $p['name'], 0, 2)) }}
                        @endif
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="mc-name-row">
                            <h1 class="mc-h1">{{ $p['nome_urna'] ?? $p['name'] }}</h1>
                        </div>
                        @if ($p['nome_urna'] && $p['nome_urna'] !== $p['name'])
                            <p class="mc-civil-name">Nome civil: {{ $p['name'] }}</p>
                        @endif
                        <p class="mc-meta-line">
                            <b>{{ $p['party'] }}</b> · <b>{{ $cargo === 'presidente' || $cargo === 'vice' ? 'Brasil' : $p['state'] }}</b> · {{ $p['position'] }} · em exercício · mandato {{ $p['mandate_period'] ?? '—' }}
                        </p>
                    </div>
                </div>

                <p class="mc-mandate-line">
                    <span class="mc-icon">→</span>{{ $p['mandate_description'] }}
                </p>

                <div class="mc-social-row">
                    @if ($p['social_media_twitter'])
                        <a href="{{ $p['social_media_twitter'] }}" target="_blank" rel="noopener" style="text-decoration:none;color:inherit;">𝕏 seguir</a>
                    @endif
                    @if ($p['social_media_instagram'])
                        <a href="{{ $p['social_media_instagram'] }}" target="_blank" rel="noopener" style="text-decoration:none;color:inherit;">◎ instagram</a>
                    @endif
                    <span class="mc-follow">{{ number_format($this->followersCount) }} seguidores</span>
                </div>
            </div>

            {{-- TCU CARD (presidente) --}}
            @if ($cargo === 'presidente')
                <div class="mc-tcu-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
                    <div>
                        <p class="mc-tcu-label">Prestação de contas · TCU</p>
                        @if ($p['tcu_parecer'])
                            <p class="mc-tcu-value">{{ $p['tcu_parecer'] }}</p>
                            <p class="mc-tcu-meta">Exercício {{ $p['tcu_ano'] }} · parecer emitido em {{ ($p['tcu_ano'] ?? 0) + 1 }}</p>
                        @else
                            <p class="mc-tcu-value" style="color:var(--ink-faint);">Não disponível</p>
                            <p class="mc-tcu-meta">Dados do TCU ainda não importados</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ATUAÇÃO NO MANDATO --}}
            @php
                $secoesAtuacao = match($cargo) {
                    'deputado' => [
                        ['key' => 'projetos', 'title' => 'Projetos de lei', 'preview' => $p['bills_count'].' apresentados em '.$p['last_bill_year'] ?? '—', 'icon' => 'seal', 'has_data' => $p['bills_count'] > 0],
                        ['key' => 'votos', 'title' => 'Votos e presença', 'preview' => $p['votes_count'].' votos registrados'.($p['presenca_percentual'] ? ' · presença em '.$p['presenca_percentual'].'% das sessões' : ''), 'icon' => 'seal', 'has_data' => $p['votes_count'] > 0],
                        ['key' => 'comissoes', 'title' => 'Comissões e frentes parlamentares', 'preview' => 'Membro de '.$p['committees_count'].' comissões · '.$p['fronts_total'].' frentes', 'icon' => 'neutral', 'has_data' => $p['committees_count'] > 0 || $p['fronts_total'] > 0],
                        ['key' => 'fiscalizacao', 'title' => 'Fiscalização do governo', 'preview' => $p['speeches_count'].' discursos registrados', 'icon' => 'neutral', 'has_data' => $p['speeches_count'] > 0],
                    ],
                    'senador' => [
                        ['key' => 'projetos', 'title' => 'Projetos de lei', 'preview' => $p['bills_count'].' apresentados', 'icon' => 'seal', 'has_data' => $p['bills_count'] > 0],
                        ['key' => 'votos', 'title' => 'Votos e presença', 'preview' => $p['votes_count'].' votos registrados'.($p['presenca_percentual'] ? ' · presença em '.$p['presenca_percentual'].'% das sessões' : ''), 'icon' => 'seal', 'has_data' => $p['votes_count'] > 0],
                        ['key' => 'indicacoes', 'title' => 'Indicações e nomeações aprovadas', 'preview' => 'Ainda não importado', 'icon' => 'neutral', 'has_data' => false],
                        ['key' => 'comissoes', 'title' => 'Comissões e relatorias', 'preview' => 'Membro de '.$p['committees_count'].' comissões · '.count($p['rapporteurships']).' relatorias', 'icon' => 'neutral', 'has_data' => $p['committees_count'] > 0 || count($p['rapporteurships']) > 0],
                    ],
                    'presidente' => [
                        ['key' => 'leis', 'title' => 'Leis sancionadas e vetos', 'preview' => 'Ainda não importado', 'icon' => 'seal', 'has_data' => false],
                        ['key' => 'mps', 'title' => 'Medidas provisórias', 'preview' => 'Ainda não importado', 'icon' => 'neutral', 'has_data' => false],
                        ['key' => 'ministros', 'title' => 'Ministros nomeados e exonerados', 'preview' => 'Ainda não importado', 'icon' => 'neutral', 'has_data' => false],
                        ['key' => 'agenda', 'title' => 'Agenda internacional', 'preview' => 'Ainda não importado', 'icon' => 'neutral', 'has_data' => false],
                    ],
                    'vice' => [
                        ['key' => 'substituicoes', 'title' => 'Substituições da presidência', 'preview' => 'Ainda não importado', 'icon' => 'seal', 'has_data' => false],
                        ['key' => 'atribuicoes', 'title' => 'Atribuições delegadas', 'preview' => 'Sem registro público estruturado (não verificável)', 'icon' => 'neutral', 'has_data' => false],
                    ],
                };
            @endphp

            <p class="mc-section-label">atuação no mandato</p>
            <div class="mc-accordion">
                @foreach ($secoesAtuacao as $i => $secao)
                    <details {{ $i === 0 ? 'open' : '' }} class="mc-details">
                        <summary class="mc-summary">
                            <span class="mc-icon-wrap mc-ic-{{ $secao['icon'] }}">
                                @if ($secao['icon'] === 'seal')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                @elseif ($secao['icon'] === 'alert')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                                @endif
                            </span>
                            <span class="mc-summary-text">
                                <div class="mc-summary-title">{{ $secao['title'] }}</div>
                                <div class="mc-summary-preview">{{ $secao['preview'] }}</div>
                            </span>
                            <svg class="mc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </summary>
                        <div class="mc-body">
                            @if ($secao['key'] === 'projetos')
                                @if (count($p['bills']) > 0)
                                    @foreach ($p['bills'] as $bill)
                                        <a href="https://www.camara.leg.br/proposicoesWeb/fichadetramitacao?idProposicao={{ $bill['external_id'] }}" target="_blank" rel="noopener" style="display:flex;gap:14px;padding:11px 0;border-bottom:1px solid var(--line);text-decoration:none;">
                                            <span class="mc-act-date">{{ $bill['year'] }}</span>
                                            <span class="mc-act-desc">{{ $bill['title'] }}</span>
                                        </a>
                                    @endforeach
                                    @if ($p['bills_count'] > 3)
                                        <div class="mc-see-all" wire:click="$dispatch('openProposicoesModal')">ver todos os {{ $p['bills_count'] }} →</div>
                                    @endif
                                @else
                                    <p class="mc-empty-note">Nenhum projeto encontrado.</p>
                                @endif

                            @elseif ($secao['key'] === 'votos')
                                @if (count($p['votes']) > 0)
                                    @foreach ($p['votes'] as $vote)
                                        @php
                                            $extId = $vote['session_external_id'] ?? '';
                                            $votacaoId = explode('-', $extId)[0];
                                            $camaraUrl = $votacaoId ? "https://www.camara.leg.br/plenario/votacao/{$votacaoId}" : '#';
                                            $voteTitle = $vote['bill_title'] ?? 'Votação';
                                            $orientation = $vote['party_orientation'] ?? null;
                                            $aligned = $vote['aligned'] ?? null;
                                        @endphp
                                        <a href="{{ $camaraUrl }}" target="_blank" rel="noopener" style="display:flex;gap:14px;padding:11px 0;border-bottom:1px solid var(--line);text-decoration:none;align-items:center;">
                                            <span style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:{{ $vote['vote'] === 'Sim' ? 'var(--seal)' : ($vote['vote'] === 'Não' ? 'var(--alert)' : 'var(--ink-faint)') }};"></span>
                                            <span style="flex:1;min-width:0;">
                                                <span class="mc-act-desc" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $voteTitle }}</span>
                                                <span class="mc-act-date" style="width:auto;">{{ $vote['date'] }}@if($orientation) · orientação: {{ $orientation }}@if($aligned !== null) · {{ $aligned ? 'alinhado' : 'desalinhado' }}@endif@endif</span>
                                            </span>
                                            <span style="font-size:12px;font-weight:500;color:{{ $vote['vote'] === 'Sim' ? 'var(--seal)' : ($vote['vote'] === 'Não' ? 'var(--alert)' : 'var(--ink-faint)') }};">{{ $vote['vote'] }}</span>
                                        </a>
                                    @endforeach
                                    @if ($p['votes_count'] > 5)
                                        <div class="mc-see-all" wire:click="$dispatch('openVotacoesModal')">ver todos os {{ $p['votes_count'] }} →</div>
                                    @endif
                                    <p class="mc-empty-note" style="font-style:normal;margin-top:8px;">* Presença calculada com base nos votos registrados.</p>
                                @else
                                    <p class="mc-empty-note">Nenhum voto registrado.</p>
                                @endif

                            @elseif ($secao['key'] === 'comissoes')
                                @if (count($p['committees']) > 0)
                                    @foreach (array_slice($p['committees'], 0, 5) as $committee)
                                        <div style="padding:11px 0;border-bottom:1px solid var(--line);">
                                            <p style="font-size:13.5px;color:var(--ink);margin:0;line-height:1.5;">
                                                {{ $committee['acronym'] ? $committee['acronym'].' · ' : '' }}{{ $committee['name'] }}
                                            </p>
                                            <p style="font-size:12px;color:var(--ink-faint);margin:2px 0 0;">
                                                {{ $committee['role'] ?? 'Membro' }}
                                                @if ($committee['start_date']) · Desde {{ $committee['start_date'] }} @endif
                                            </p>
                                        </div>
                                    @endforeach
                                @endif
                                @if (count($p['fronts_grouped']) > 0)
                                    <p style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-faint);margin:14px 0 6px;">Frentes parlamentares</p>
                                    @foreach ($p['fronts_grouped'] as $group)
                                        <div style="padding:8px 0;border-bottom:1px solid var(--line);">
                                            <p style="font-size:13px;color:var(--ink);margin:0;">{{ $group['category'] }} <span style="font-size:11px;color:var(--ink-faint);">({{ $group['count'] }})</span></p>
                                        </div>
                                    @endforeach
                                @endif
                                @if (count($p['rapporteurships']) > 0)
                                    <p style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-faint);margin:14px 0 6px;">Relatorias</p>
                                    @foreach (array_slice($p['rapporteurships'], 0, 3) as $rapport)
                                        <div style="padding:8px 0;border-bottom:1px solid var(--line);">
                                            <p style="font-size:13px;color:var(--ink);margin:0;">{{ $rapport['bill_description'] ?? '—' }}</p>
                                            <p style="font-size:12px;color:var(--ink-faint);margin:2px 0 0;">{{ $rapport['commission'] }}</p>
                                        </div>
                                    @endforeach
                                @endif
                                @if (count($p['leaderships']) > 0)
                                    <p style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-faint);margin:14px 0 6px;">Lideranças</p>
                                    @foreach ($p['leaderships'] as $leadership)
                                        <div style="padding:8px 0;border-bottom:1px solid var(--line);">
                                            <p style="font-size:13px;color:var(--ink);margin:0;">{{ $leadership['position'] }}</p>
                                            <p style="font-size:12px;color:var(--ink-faint);margin:2px 0 0;">{{ $leadership['house'] }} · {{ $leadership['start_date'] ?? '—' }} a {{ $leadership['end_date'] ?? 'Atual' }}</p>
                                        </div>
                                    @endforeach
                                @endif
                                @if (count($p['committees']) === 0 && count($p['fronts_grouped']) === 0 && count($p['rapporteurships']) === 0 && count($p['leaderships']) === 0)
                                    <p class="mc-empty-note">Nenhuma comissão ou frente encontrada.</p>
                                @endif

                            @elseif ($secao['key'] === 'fiscalizacao')
                                @if ($p['speeches_count'] > 0)
                                    <p style="font-size:13px;color:var(--ink);line-height:1.5;">{{ $p['speeches_count'] }} discursos registrados na Câmara</p>
                                @else
                                    <p class="mc-empty-note">Nenhuma fiscalização registrada.</p>
                                @endif

                            @elseif ($secao['key'] === 'indicacoes')
                                <p class="mc-empty-note">Dados de indicações e sabatinas ainda não importados.</p>

                            @elseif ($secao['key'] === 'leis' || $secao['key'] === 'mps' || $secao['key'] === 'ministros' || $secao['key'] === 'agenda')
                                <p class="mc-empty-note">Dados ainda não importados. Serão disponibilizados em atualização futura.</p>

                            @elseif ($secao['key'] === 'substituicoes')
                                <p class="mc-empty-note">Dados de substituições ainda não importados.</p>

                            @elseif ($secao['key'] === 'atribuicoes')
                                <p class="mc-empty-note">As atribuições delegadas pelo presidente ao vice não são publicadas de forma estruturada. Este campo mostra apenas o que é possível verificar em fontes oficiais.</p>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>

            {{-- FINANÇAS --}}
            @php
                $secoesFinancas = match($cargo) {
                    'deputado' => [
                        ['key' => 'gastos', 'title' => 'Gastos de gabinete (CEAP)', 'preview' => 'R$ '.number_format($p['total_expenses'], 0, ',', '.').($p['bancada_avg'] ? ' · média da bancada: R$ '.number_format($p['bancada_avg'], 0, ',', '.') : ''), 'icon' => 'money', 'has_data' => $p['total_expenses'] > 0],
                        ['key' => 'financiamento', 'title' => 'Financiamento de campanha', 'preview' => 'R$ '.number_format($p['total_campanha'], 0, ',', '.').' · '.count($p['campaign_financings']).' doadores', 'icon' => 'money', 'has_data' => $p['total_campanha'] > 0],
                        ['key' => 'bens', 'title' => 'Bens declarados', 'preview' => 'R$ '.number_format($p['total_bens'], 0, ',', '.').' declarado ao TSE em 2022', 'icon' => 'neutral', 'has_data' => $p['total_bens'] > 0],
                    ],
                    'senador' => [
                        ['key' => 'financiamento', 'title' => 'Financiamento de campanha', 'preview' => 'R$ '.number_format($p['total_campanha'], 0, ',', '.').' · '.count($p['campaign_financings']).' doadores', 'icon' => 'money', 'has_data' => $p['total_campanha'] > 0],
                        ['key' => 'bens', 'title' => 'Bens declarados', 'preview' => 'R$ '.number_format($p['total_bens'], 0, ',', '.').' declarado ao TSE em 2022', 'icon' => 'neutral', 'has_data' => $p['total_bens'] > 0],
                    ],
                    'presidente' => [
                        ['key' => 'despesas_governo', 'title' => 'Despesas do governo', 'preview' => 'Ainda não importado', 'icon' => 'money', 'has_data' => false],
                        ['key' => 'financiamento', 'title' => 'Financiamento de campanha', 'preview' => 'R$ '.number_format($p['total_campanha'], 0, ',', '.').' · '.count($p['campaign_financings']).' doadores', 'icon' => 'money', 'has_data' => $p['total_campanha'] > 0],
                        ['key' => 'bens', 'title' => 'Bens declarados', 'preview' => 'R$ '.number_format($p['total_bens'], 0, ',', '.').' declarado ao TSE em 2022', 'icon' => 'neutral', 'has_data' => $p['total_bens'] > 0],
                    ],
                    'vice' => [
                        ['key' => 'financiamento', 'title' => 'Financiamento de campanha', 'preview' => 'Compartilhado com a chapa presidencial', 'icon' => 'money', 'has_data' => $p['total_campanha'] > 0],
                        ['key' => 'bens', 'title' => 'Bens declarados', 'preview' => 'R$ '.number_format($p['total_bens'], 0, ',', '.').' declarado ao TSE em 2022', 'icon' => 'neutral', 'has_data' => $p['total_bens'] > 0],
                    ],
                };
            @endphp

            <p class="mc-section-label">finanças</p>
            <div class="mc-accordion">
                @foreach ($secoesFinancas as $i => $secao)
                    <details {{ $i === 0 ? 'open' : '' }} class="mc-details">
                        <summary class="mc-summary">
                            <span class="mc-icon-wrap mc-ic-{{ $secao['icon'] }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    @if ($secao['key'] === 'gastos' || $secao['key'] === 'despesas_governo')
                                        <rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20M7 15h3"/>
                                    @elseif ($secao['key'] === 'financiamento')
                                        <path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                                    @else
                                        <path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1m-6 4h1m4 0h1"/>
                                    @endif
                                </svg>
                            </span>
                            <span class="mc-summary-text">
                                <div class="mc-summary-title">{{ $secao['title'] }}</div>
                                <div class="mc-summary-preview">{{ $secao['preview'] }}</div>
                            </span>
                            <svg class="mc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </summary>
                        <div class="mc-body">
                            @if ($secao['key'] === 'gastos')
                                @if (count($p['expense_breakdown']) > 0)
                                    @foreach ($p['expense_breakdown'] as $item)
                                        <div style="display:flex;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--line);">
                                            <span style="font-size:13.5px;color:var(--ink);">{{ $item['type'] }}</span>
                                            <span style="font-size:13.5px;font-weight:600;color:var(--ink);font-family:monospace;">R$ {{ number_format($item['total'], 2, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                    <p style="font-size:11px;color:var(--ink-faint);margin-top:8px;">{{ $p['expenses_count'] }} documentos no total.</p>
                                    @if ($p['expenses_count'] > 5)
                                        <div class="mc-see-all" wire:click="$dispatch('openDespesasModal')">ver todas as {{ $p['expenses_count'] }} →</div>
                                    @endif
                                @else
                                    <p class="mc-empty-note">Nenhum gasto CEAP registrado.</p>
                                @endif
                            @elseif ($secao['key'] === 'financiamento')
                                @if (count($p['campaign_financings']) > 0)
                                    @foreach ($p['campaign_financings'] as $fonte)
                                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);">
                                            <span style="font-size:13px;color:var(--ink);">{{ $fonte['type'] }}</span>
                                            <span style="font-size:13px;font-weight:600;color:var(--ink);font-family:monospace;">R$ {{ number_format($fonte['total'], 2, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="mc-empty-note">Nenhum financiamento registrado.</p>
                                @endif
                            @elseif ($secao['key'] === 'bens')
                                @if (count($p['asset_declarations']) > 0)
                                    @foreach ($p['asset_declarations'] as $asset)
                                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);">
                                            <span style="font-size:13px;color:var(--ink);">{{ $asset['year'] }} · {{ $asset['description'] }}</span>
                                            <span style="font-size:13px;font-weight:600;color:var(--ink);font-family:monospace;">R$ {{ number_format($asset['value'], 2, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                    <p style="font-size:11px;color:var(--ink-faint);margin-top:8px;">Dados referentes aos anos eleitorais.</p>
                                @else
                                    <p class="mc-empty-note">Nenhum bem declarado.</p>
                                @endif
                            @elseif ($secao['key'] === 'despesas_governo')
                                <p class="mc-empty-note">Dados de despesas do governo ainda não importados.</p>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>

            {{-- COMPROMISSOS DE CAMPANHA (presidente) --}}
            @if ($cargo === 'presidente')
                <p class="mc-section-label">compromissos de campanha</p>
                <div class="mc-accordion">
                    <details class="mc-details">
                        <summary class="mc-summary">
                            <span class="mc-icon-wrap mc-ic-seal">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                            </span>
                            <span class="mc-summary-text">
                                <div class="mc-summary-title">Propostas de governo</div>
                                <div class="mc-summary-preview">Plano de governo registrado no TSE em 2022</div>
                            </span>
                            <svg class="mc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </summary>
                        <div class="mc-body">
                            <p class="mc-empty-note">Propostas de governo ainda não importadas.</p>
                        </div>
                    </details>
                </div>
            @endif

            {{-- HISTÓRICO E INTEGRIDADE --}}
            <p class="mc-section-label">histórico e integridade</p>
            <div class="mc-accordion">
                <details open class="mc-details">
                    <summary class="mc-summary">
                        <span class="mc-icon-wrap mc-ic-neutral">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                        </span>
                        <span class="mc-summary-text">
                            <div class="mc-summary-title">Trajetória política</div>
                            <div class="mc-summary-preview">{{ count($p['mandates']) }} mandato{{ count($p['mandates']) !== 1 ? 's' : '' }}: {{ strtolower($p['position']) }}{{ count($p['mandates']) > 1 ? '' : ' (atual)' }}</div>
                        </span>
                        <svg class="mc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </summary>
                    <div class="mc-body">
                        @if (count($p['mandates']) > 0)
                            @foreach ($p['mandates'] as $mandate)
                                <div class="mc-act">
                                    <span class="mc-act-date" style="width:auto;">{{ $mandate['started_at'] ?? '—' }}</span>
                                    <span class="mc-act-desc" style="font-weight:500;">{{ $mandate['position'] }} <span style="font-weight:400;color:var(--ink-faint);">a {{ $mandate['ended_at'] ?? 'Em exercício' }}</span></span>
                                </div>
                            @endforeach
                        @else
                            <p class="mc-empty-note">Nenhum mandato registrado.</p>
                        @endif
                    </div>
                </details>

                <details class="mc-details">
                    <summary class="mc-summary">
                        <span class="mc-icon-wrap {{ count($p['legal_proceedings']) > 0 ? 'mc-ic-alert' : 'mc-ic-seal' }}">
                            @if (count($p['legal_proceedings']) > 0)
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/></svg>
                            @endif
                        </span>
                        <span class="mc-summary-text">
                            <div class="mc-summary-title">Processos e integridade</div>
                            <div class="mc-summary-preview">{{ count($p['legal_proceedings']) > 0 ? count($p['legal_proceedings']).' processo(s) em andamento' : 'Nenhum processo em andamento' }}</div>
                        </span>
                        <svg class="mc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </summary>
                    <div class="mc-body">
                        @if (count($p['legal_proceedings']) > 0)
                            @foreach ($p['legal_proceedings'] as $process)
                                <div style="padding:11px 0;border-bottom:1px solid var(--line);">
                                    <div style="display:flex;justify-content:space-between;align-items:center;">
                                        <span style="font-size:13.5px;font-weight:500;color:var(--ink);">{{ $process['process_number'] }}</span>
                                        <span style="font-size:11px;color:var(--ink-faint);background:var(--neutral);padding:2px 8px;border-radius:8px;">{{ $process['status'] }}</span>
                                    </div>
                                    <p style="font-size:12px;color:var(--ink-faint);margin:2px 0 0;">{{ $process['court'] }}</p>
                                    @if ($process['description'])
                                        <p style="font-size:12px;color:var(--ink-soft);margin:2px 0 0;">{{ $process['description'] }}</p>
                                    @endif
                                    @if ($process['source_url'])
                                        <a href="{{ $process['source_url'] }}" target="_blank" rel="noopener" style="font-size:12px;color:var(--seal);text-decoration:none;margin-top:4px;display:inline-block;">Ver no tribunal →</a>
                                    @endif
                                </div>
                            @endforeach
                            <p style="font-size:11px;color:var(--ink-faint);margin-top:8px;">Status, não veredito. Cada processo linka à fonte oficial para verificação.</p>
                        @else
                            <p class="mc-empty-note">Nenhum processo judicial em andamento registrado no TSE ou nos tribunais consultados.</p>
                        @endif
                    </div>
                </details>
            </div>

            {{-- CONTEXT NOTE (vice) --}}
            @if ($cargo === 'vice')
                <div class="mc-context-note">
                    O vice-presidente tem poucas atribuições próprias publicadas de forma verificável — a maior parte do trabalho do cargo é delegada informalmente pelo presidente e não gera registro público estruturado. Este perfil mostra o que é possível confirmar em fontes oficiais.
                </div>
            @endif

            {{-- FOOTER --}}
            <p class="mc-footer-note">Dados oficiais: {{ $this->sourceFooter }}</p>

        </div>
    @endif
</section>
</div>
