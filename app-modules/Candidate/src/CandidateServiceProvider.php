<?php

namespace MeuCandidato\Candidate;

use Illuminate\Support\ServiceProvider;

class CandidateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
