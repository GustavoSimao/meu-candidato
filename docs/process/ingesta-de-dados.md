# Ingestão de dados

## Visão geral

Pipeline de ingestão das três fontes oficiais: Câmara dos Deputados, Senado Federal e TSE. O objetivo é transformar dados dispersos em um modelo unificado, versionado e auditável.

## Fontes

| Órgão | Fonte | Formato | Frequência |
|-------|-------|---------|------------|
| Câmara dos Deputados | `dadosabertos.camara.leg.br` | CSV, JSON, XML | Diária |
| Senado Federal | `legis.senado.leg.br` | XML, JSON | Diária |
| TSE | `cdn.tse.jus.br` | CSV, ZIP | Por eleição |

## Modelo de dados unificado

Tabelas principais:
- `politician` — identidade, partido, mandatos, cargos
- `mandate` — período, casa legislativa, UF, suplência
- `vote` — proposição, data, sessão, voto do político
- `proposition` — tipo, autor, tema, status, tramitação
- `expense` — cota parlamentar, categoria, valor, data, fornecedor
- `campaign_finance` — eleição, doador tipo, valor, origem

Chaves naturais onde possível (CPF do político, ID da proposição). Surrogates para joins internos.

## Pipeline

1. **Extração** — Download via HTTP (requests/httpx). Respeito a rate limits. Retry com backoff exponencial. Checksum do arquivo bruto salvo em `raw/`.
2. **Validação** — Schema check com Pydantic. Linhas inválidas vão para quarentena com log estruturado.
3. **Transformação** — Normalização de colunas, parse de datas, mapeamento de enums (tipo de despesa, voto, partido). Deduplicação por chave natural.
4. **Carga** — Upsert no PostgreSQL via SQLAlchemy. Transação por lote. Índices em chaves de busca (político, data, UF).
5. **Versionamento** — Snapshot diário das tabelas críticas em schema `history/`. Permite auditoria e rollback.

## Agendamento

- Câmara/Senado: job diário às 03:00 BRT
- TSE: job sob demanda (novas eleições) + verificação mensal
- Orquestração com Prefect ou cron + lock file

## Monitoramento

- Métricas: linhas processadas, taxa de erro, latência fim-a-fim
- Alertas: falha de download, schema mismatch, queda brusca de volume
- Logs estruturados em JSON para Loki/ELK

## Qualidade de dados

- Regras de validação por fonte (ex.: CPF válido, UF conhecida, valor não negativo)
- Relatórios de divergência entre fontes (ex.: político na Câmara mas não no Senado)
- Dicionário de dados versionado no repo (`docs/dicionario-dados.md`)

## Rollback

Snapshot anterior em `history/` permite `TRUNCATE + INSERT` em minutos. Script de rollback versionado.