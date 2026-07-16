<?php

namespace MeuCandidato\Geography;

use Illuminate\Support\ServiceProvider;

class GeographyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
