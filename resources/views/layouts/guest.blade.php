<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased">
        <nav class="border-b border-zinc-200">
            <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
                <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight" wire:navigate>Meu Candidato</a>
                <div class="flex items-center gap-4">
                    <a href="{{ route('politicos') }}" class="text-sm text-zinc-600 hover:text-zinc-900" wire:navigate>Políticos</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm bg-zinc-900 text-white px-4 py-1.5 rounded-lg hover:bg-zinc-800" wire:navigate>Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-zinc-600 hover:text-zinc-900">Entrar</a>
                        <a href="{{ route('register') }}" class="text-sm bg-zinc-900 text-white px-4 py-1.5 rounded-lg hover:bg-zinc-800">Cadastrar</a>
                    @endauth
                </div>
            </div>
        </nav>

        {{ $slot }}

        <footer class="border-t border-zinc-200 mt-16">
            <div class="max-w-5xl mx-auto px-4 py-6 text-center text-xs text-zinc-500">
                {{ date('Y') }} Meu Candidato · Dados atualizados diariamente · Fontes: Câmara, Senado e TSE
            </div>
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
