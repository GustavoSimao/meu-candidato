<?php

namespace MeuCandidato\Transparency;

use Illuminate\Support\ServiceProvider;

class TransparencyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
