# ADR-011: Ingestão Incremental

## Status

Aceito

## Contexto

A ingestão pode fazer backfill completo (toda a história) ou incremental (só novos dados). O volume de dados históricos é grande (TSE: ZIPs de 500MB+).

## Decisão

Decidi fazer **ingestão incremental a partir da data de deploy**.

- Câmara: `dataInicio` = data de deploy (rolling window)
- Senado: `DataApresentacaoInicio` = data de deploy
- TSE: `TSE_ELECTION_YEAR` = eleição mais recente
- Configurável via env vars para estender a janela

**Motivação:**
- Backfill completo é lento e caro (armazenamento, tempo)
- Dados recentes são os mais relevantes
- Janela configurável permite extensão futura
- Ingestão é idempotente (upsert), então estender a janela é seguro

**Migração futura:**
- Se precisar de histórico completo, rodar backfill uma vez com `TSE_ELECTION_YEAR=all`

## Consequências

**Positivas:**
- Primeira execução rápida
- Storage controlado
- Dados recentes disponíveis

**Negativas:**
- Sem histórico completo inicialmente
- Análise de tendências de longo prazo limitada

**Neutras:**
- Precisa de backfill se histórico for necessário

## Alternativas

- **Backfill completo:** rejeitado por volume e tempo
- **Janela fixa (2 anos):** rejeitado por menos flexível
- **Configurável por fonte:** rejeitado por mais complexidade de configuração

## Referências

- `docs/process/ingesta-de-dados.md` - Pipeline
- `docs/configuration.md` - `TSE_ELECTION_YEAR`
