<?php

namespace MeuCandidato\Ingestion;

use Illuminate\Support\ServiceProvider;
use MeuCandidato\Ingestion\Commands\AtualizarDadosCommand;
use MeuCandidato\Ingestion\Commands\ImportarDadosCommand;
use MeuCandidato\Ingestion\Commands\VincularVotosBillsCommand;

class IngestionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportarDadosCommand::class,
                AtualizarDadosCommand::class,
                VincularVotosBillsCommand::class,
            ]);
        }
    }
}
