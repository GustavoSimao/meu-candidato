# ADR-010: Deploy com Docker Compose

## Status

Aceito

## Contexto

O projeto precisa de um ambiente de deploy que seja simples de operar e portável.

## Decisão

Decidi usar **Docker Compose** como ambiente de deploy.

- `docker-compose.yml` com: `api`, `worker`, `db`
- Imagem única para API e worker (mesmo código, comando diferente)
- PostgreSQL 16 como banco
- `restart: unless-stopped` para auto-recuperação
- Resource limits: `mem_limit: 1g`, `cpus: 1` para worker

**Motivação:**
- Simples: um comando sobe tudo
- Portável: funciona em qualquer servidor com Docker
- Sem lock-in de cloud
- Já existe `docker-compose.yml` no projeto

**Migração futura:**
- Se precisar de HA/escalabilidade, migrar para ECS/Cloud Run/K8s

## Consequências

**Positivas:**
- Simples de operar
- Portável
- Reprodutível
- Sem lock-in

**Negativas:**
- Single point of failure (um servidor)
- Sem auto-scaling
- Manual scaling

**Neutras:**
- Precisa de monitoramento externo

## Alternativas

- **Cloud managed (ECS/Cloud Run):** rejeitado por lock-in e custo prematuro
- **Kubernetes:** rejeitado por complexidade excessiva
- **VPS + systemd:** rejeitado por menos portável

## Referências

- `docs/process/deployment.md` - Guia de deploy
- `docker-compose.yml`
