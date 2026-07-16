<?php

namespace MeuCandidato\Party\Database\Seeders;

use Illuminate\Database\Seeder;
use MeuCandidato\Party\Models\Party;

class PartySeeder extends Seeder
{
    public function run(): void
    {
        $partidos = [
            ['name' => 'Partido Liberal', 'acronym' => 'PL', 'number' => 22],
            ['name' => 'Partido dos Trabalhadores', 'acronym' => 'PT', 'number' => 13],
            ['name' => 'Movimento Democrático Brasileiro', 'acronym' => 'MDB', 'number' => 15],
            ['name' => 'União Brasil', 'acronym' => 'UNIÃO', 'number' => 44],
            ['name' => 'Partido Social Democrático', 'acronym' => 'PSD', 'number' => 55],
            ['name' => 'Partido Progressista', 'acronym' => 'PP', 'number' => 11],
            ['name' => 'Republicanos', 'acronym' => 'REPUBLICANOS', 'number' => 10],
            ['name' => 'Podemos', 'acronym' => 'PODE', 'number' => 19],
            ['name' => 'Partido Social Democrata Cristão', 'acronym' => 'PSDC', 'number' => 27],
            ['name' => 'Partido da Social Democracia Brasileira', 'acronym' => 'PSDB', 'number' => 45],
            ['name' => 'Cidadania', 'acronym' => 'CIDADANIA', 'number' => 23],
            ['name' => 'Partido Verde', 'acronym' => 'PV', 'number' => 43],
            ['name' => 'Partido Socialismo e Liberdade', 'acronym' => 'PSOL', 'number' => 50],
            ['name' => 'Partido Democrático Trabalhista', 'acronym' => 'PDT', 'number' => 12],
            ['name' => 'Solidariedade', 'acronym' => 'SOLIDARIEDADE', 'number' => 23],
            ['name' => 'Partido Novo', 'acronym' => 'NOVO', 'number' => 30],
            ['name' => 'Partido Republicano da Ordem Social', 'acronym' => 'PROS', 'number' => 90],
            ['name' => 'Partido Comunista Brasileiro', 'acronym' => 'PCdoB', 'number' => 65],
            ['name' => 'Partido Socialista Brasileiro', 'acronym' => 'PSB', 'number' => 40],
            ['name' => 'Partido Trabalhista Brasileiro', 'acronym' => 'PTB', 'number' => 14],
            ['name' => 'Partido da Mobilização Nacional', 'acronym' => 'PMN', 'number' => 33],
            ['name' => 'Partido Ecológico Nacional', 'acronym' => 'PEN', 'number' => 51],
            ['name' => 'Partido Social Liberal', 'acronym' => 'PSL', 'number' => 17],
            ['name' => 'Partido da Causa Operária', 'acronym' => 'PCO', 'number' => 29],
            ['name' => 'Partido Trabalhista Cristão', 'acronym' => 'PTC', 'number' => 36],
            ['name' => 'Partido Socialista dos Trabalhadores Unificado', 'acronym' => 'PSTU', 'number' => 16],
            ['name' => 'Partido Republicano Progressista', 'acronym' => 'PRP', 'number' => 44],
            ['name' => 'Democratas', 'acronym' => 'DEM', 'number' => 25],
            ['name' => 'Partido Patriota', 'acronym' => 'PATRIOTA', 'number' => 51],
            ['name' => 'Partido dos Trabalhadores do Brasil', 'acronym' => 'PTdoB', 'number' => 70],
        ];

        $seen = [];

        foreach ($partidos as $partido) {
            if (in_array($partido['acronym'], $seen)) {
                continue;
            }

            $seen[] = $partido['acronym'];

            Party::updateOrCreate(
                ['acronym' => $partido['acronym']],
                [
                    'name' => $partido['name'],
                    'number' => $partido['number'],
                ]
            );
        }
    }
}
