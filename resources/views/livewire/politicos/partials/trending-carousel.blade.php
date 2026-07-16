{{--
    resources/views/livewire/politicos/partials/trending-carousel.blade.php

    "Em Alta" — carousel de destaques.
    Espera receber $trending como Collection de Politician com party e latestAddress eager-loaded.

    Uso no index.blade.php:
        @include('livewire.politicos.partials.trending-carousel', ['trending' => $trending])
--}}

<div
    x-data="trendingCarousel({ total: {{ $trending->count() }}, perView: { base: 1, md: 3 }, interval: 5000 })"
    x-init="init()"
    @mouseenter="pause()"
    @mouseleave="resume()"
    @focusin="pause()"
    @focusout="resume()"
    class="mc-trending w-full max-w-[1200px] mx-auto"
>
    <div class="flex items-center gap-3 mb-4">
        <span class="mc-eyebrow inline-flex items-center gap-1 text-sm font-medium text-[#2f6f4f]">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                <path d="M13 2L3 14h7l-1 8 11-14h-7l1-6Z"/>
            </svg>
            EM ALTA
        </span>
    </div>

    <div class="relative">
        {{-- Seta esquerda --}}
        <button
            type="button"
            @click="prev()"
            aria-label="Anterior"
            class="hidden md:flex absolute -left-14 top-1/2 -translate-y-1/2 z-10 h-11 w-11 items-center justify-center rounded-full bg-white shadow-md hover:shadow-lg transition-shadow"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </button>

        {{-- Track --}}
        <div class="trending-track overflow-hidden">
            <div
                class="flex transition-transform duration-500 ease-out"
                :style="`transform: translateX(-${index * (100 / itemsPerView)}%)`"
            >
                @foreach ($trending as $pessoa)
                    <div class="shrink-0 px-2.5" :style="`width: ${100 / itemsPerView}%`">
                        <a
                            href="{{ route('politicos.show', $pessoa->id) }}"
                            class="mc-trending-card group flex flex-col items-center text-center bg-white rounded-2xl border border-black/5 shadow-sm hover:shadow-md transition-shadow px-8 py-10 h-full"
                        >
                            <div class="mb-5">
                                @if($pessoa->photo_url)
                                    <img
                                        src="{{ $pessoa->photo_url }}"
                                        alt="{{ $pessoa->name }}"
                                        class="w-20 h-20 rounded-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="w-20 h-20 rounded-full bg-[#e4ede7] flex items-center justify-center">
                                        <span class="font-serif text-2xl text-[#2f6f4f]">{{ strtoupper(mb_substr($pessoa->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                            </div>

                            <h3 class="font-serif text-2xl text-[#1c1c1c] mb-1">{{ $pessoa->nome_urna ?? $pessoa->name }}</h3>
                            <p class="text-sm text-[#8a8a80] mb-4">
                                {{ $pessoa->party?->acronym ?? '—' }} &middot; {{ $pessoa->latestAddress?->uf ?? '—' }}
                            </p>

                            <p class="text-sm font-medium text-[#2f6f4f] group-hover:underline">
                                {{ $pessoa->trendings }}
                            </p>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Seta direita --}}
        <button
            type="button"
            @click="next()"
            aria-label="Próximo"
            class="hidden md:flex absolute -right-14 top-1/2 -translate-y-1/2 z-10 h-11 w-11 items-center justify-center rounded-full bg-white shadow-md hover:shadow-lg transition-shadow"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                <path d="M9 18l6-6-6-6"/>
            </svg>
        </button>
    </div>

    {{-- Dots --}}
    <div class="flex items-center justify-center gap-2 mt-6">
        <template x-for="slide in totalSlides" :key="slide">
            <button
                type="button"
                @click="goTo(slide - 1)"
                :aria-label="`Ir para grupo ${slide}`"
                :aria-current="index === slide - 1"
                class="h-2 rounded-full transition-all duration-300 dot"
                :class="index === slide - 1 ? 'w-6 bg-[#2f6f4f]' : 'w-2 bg-black/15 hover:bg-black/25'"
                :data-slide="slide - 1"
            ></button>
        </template>
    </div>

    <script>
        function trendingCarousel({ total, perView, interval }) {
            return {
                total,
                interval,
                index: 0,
                itemsPerView: perView.base,
                timer: null,
                paused: false,

                get totalSlides() {
                    return Math.max(1, this.total - this.itemsPerView + 1);
                },

                init() {
                    this.setItemsPerView();
                    window.addEventListener('resize', () => this.setItemsPerView());
                    this.start();
                },

                setItemsPerView() {
                    this.itemsPerView = window.innerWidth >= 768 ? perView.md : perView.base;
                    if (this.index > this.totalSlides - 1) this.index = 0;
                },

                start() {
                    this.timer = setInterval(() => {
                        if (!this.paused) this.next();
                    }, this.interval);
                },

                pause() { this.paused = true; },
                resume() { this.paused = false; },

                next() {
                    this.index = (this.index + 1) % this.totalSlides;
                },

                prev() {
                    this.index = (this.index - 1 + this.totalSlides) % this.totalSlides;
                },

                goTo(i) {
                    this.index = i;
                    clearInterval(this.timer);
                    this.start();
                },
            };
        }
    </script>
</div>