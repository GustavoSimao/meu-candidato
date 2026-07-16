<?php

namespace MeuCandidato\Legislative;

use Illuminate\Support\ServiceProvider;

class LegislativeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
