# ADR-009: Sem Redis

## Status

Aceito

## Contexto

A ingestão precisa de cache para mapeamentos `external_id` → `id` interno. A documentação lista "Cache de ext_to_int maps em Redis" como melhoria.

## Decisão

Decidi **não usar Redis** agora.

- Mapeamentos `external_id` → `id` são mantidos em memória (dict) durante a execução do worker
- Cache é por execução (não persiste entre runs)
- Redis só seria necessário para workers paralelos compartilhando estado

**Motivação:**
- Mapeamentos são necessários apenas durante a ingestão
- Um worker (decisão ADR-010) não precisa de cache compartilhado
- Redis adiciona infra sem ganho imediato

**Migração futura:**
- Se adicionar workers paralelos, adicionar Redis para cache compartilhado

## Consequências

**Positivas:**
- Sem infra extra
- Simples
- Sem dependência de Redis

**Negativas:**
- Cache perdido entre runs (re-fetch)
- Não escala para múltiplos workers

**Neutras:**
- Precisa reavaliar se adicionar paralelismo

## Alternativas

- **Redis:** rejeitado por infra extra prematura
- **SQLite cache:** rejeitado por I/O de arquivo
- **Cache em DB:** rejeitado por queries extras

## Referências

- ADR-010: Worker singleton
- `docs/process/ingestao-camara.md` - Próximas melhorias
