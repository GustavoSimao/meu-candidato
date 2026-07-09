<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Candidate\Models\PoliticianBadge;
use MeuCandidato\Candidate\Services\BadgeAssignmentService;

class AssignBadges extends Command
{
    protected $signature = 'badges:assign {--politician=}';

    protected $description = 'Atribuir badges aos políticos com base nas regras';

    public function handle(BadgeAssignmentService $service): int
    {
        $politicianId = $this->option('politician');

        if ($politicianId) {
            $politician = Politician::find($politicianId);

            if (! $politician) {
                $this->error("Político {$politicianId} não encontrado.");

                return self::FAILURE;
            }

            $badges = $service->evaluatePolitician($politician);
            $this->info("Político {$politician->name}: ".count($badges).' badges atribuídos.');

            return self::SUCCESS;
        }

        $this->info('Avaliando badges para todos os políticos...');
        $service->evaluateAll();

        $total = PoliticianBadge::count();
        $this->info("Total de badges atribuídas: {$total}");

        return self::SUCCESS;
    }
}
