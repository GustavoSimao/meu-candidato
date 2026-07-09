<?php

namespace MeuCandidato\Mandate;

use Illuminate\Support\ServiceProvider;

class MandateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
