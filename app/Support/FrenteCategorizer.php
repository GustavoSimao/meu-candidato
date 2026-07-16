<?php

namespace App\Support;

class FrenteCategorizer
{
    private const CATEGORIES = [
        'Segurança' => [
            'segurança', 'polícia', 'guarda', 'corrupção', 'justiça', 'penitenciário',
            'tráfico', 'droga', 'violência',
        ],
        'Saúde' => [
            'saúde', 'odontologia', 'farmácia', 'enfermagem', 'médic', 'hospitalar',
            'doença', 'câncer', 'transplante', 'hemoterapia', 'vacina',
        ],
        'Educação' => [
            'educação', 'escola', 'ensino', 'profissionalizante', 'universidade',
            'primeira infância', 'alfabetização',
        ],
        'Agronegócio' => [
            'agropecuária', 'agricultura', 'cooperativismo', 'agroecologia',
            'pecuária', 'agronegócio', 'rural', 'produção orgânica',
        ],
        'Economia' => [
            'economia', 'fiscal', 'tributário', 'gestão pública', 'orçamento',
            'financ', 'imposto', 'receita',
        ],
        'Direitos Sociais' => [
            'família', 'criança', 'mulher', 'conselheiro tutelar', 'idoso',
            'deficiência', 'pessoa com deficiência', 'direitos humanos',
        ],
        'Religião' => [
            'evangélica', 'cristã', 'religiosa', 'fé', 'biblia',
            'defesa da vida',
        ],
        'Infraestrutura' => [
            'usina', 'hidrelétrica', 'transporte', 'energia', 'infraestrutura',
            'rodovia', 'ferrovia', 'porto', 'aeroporto', 'saneamento',
        ],
        'Relações Internacionais' => [
            'internacional', 'comércio exterior', 'cooperação',
        ],
        'Comunicação' => [
            'radiodifusão', 'comunicação', 'mídia', 'imprensa', 'digital',
        ],
        'Serviço Público' => [
            'serviço público', 'servidor',
        ],
        'Cultura e Lazer' => [
            'cultura', 'esporte', 'lazer', 'turismo', 'patrimônio',
        ],
        'Meio Ambiente' => [
            'meio ambiente', 'sustentabilidade', 'clima', 'biodiversidade',
            'floresta', 'amazônia', 'hídrica',
        ],
    ];

    private const DESCRIPTIONS = [
        'Segurança' => 'Polícia, guarda municipal, combate à corrupção e segurança pública.',
        'Saúde' => 'Saúde pública, odontologia, farmácia, enfermagem e hospitais.',
        'Educação' => 'Escolas, ensino, universidades e primeira infância.',
        'Agronegócio' => 'Agricultura, pecuária, cooperativismo e produção orgânica.',
        'Economia' => 'Gestão pública, impostos, orçamento e desenvolvimento econômico.',
        'Direitos Sociais' => 'Família, criança, mulher, idoso e direitos humanos.',
        'Religião' => 'Valores religiosos e defesa da vida.',
        'Infraestrutura' => 'Energia, transporte, saneamento e obras públicas.',
        'Relações Internacionais' => 'Cooperação internacional e comércio exterior.',
        'Comunicação' => 'Radiodifusão, mídia e comunicação digital.',
        'Serviço Público' => 'Servidores públicos e administração pública.',
        'Cultura e Lazer' => 'Cultura, esporte, turismo e patrimônio.',
        'Meio Ambiente' => 'Sustentabilidade, clima, biodiversidade e florestas.',
        'Outros' => 'Temas que não se encaixam nas demais categorias.',
    ];

    public static function categorize(string $title): string
    {
        $lower = mb_strtolower($title);

        foreach (self::CATEGORIES as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    return $category;
                }
            }
        }

        return 'Outros';
    }

    public static function getDescription(string $category): string
    {
        return self::DESCRIPTIONS[$category] ?? '';
    }

    /**
     * Group fronts by category, sorted by count descending.
     *
     * @param  array<int, array{title: string, legislature: int|null, external_id: string}>  $fronts
     * @return array<int, array{category: string, description: string, count: int, fronts: list<string>}>
     */
    public static function group(array $fronts): array
    {
        $grouped = [];
        $seen = [];

        foreach ($fronts as $front) {
            $category = self::categorize($front['title']);

            if (! isset($grouped[$category])) {
                $grouped[$category] = [
                    'category' => $category,
                    'description' => self::getDescription($category),
                    'count' => 0,
                    'fronts' => [],
                ];
            }

            $grouped[$category]['count']++;

            $simplified = self::simplifyTitle($front['title']);
            $key = mb_strtolower($simplified);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $grouped[$category]['fronts'][] = [
                    'title' => $simplified,
                    'external_id' => $front['external_id'],
                ];
            }
        }

        usort($grouped, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $grouped;
    }

    public static function simplifyTitle(string $title): string
    {
        $title = preg_replace('/^frente parlamentar\s+(mista\s+)?(em defesa|pelo|pela|do|da|dos|das|de|para o|para a)\s+/i', '', $title);
        $title = preg_replace('/\s*\([^)]*\)\s*$/', '', $title);
        $title = preg_replace('/\s*-\s*FPA\s*$/', '', $title);

        return trim($title);
    }
}
