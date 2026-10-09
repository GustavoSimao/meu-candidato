# ADR-005: Agendamento com Cron + Lock File

## Status

Aceito

## Contexto

A ingestão de dados precisa rodar diariamente (Câmara/Senado às 03:00 BRT, TSE mensal). Precisamos de um mecanismo de agendamento.

## Decisão

Decidi usar **cron do SO + lock file (`flock`)**.

- Cron: `0 3 * * *` (diário às 03:00 BRT)
- Lock: `flock` em `/tmp/meu-candidato-ingestion.lock` evita sobreposição
- Trigger manual: `POST /api/v1/ingestion/jobs` (API existente)
- Worker: container separado, polling de jobs pendentes

**Motivação:**
- Cron é battle-tested, sem infra extra
- `flock` é atômico e confiável
- API permite trigger on-demand
- Simples de debugar

**Migração futura:**
- Se precisar de dashboard/retries complexos, migrar para Prefect

## Consequências

**Positivas:**
- Sem infra extra (sem Redis, Celery, Prefect)
- Confiável (cron + flock)
- Simples

**Negativas:**
- Sem dashboard de jobs
- Retry manual
- Cron depende do SO

**Neutras:**
- Precisa de monitoramento externo (UptimeRobot)

## Alternativas

- **Prefect:** rejeitado por infra extra prematura
- **Celery Beat:** rejeitado por dependência de Redis/RabbitMQ
- **Apenas API (sem cron):** rejeitado por dados ficarem desatualizados

## Referências

- `docs/process/ingesta-de-dados.md` - Agendamento
- `docs/process/deployment.md` - Worker service
