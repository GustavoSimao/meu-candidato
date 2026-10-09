# Changelog

Todos os cambios notáveis neste projeto serão documentados neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adota [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Added

- Documentação completa do projeto (Sprint 1):
  - `docs/dicionario-dados.md` - Dicionário de dados com mapeamento de fontes
  - `docs/configuration.md` - Referência de configuração
  - `docs/development.md` - Guia do desenvolvedor
  - `docs/troubleshooting.md` - Solução de problemas
  - `docs/api/reference.md` - Referência da API
  - `docs/process/deployment.md` - Guia de deploy
  - `docs/architecture/adr/` - Registros de decisão arquitetural (ADR-002 a ADR-011)
  - `CHANGELOG.md` - Este arquivo

### Changed

- `docs/README.md` - Atualizado para refletir nomes de pastas em inglês

### Fixed

- Inconsistência entre `docs/README.md` (nomes PT-BR) e pastas reais (nomes EN)

## [0.1.0] - 2026-10-07

### Added

- API FastAPI com endpoints de políticos, atividade legislativa, financeiro, engajamento
- Ingestão de dados da Câmara dos Deputados (deputados, proposições, despesas)
- Armazenamento raw e quarentena (Parquet)
- Modelo de dados unificado (politicians, mandates, propositions, votes, expenses, campaign_finances)
- Migrações Alembic (schema inicial)
- Docker Compose (api + db)
- Testes unitários (domínio, serviços, router)

### Architecture

- Clean Architecture (domain/application/infrastructure/api)
- SQLAlchemy 2.0 async + Alembic
- Pydantic v2 + Pydantic Settings
- structlog (logs JSON estruturados)
- tenacity (retry com backoff exponencial)
