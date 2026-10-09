# ADR-008: Documentação Primeiro

## Status

Aceito

## Contexto

O projeto precisa de um processo que garanta que código e documentação estejam alinhados.

## Decisão

Decidi que **documentação vem antes do código**.

- Toda decisão arquitetural é registrada em ADR antes da implementação
- Toda feature é documentada antes do código
- O código segue a documentação, nunca o contrário
- Documentação em PT-BR, erros/logs em inglês

**Motivação:**
- Decisões são revisáveis antes do código
- Documentação não fica para depois
- Código é consequência da decisão documentada

## Consequências

**Positivas:**
- Decisões explícitas e revisáveis
- Alinhamento entre docs e código
- Histórico de decisões (ADRs)

**Negativas:**
- Mais trabalho inicial
- Docs podem atrasar código

**Neutras:**
- Requer disciplina

## Alternativas

- **Código primeiro, docs depois:** rejeitado por docs nunca ficarem prontas
- **Sem docs:** rejeitado por perda de conhecimento

## Referências

- `docs/README.md` - "o código acompanha a documentação, nunca o contrário"
