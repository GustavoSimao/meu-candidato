# Meu Candidato

Plataforma de transparência política brasileira. Consolida dados públicos de Câmara dos Deputados, Senado Federal e TSE em perfis acessíveis para o cidadão comum.

O projeto surgiu da dificuldade de acompanhar o trabalho dos representantes eleitos. Um cidadão comum não sabe quantos projetos o deputado apresentou, como votou, ou onde gasta o dinheiro público. O Meu Candidato transforma esses dados dispersos em uma experiência clara e útil.

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | Laravel 13 + PHP 8.3 |
| Frontend | Livewire 4 + Flux UI + Tailwind CSS v4 |
| Admin Panel | Filament 5 |
| Banco | PostgreSQL 17 |
| Cache/Fila | Redis |
| Container | Docker Compose |

## Como Rodar

```bash
docker compose up -d pgsql redis
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
php artisan importar-dados
npm install && npm run build
php artisan serve
```

## Comandos de Dados

```bash
php artisan importar-dados                  # Importar tudo
php artisan importar-dados deputados        # Deputados da Câmara
php artisan importar-dados votos            # 34k+ votos (streaming bulk)
php artisan importar-dados despesas-deputados  # 359k+ despesas CEAP
php artisan importar-dados senadores        # 81 senadores
php artisan importar-dados financiamento-campanha  # TSE
php artisan atualizar-dados                 # Atualização diária (cron 06:00)
```

## Funcionalidades

- Perfil completo do político (mandatos, votos, proposições, despesas com breakdown por tipo)
- 10 badges automáticas baseadas em atividade legislativa
- Sistema de follow com dashboard
- Modais paginados com busca para votações, proposições e despesas
- Links diretos para Câmara dos Deputados
- Painel admin Filament com 8 resources
- 82 testes / 186 assertions

## Licença

MIT
