<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="$set('isOpen', false)">
            <div class="fixed inset-0 bg-black/50"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col" @click.outside="$set('isOpen', false)" @keydown.escape.window="$set('isOpen', false)">
                <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200">
                    <flux:heading size="lg">Todas as Proposições</flux:heading>
                    <button wire:click="$set('isOpen', false)" class="text-zinc-400 hover:text-zinc-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-6 py-3 border-b border-zinc-100">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar proposição..." class="w-full px-3 py-2 text-sm border border-zinc-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-3">
                    @if ($bills->isEmpty())
                        <p class="text-sm text-zinc-500 text-center py-8">Nenhuma proposição encontrada.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($bills as $bill)
                                @php
                                    $camaraUrl = "https://www.camara.leg.br/proposicoesWeb/fichadetramitacao?idProposicao={$bill->external_id}";
                                @endphp
                                <a href="{{ $camaraUrl }}" target="_blank" rel="noopener" class="block bg-white border border-zinc-200 rounded-lg px-4 py-3 hover:bg-zinc-50 transition">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm text-zinc-900 font-medium line-clamp-2">{{ $bill->title }}</p>
                                            @if ($bill->description)
                                                <p class="text-xs text-zinc-500 mt-1 line-clamp-2">{{ $bill->description }}</p>
                                            @endif
                                        </div>
                                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                            @if ($bill->status)
                                                <span class="text-xs bg-zinc-100 text-zinc-600 px-2 py-0.5 rounded-full">{{ $bill->status }}</span>
                                            @endif
                                            <span class="text-xs text-zinc-400">{{ $bill->year }}</span>
                                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3 border-t border-zinc-200">
                    {{ $bills->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
