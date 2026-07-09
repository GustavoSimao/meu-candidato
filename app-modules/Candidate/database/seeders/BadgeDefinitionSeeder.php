<?php

namespace MeuCandidato\Candidate\Database\Seeders;

use Illuminate\Database\Seeder;
use MeuCandidato\Candidate\Models\BadgeDefinition;

class BadgeDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'badge_type' => 'veteran',
                'label' => 'Político veterano',
                'description' => 'Mais de 3 mandatos exercidos',
                'color' => '#7c3aed',
                'rules' => ['min_mandates' => 4],
            ],
            [
                'badge_type' => 'prolific_author',
                'label' => 'Autor prolífico',
                'description' => 'Mais de 50 proposições autoradas',
                'color' => '#2563eb',
                'rules' => ['min_bills' => 50],
            ],
            [
                'badge_type' => 'newcomer',
                'label' => 'Estreante',
                'description' => 'Primeiro mandato em exercício',
                'color' => '#059669',
                'rules' => ['max_mandates' => 1],
            ],
            [
                'badge_type' => 'senator',
                'label' => 'Senador',
                'description' => 'Exercendo mandato no Senado Federal',
                'color' => '#dc2626',
                'rules' => ['position' => 'Senador'],
            ],
            [
                'badge_type' => 'deputy',
                'label' => 'Deputado Federal',
                'description' => 'Exercendo mandato na Câmara dos Deputados',
                'color' => '#ea580c',
                'rules' => ['position' => 'Deputado Federal'],
            ],
            [
                'badge_type' => 'big_spender',
                'label' => 'Grandes despesas',
                'description' => 'Despesas CEAP acima da média',
                'color' => '#d97706',
                'rules' => ['min_total_expenses_percentile' => 90],
            ],
            [
                'badge_type' => 'budget_conscious',
                'label' => 'Econômico',
                'description' => 'Despesas CEAP abaixo da média',
                'color' => '#16a34a',
                'rules' => ['max_total_expenses_percentile' => 10],
            ],
            [
                'badge_type' => 'multi_party',
                'label' => 'Viajante partidário',
                'description' => 'Mudou de partido mais de 2 vezes',
                'color' => '#9333ea',
                'rules' => ['min_party_changes' => 3],
            ],
            [
                'badge_type' => 'long_career',
                'label' => 'Carreira longa',
                'description' => 'Mais de 10 anos em mandatos cumulativos',
                'color' => '#0891b2',
                'rules' => ['min_years_in_office' => 10],
            ],
            [
                'badge_type' => 'high_education',
                'label' => 'Formação superior',
                'description' => 'Possui pós-graduação ou doutorado',
                'color' => '#4f46e5',
                'rules' => ['education_level' => ['Mestrado', 'Doutorado', 'Pós-graduação']],
            ],
        ];

        foreach ($badges as $badge) {
            BadgeDefinition::updateOrCreate(
                ['badge_type' => $badge['badge_type']],
                $badge
            );
        }
    }
}
