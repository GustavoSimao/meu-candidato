# ADR-006: Valores Monetários em Centavos (Inteiro)

## Status

Aceito

## Contexto

Despesas parlamentares e financiamento de campanha envolvem valores monetários. Precisamos de um formato de armazenamento preciso.

## Decisão

Decidi armazenar valores monetários como **inteiros em centavos**.

- `expenses.amount` = Integer (centavos)
- `campaign_finances.amount` = Integer (centavos)
- Exemplo: R$ 1.234,56 → `123456`
- Conversão para string BRL no display

**Motivação:**
- Inteiros são exatos (sem erros de ponto flutuante)
- Rápido para agregação (SUM, AVG)
- Padrão em sistemas financeiros
- Schema já usa `INTEGER`

## Consequências

**Positivas:**
- Precisão total (sem rounding errors)
- Agregação eficiente
- Comparação exata

**Negativas:**
- Divisão por 100 no display
- Formatação BRL no cliente

**Neutras:**
- Precisa de convenção clara (documentada)

## Alternativas

- **Numeric(15,2):** rejeitado por storage maior e slower que int
- **Float:** rejeitado por erros de arredondamento (inaceitável para dinheiro)
- **String:** rejeitado por não permitir agregação

## Referências

- `docs/dicionario-dados.md` - Convenções
- `docs/process/ingestao-camara.md` - "Converter valor para centavos (int)"
