# ADR-003: Clean Architecture

## Status

Aceito

## Contexto

O projeto precisa de uma estrutura que separe:
- Lógica de negócio (domínio)
- Casos de uso (aplicação)
- Persistência (infraestrutura)
- API (interface)

## Decisão

Decidi usar **Clean Architecture** com camadas:

```
modulo/
├── domain/            # Entidades e value objects (puro Python)
├── application/       # Casos de uso, DTOs, ports (interfaces)
├── infrastructure/    # ORM, repositórios, mappers (SQLAlchemy)
└── api/               # Rotas FastAPI, dependências
```

**Regras:**
- `domain/` não depende de nenhum framework
- `application/` depende apenas de `domain/`
- `infrastructure/` implementa ports de `application/`
- `api/` depende de `application/`

**Fluxo:** `API (DTO)` → `Application Service` → `Domain Entity` → `Repository (ORM)` → `Database`

## Consequências

**Positivas:**
- Testável (domínio puro, sem DB/HTTP)
- Substituível (trocar ORM ou framework sem mudar regras)
- Clara separação de responsabilidades

**Negativas:**
- Mais arquivos (mappers, ports, DTOs)
- Curva de aprendizado
- Boilerplate

**Neutras:**
- Requer disciplina para não violar dependências

## Alternativas

- **MVC tradicional:** rejeitado por acoplamento entre modelo e ORM
- **Repository pattern simples:** rejeitado por não separar domínio de aplicação
- **Monolito sem camadas:** rejeitado por dificuldade de testes

## Referências

- `docs/development.md` - Estrutura do projeto
- `app/politician/` - Exemplo de módulo completo
