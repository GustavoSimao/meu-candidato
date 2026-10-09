# ADR-002: Unificação de Políticos por CPF

## Status

Aceito

## Contexto

O mesmo político pode existir em múltiplas fontes:
- Câmara dos Deputados (deputado)
- Senado Federal (senador)
- TSE (candidato a diversos cargos)

Precisamos de um modelo unificado de `politicians` que represente uma pessoa única, independente da fonte.

## Decisão

Decidi unificar políticos por **CPF exato**. Uma linha por CPF na tabela `politicians`.

- `politicians.cpf` é a chave natural de unificação
- `politicians.external_id` armazena o ID da Câmara/Senado (quando disponível)
- Quando o CPF não está disponível, o registro fica separado até obter o CPF
- Não uso matching fuzzy (nome + partido + UF) para evitar falsos positivos

## Consequências

**Positivas:**
- CPF é identificador canônico no Brasil
- TSE já usa CPF, facilita integração
- Simples e determinístico

**Negativas:**
- Alguns registros históricos podem não ter CPF
- Câmara/Senado podem não retornar CPF em todos os endpoints
- Registros sem CPF ficam duplicados até correção

**Neutras:**
- Precisa de processo de resolução manual para registros sem CPF

## Alternativas

- **CPF + fuzzy name match:** rejeitado por risco de falsos positivos
- **Tabelas separadas por fonte:** rejeitado por duplicação e complexidade de queries
- **Core + source mappings:** rejeitado por complexidade excessiva para o caso de uso

## Referências

- `docs/dicionario-dados.md` - Mapeamento de fontes
- `docs/process/ingestao-tse.md` - Resolução por CPF
