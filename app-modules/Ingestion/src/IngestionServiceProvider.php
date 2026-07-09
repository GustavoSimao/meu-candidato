<?php

namespace MeuCandidato\Ingestion;

use Illuminate\Support\ServiceProvider;
use MeuCandidato\Ingestion\Commands\AtualizarDadosCommand;
use MeuCandidato\Ingestion\Commands\ImportarDadosCommand;

class IngestionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportarDadosCommand::class,
                AtualizarDadosCommand::class,
            ]);
        }
    }
}
