<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="$set('isOpen', false)">
            <div class="fixed inset-0 bg-black/50"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col" @click.outside="$set('isOpen', false)" @keydown.escape.window="$set('isOpen', false)">
                <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200">
                    <flux:heading size="lg">Lideranças</flux:heading>
                    <button wire:click="$set('isOpen', false)" class="text-zinc-400 hover:text-zinc-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-6 py-3 border-b border-zinc-100">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar liderança..." class="w-full px-3 py-2 text-sm border border-zinc-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-3">
                    @if ($leaderships->isEmpty())
                        <p class="text-sm text-zinc-500 text-center py-8">Nenhuma liderança encontrada.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($leaderships as $leadership)
                                <div class="bg-white border border-zinc-200 rounded-lg px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-zinc-900 line-clamp-1">{{ $leadership->position }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-xs text-zinc-600">{{ $leadership->party_acronym }}</span>
                                                <span class="text-xs text-zinc-400">•</span>
                                                <span class="text-xs text-zinc-500">{{ $leadership->house }}</span>
                                                <span class="text-xs text-zinc-400">•</span>
                                                <span class="text-xs text-zinc-500">
                                                    {{ $leadership->start_date?->format('d/m/Y') }}
                                                    @if ($leadership->end_date)
                                                        — {{ $leadership->end_date?->format('d/m/Y') }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3 border-t border-zinc-200">
                    {{ $leaderships->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
