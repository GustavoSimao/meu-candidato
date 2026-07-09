<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Meu Candidato') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-white text-zinc-900 min-h-screen">
    {{-- Nav --}}
    <nav class="border-b border-zinc-200">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight">Meu Candidato</a>
            <div class="flex items-center gap-4">
                <a href="{{ route('politicos') }}" class="text-sm text-zinc-600 hover:text-zinc-900">Políticos</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm bg-zinc-900 text-white px-4 py-1.5 rounded-lg hover:bg-zinc-800">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-zinc-600 hover:text-zinc-900">Entrar</a>
                    <a href="{{ route('register') }}" class="text-sm bg-zinc-900 text-white px-4 py-1.5 rounded-lg hover:bg-zinc-800">Cadastrar</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="max-w-5xl mx-auto px-4 py-20 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold tracking-tight leading-tight">
            Dados reais.<br>Decisões informadas.
        </h1>
        <p class="mt-4 text-lg text-zinc-600 max-w-2xl mx-auto">
            Acompanhe em tempo real o trabalho dos seus representantes na Câmara, Senado e TSE.
            Despesas, proposições, votações e mandatos — tudo em um só lugar.
        </p>
        <div class="mt-8 flex items-center justify-center gap-4">
            <a href="{{ route('politicos') }}" class="bg-zinc-900 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-zinc-800 transition">
                Explorar políticos
            </a>
            @guest
                <a href="{{ route('register') }}" class="border border-zinc-300 text-zinc-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-zinc-50 transition">
                    Criar conta grátis
                </a>
            @endguest
        </div>
    </section>

    {{-- Stats --}}
    @php
        $politicianCount = \MeuCandidato\Candidate\Models\Politician::count();
        $partyCount = \MeuCandidato\Party\Models\Party::count();
        $billCount = \MeuCandidato\Legislative\Models\Bill::count();
        $expenseCount = \MeuCandidato\Transparency\Models\Expense::count();
    @endphp
    <section class="max-w-5xl mx-auto px-4 pb-16">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-zinc-50 rounded-xl p-5 text-center">
                <p class="text-3xl font-bold text-zinc-900">{{ number_format($politicianCount, 0, ',', '.') }}</p>
                <p class="text-sm text-zinc-500 mt-1">Políticos cadastrados</p>
            </div>
            <div class="bg-zinc-50 rounded-xl p-5 text-center">
                <p class="text-3xl font-bold text-zinc-900">{{ $partyCount }}</p>
                <p class="text-sm text-zinc-500 mt-1">Partidos ativos</p>
            </div>
            <div class="bg-zinc-50 rounded-xl p-5 text-center">
                <p class="text-3xl font-bold text-zinc-900">{{ number_format($billCount, 0, ',', '.') }}</p>
                <p class="text-sm text-zinc-500 mt-1">Proposições rastreadas</p>
            </div>
            <div class="bg-zinc-50 rounded-xl p-5 text-center">
                <p class="text-3xl font-bold text-zinc-900">{{ $expenseCount > 0 ? number_format($expenseCount, 0, ',', '.') : '—' }}</p>
                <p class="text-sm text-zinc-500 mt-1">Despesas CEAP</p>
            </div>
        </div>
        <p class="text-center text-sm text-zinc-400 pt-2 pb-4">Dados atualizados diariamente a partir das APIs oficiais da Câmara, Senado e TSE.</p>
    </section>

    {{-- Features --}}
    <section class="bg-zinc-50 border-y border-zinc-200">
        <div class="max-w-5xl mx-auto px-4 py-16">
            <h2 class="text-2xl font-bold text-center mb-10">Como funciona</h2>
            <div class="grid sm:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-12 h-12 bg-zinc-200 rounded-xl mx-auto mb-3 flex items-center justify-center">
                        <svg class="w-6 h-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <h3 class="font-semibold">Busca inteligente</h3>
                    <p class="text-sm text-zinc-500 mt-1">Encontre políticos por nome, partido, estado ou cargo.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-zinc-200 rounded-xl mx-auto mb-3 flex items-center justify-center">
                        <svg class="w-6 h-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="font-semibold">Dados oficiais</h3>
                    <p class="text-sm text-zinc-500 mt-1">Informações direto das APIs da Câmara, Senado e TSE.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-zinc-200 rounded-xl mx-auto mb-3 flex items-center justify-center">
                        <svg class="w-6 h-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <h3 class="font-semibold">Siga e acompanhe</h3>
                    <p class="text-sm text-zinc-500 mt-1">Receba alertas sobre os políticos que você acompanha.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    @guest
    <section class="max-w-5xl mx-auto px-4 py-20 text-center">
        <h2 class="text-2xl font-bold">Pronto para acompanhar?</h2>
        <p class="text-zinc-600 mt-2">Crie sua conta gratuita e comece a acompanhar seus representantes.</p>
        <a href="{{ route('register') }}" class="inline-block mt-6 bg-zinc-900 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-zinc-800 transition">
            Criar conta grátis
        </a>
    </section>
    @endguest

    {{-- Footer --}}
    <footer class="border-t border-zinc-200">
        <div class="max-w-5xl mx-auto px-4 py-6 text-center text-xs text-zinc-500">
            {{ date('Y') }} Meu Candidato · Dados públicos agregados para transparência política · Fontes: Câmara, Senado e TSE
        </div>
    </footer>
</body>
</html>
