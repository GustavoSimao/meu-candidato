<?php

namespace MeuCandidato\Party;

use Illuminate\Support\ServiceProvider;

class PartyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
