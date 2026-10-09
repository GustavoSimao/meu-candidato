# ADR-007: UTC em Todos os Timestamps

## Status

Aceito

## Contexto

A API opera em múltiplos contextos de tempo:
- Ingestão agendada às 03:00 BRT
- Datas de mandato, votação, despesa
- Timestamps de criação/atualização

## Decisão

Decidi armazenar **todos os timestamps em UTC**.

- `TIMESTAMP` columns: UTC
- `DATE` columns: sem timezone (apenas data)
- Cron: timezone local do servidor (`TZ=America/Sao_Paulo`)
- Display: conversão UTC → BRT no cliente

**Motivação:**
- UTC é padrão universal
- Sem ambiguidade de DST
- Servidor pode estar em qualquer timezone
- Datas de negócio (mandato, votação) são date-only

## Consequências

**Positivas:**
- Sem ambiguidade
- Portável entre timezones
- Padrão da indústria

**Negativas:**
- Conversão no display
- Cron precisa de `TZ` configurado

**Neutras:**
- Precisa de convenção clara

## Alternativas

- **BRT (America/Sao_Paulo):** rejeitado por problemas de DST e ambiguidade
- **Date-only:** rejeitado para timestamps (perde precisão)
- **Misturado:** rejeitado por inconsistência

## Referências

- `docs/dicionario-dados.md` - Convenções
- `docs/configuration.md` - `TZ`
