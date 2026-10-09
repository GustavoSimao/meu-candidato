# Visão do produto

## O que é

Meu Candidato é um site de facilitação para visualizar o agregado de dados que já existem e são públicos. Ele existe para facilitar a fiscalização, o acesso aos candidatos e identificar quais são os melhores em quem a pessoa deve votar.

O projeto nasceu de uma dificuldade simples: acompanhar o trabalho de quem foi eleito. O cidadão não sabe quantos projetos o deputado apresentou, como ele votou, ou para onde foi o dinheiro público. Eu construo o Meu Candidato para transformar esse dado disperso em algo claro.

## O problema

Hoje em sua grande maioria o governo não possui dados centralizados e quando estão, são de difícil entendimento para pessoas de menor instrução.

Os dados dos representantes eleitos são públicos, mas estão espalhados. A Câmara tem uma base, o Senado tem um sistema, o TSE tem outro. Cada um com formato próprio, sigla própria e linguagem técnica. Para o cidadão entender como o seu representante votou num único projeto, ele precisa cruzar vários portais e entender jargão legislativo. A maioria desiste, e a transparência termina ficando só no papel.

## Para quem

Cidadão sem domínio de jargão legislativo. É literalmente isso mesmo.

## O que eu entrego

- Perfil completo do político: mandatos, partido e dados básicos.
- Histórico de votações com um resumo claro de como o político votou.
- Proposições apresentadas, com link direto para a Câmara.
- Despesas da cota de auxílio parlamentar, com detalhamento por tipo.
- Financiamento de campanha, vindo do TSE.
- Badges automáticas baseadas em atividade legislativa. As regras de cada badge ficam documentadas em `produto/features/`.
- Follow de políticos, com um dashboard de quem eu acompanho.

Vamos detalhar cada feature em conversa separada, conforme suas necessidades de portfólio.

## O que eu não faço

O projeto não ira dizer para as pessoas em quem votar nem tomar partido. É exposição de fatos e comentários frios e sem tendência.

## Direção

Começaremos de cima para baixo: presidente e vice, federal, estadual, municipal. Não é só deputado ou presidente. Isso deve englobar todos os políticos.

## Como eu sei que está funcionando

Eu considero o produto funcionando quando um cidadão encontra um político em três cliques e vê as últimas votações dele na mesma tela. Vou revisar esse objetivo quando o produto evoluir, e registro aqui quando mudar.

## Fontes de dados

| Órgão | Fonte |
|---|---|
| Câmara dos Deputados | `dadosabertos.camara.leg.br` |
| Senado Federal | `legis.senado.leg.br` |
| TSE | `cdn.tse.jus.br` |

As três fontes são públicas. O detalhamento de como eu ingerio esses dados fica em `processos/ingesta-de-dados.md`.