# 001 - Backend em Python com FastAPI

## Contexto

O projeto precisa de uma API para servir os dados agregados de políticos, votações, proposições, despesas e financiamento. A escolha da tecnologia afeta velocidade de desenvolvimento, manutenibilidade e ecossistema de dados.

## Decisão

Decidi usar Python com FastAPI para o backend.

Stack padrão: FastAPI + Uvicorn + Pydantic + SQLAlchemy + Alembic.

Processamento pesado (ingestão): async workers simples (BackgroundTasks / scripts assíncronos), sem Celery/RQ por enquanto.

## Justificativa

Python tem o melhor ecossistema para ingestão e processamento de dados (pandas, polars, requests, httpx). A maioria das fontes oficiais (Câmara, Senado, TSE) fornece CSV, JSON ou APIs REST que se mapeiam direto para ferramentas Python.

FastAPI traz tipagem nativa com Pydantic, documentação automática (OpenAPI/Swagger) e performance comparável a Node.js/Go via Starlette + Uvicorn. A curva de aprendizado é baixa e a produtividade alta.

SQLAlchemy + Alembic dão ORM maduro e migrações versionadas. Pydantic v2 valida entrada e saída. Uvicorn roda em produção com workers.

Alternativas consideradas:
- Node.js/Express: bom ecossistema, mas tipagem fraca sem TypeScript e processamento de dados mais verboso.
- Go: performance superior, mas verboso para ETL e ecossistema de dados menor.
- PHP/Laravel: não é ideal para pipelines de dados pesados e o foco do portfólio é Python.

Async workers simples vs Celery/RQ: a ingestão é diária, previsível e em lote. Workers assíncronos simples (scripts rodando em containers, agendados via cron) resolvem sem infra extra. Celery adiciona Redis, broker, complexidade operacional. Pode migrar depois se volume ou confiabilidade exigirem.

## Consequências

Positivas:
- Integração direta com pandas/polars para ingestão
- Tipagem end-to-end com Pydantic
- Documentação de API automática
- Deploy simples com Docker
- Baixa complexidade operacional (sem Redis/Celery)

Negativas:
- GIL limita paralelismo CPU-bound (resolvido com multiprocessing ou offloading para workers)
- Menos maduro que Django/Flask para apps tradicionais (não é o caso aqui)
- Sem retry/agendamento nativo para jobs (aceitável para ingestão diária controlada)