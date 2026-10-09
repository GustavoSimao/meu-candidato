# ADR-004: API Pública (Sem Autenticação)

## Status

Aceito

## Contexto

A API agrega dados públicos de políticos brasileiros. Precisamos decidir se a API é aberta ou requer autenticação.

## Decisão

Decidi manter a API **pública, sem autenticação**.

- Nenhum endpoint requer API key, JWT ou OAuth
- `follows.user_id` é um UUID anônimo gerado pelo cliente (localStorage)
- Rate limiting: nenhum

**Motivação:**
- Dados são públicos (transparência)
- Simplicidade: sem gestão de usuários, senhas, tokens
- Adoção imediata: qualquer cliente pode consumir

**Migração futura:**
- Se houver abuso, adicionar API key (Option B)
- Mapear UUID anônimo → usuário real quando houver auth

## Consequências

**Positivas:**
- Simples de consumir
- Sem infra de auth
- Adoção rápida

**Negativas:**
- Risco de abuso (scraping, DDoS)
- Sem rate limit por usuário
- Sem identidade real (follows não recuperáveis)

**Neutras:**
- Precisa de monitoramento de abuso

## Alternativas

- **API key:** rejeitado por complexidade prematura
- **JWT/OAuth2:** rejeitado por excesso para dados públicos
- **Proxy-level auth:** rejeitado por dependência externa

## Referências

- `docs/api/reference.md` - Autenticação
