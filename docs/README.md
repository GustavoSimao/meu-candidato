# Documentação do projeto

Esta pasta é a raiz do projeto. Toda decisão e toda especificação ficam aqui, e o código acompanha a documentação, nunca o contrário.

## Estrutura

| Pasta | O que vive aqui |
|---|---|
| `product/` | Visão, mercado, personas, features, roadmap. O que eu construo e por quê. |
| `architecture/` | Decisões de software. Cada decisão fica registrada em `architecture/adr/` como um registro de decisão. |
| `design/` | Experiência do usuário: fluxos, telas, wireframes. |
| `process/` | Como os dados fluem: ingestão, automação, agendamento, deploy. |
| `glossario.md` | Termos do domínio que uso neste projeto. |

Decisões que envolvem dados caem em `process/` quando são de fluxo e em `architecture/` quando são de modelagem.

## Documentos de referência

| Documento | O que é |
|---|---|
| `dicionario-dados.md` | Dicionário de dados: tabelas, colunas, mapeamento de fontes. |
| `configuration.md` | Referência de configuração: todas as variáveis de ambiente. |
| `development.md` | Guia do desenvolvedor: setup, estrutura, testes, estilo. |
| `troubleshooting.md` | Solução de problemas: erros comuns, FAQ, debug. |
| `api/reference.md` | Referência da API: endpoints, exemplos, convenções. |
| `../CHANGELOG.md` | Histórico de mudanças do projeto. |

## Como eu escrevo

- Escrevo em primeira pessoa. Quando registro uma decisão, uso a forma "decidi usar X porque Y".
- Um documento cobre um tema. Quando um tema cresce demais, ele vira uma subpasta com subdocumentos.
- Nenhum documento entra como aprovado sem minha revisão. Rascunho não é decisão.
