# User Flows - Meu Candidato

## Fluxo 1: Busca e Descoberta de Político

### 1.1 Busca Direta (Home → Perfil)
```
Home
  ├─ Digita nome/partido/UF na busca global
  ├─ Enter / Clica "Buscar"
  └─ Lista de resultados (cards)
       ├─ Clica no card do político
       └─ Perfil do Político (Aba Visão Geral)
```

### 1.2 Busca com Filtros (Home → Lista Filtrada → Perfil)
```
Home
  ├─ Clica "Filtros avançados"
  ├─ Seleciona: UF=SP, Partido=PT, Cargo=Deputado Federal
  ├─ Aplica filtros
  ├─ Lista paginada (20 por página)
  ├─ Navega páginas / Ordena
  ├─ Clica no card
  └─ Perfil do Político
```

### 1.3 Descoberta por Badge (Home → Badge → Lista → Perfil)
```
Home
  ├─ Vê seção "Destaques" com badges
  ├─ Clica "Legislador Ativo"
  ├─ Lista de políticos com essa badge
  ├─ Clica em um
  └─ Perfil do Político (badge destacada)
```

---

## Fluxo 2: Acompanhamento de Político (Follow)

### 2.1 Follow Direto (Perfil → Seguindo)
```
Perfil do Político
  ├─ Clica botão "Seguir" (ícone estrela/olho)
  ├─ Toast: "Agora você acompanha João Silva"
  ├─ Botão muda para "Seguindo" (estado ativo)
  └─ Político aparece no Dashboard
```

### 2.2 Unfollow (Dashboard → Perfil → Deixar de seguir)
```
Dashboard (Meus Políticos)
  ├─ Vê lista de seguidos
  ├─ Clica "Deixar de seguir" no card
  ├─ Confirma no modal
  ├─ Toast: "Você parou de acompanhar João Silva"
  └─ Político removido da lista
```

### 2.3 Notificação de Nova Atividade (Push/Email → Perfil)
```
Notificação: "João Silva votou a favor do PL 1234/2024"
  ├─ Clica na notificação
  ├─ Abre Perfil → Aba Atividade Legislativa → Votação destacada
  └─ Lê resumo da votação
```

---

## Fluxo 3: Análise de Atividade Legislativa

### 3.1 Votações Recentes (Perfil → Votações)
```
Perfil → Aba Atividade Legislativa → Votações
  ├─ Lista cronológica (mais recente primeiro)
  ├─ Filtra: "Últimos 30 dias", "Câmara", "Tema: Saúde"
  ├─ Vê cards: Data | Proposição | Resumo | Voto (badge colorida)
  ├─ Clica em uma votação
  └─ Detalhe da Votação: Proposição completa, Todos os votos, Contexto
```

### 3.2 Proposições do Político (Perfil → Proposições)
```
Perfil → Aba Atividade Legislativa → Proposições
  ├─ Contador total no cabeçalho
  ├─ Filtra: Tipo=PL, Ano=2024, Status=Em tramitação
  ├─ Lista: Título | Tipo | Data | Status | Link oficial
  ├─ Clica em uma
  └─ Detalhe: Ementa, Resumo, Autor, Tramitação, Votações relacionadas
```

### 3.3 Comparação Implícita (Mental do Usuário)
```
Usuário abre 2 abas: Deputado A vs Deputado B
  ├─ Compara: Total proposições, Presença, Gasto cota
  └─ Decide quem acompanhar mais de perto
```

---

## Fluxo 4: Análise Financeira

### 4.1 Cota Parlamentar (Perfil → Financeiro → Cota)
```
Perfil → Aba Financeiro → Cota Parlamentar
  ├─ KPIs: Total gasto, Mês atual, Média mensal
  ├─ Gráfico pizza: Distribuição por tipo
  ├─ Gráfico linha: Evolução mensal (12 meses)
  ├─ Tabela: Tipo | Total | Qtd | Média | Última despesa
  ├─ Alerta visual se tipo > 2x média (ex: Passagens aéreas)
  └─ Clica em tipo → Detalhamento desse tipo
```

### 4.2 Financiamento de Campanha (Perfil → Financeiro → Campanha)
```
Perfil → Aba Financeiro → Campanha
  ├─ KPIs: Total arrecadado, Eleição mais cara
  ├─ Gráfico barras: Por tipo de doador (PF, PJ, Fundo, Próprios)
  ├─ Gráfico linha: Por ano de eleição
  ├─ Top 10 doadores: Nome | Tipo | Valor | Ano
  └─ Filtra por eleição específica (2022, 2020, etc.)
```

---

## Fluxo 5: Engajamento e Gamificação

### 5.1 Conquista de Badge (Automático + Notificação)
```
Sistema (job noturno)
  ├─ Calcula métricas de todos políticos
  ├─ Aplica regras de badge
  ├─ Concede novas badges
  ├─ Para usuários que seguem: Cria notificação
  └─ Toast/Email: "João Silva conquistou 'Legislador Ativo'!"
```

### 5.2 Visualização de Badges (Perfil → Badges)
```
Perfil → Seção Badges (Visão Geral)
  ├─ Grid de badges conquistadas (ícone + nome + data)
  ├─ Tooltip: Regra da badge + métrica atual
  ├─ Badges não conquistadas: Cinza com progresso (ex: "8/10 proposições")
  └─ Link "Ver todas as regras" → Página de regras públicas
```

### 5.3 Regras Públicas (Transparência)
```
Rodapé → "Como funcionam as badges"
  ├─ Lista todas as badges com regra e threshold
  ├─ Exemplo: "Legislador Ativo: >10 proposições/ano"
  └─ Link para API de regras (para desenvolvedores)
```

---

## Fluxo 6: Onboarding (Primeiro Acesso)

### 6.1 Usuário Não Logado
```
Home (primeira vez)
  ├─ Hero: "Acompanhe quem você elegeu"
  ├─ Busca proeminente
  ├─ Exemplos: "Busque por 'São Paulo'", "Filtre por 'Meio Ambiente'"
  ├─ CTA: "Crie sua conta para seguir políticos"
  └─ Footer: Sobre, Fontes, Privacidade
```

### 6.2 Cadastro (Email → Confirmação → Dashboard)
```
Clica "Criar conta"
  ├─ Modal: Email + Senha
  ├─ Envia magic link / código
  ├─ Usuário confirma
  ├─ Redireciona para Dashboard vazio
  ├─ Empty state: "Comece seguindo seus políticos"
  ├─ Botão "Buscar políticos"
  └─ Volta ao Fluxo 1
```

---

## Fluxo 7: Admin/Operação (Interno)

### 7.1 Monitoramento de Ingestão
```
Admin → Ingestion Jobs
  ├─ Lista jobs recentes (status, registros, duração)
  ├─ Filtra: Fonte=Câmara, Dataset=Despesas, Status=Failed
  ├─ Clica em job falho → Detalhes + Quarentena
  ├─ Reprocessa quarantena após correção
  └─ Métricas: Volume/dia, Taxa erro, Latência
```

### 7.2 Gestão de Badge Rules
```
Admin → Badge Rules
  ├─ Lista regras ativas/inativas
  ├─ Cria nova: Tipo, Nome, Descrição, Condição, Threshold
  ├─ Edita threshold (ex: 10 → 15 proposições)
  ├─ Desativa regra obsoleta
  └─ Preview: Quantos políticos ganhariam hoje
```

---

## Estados de Edge Cases

| Cenário | Comportamento |
|---------|---------------|
| Político sem foto | Placeholder com iniciais do nome |
| Político sem votações | "Nenhuma votação registrada no período" |
| Político sem proposições | "Nenhuma proposição apresentada" |
| Cota zerada | "Sem despesas declaradas neste período" |
| Campanha zerada | "Sem financiamento declarado" |
| API externa fora | Cache stale + badge "Dados desatualizados" |
| Usuário offline | Service worker + dados em cache |
| Busca sem resultados | Sugestões: "Tente 'SP' ou 'PT'" |

---

## Métricas de Sucesso por Fluxo

| Fluxo | Métrica Principal | Meta |
|-------|-------------------|------|
| Busca | Tempo até primeiro perfil | < 10s |
| Follow | Taxa follow por visita | > 15% |
| Atividade | Páginas por sessão | > 3 |
| Financeiro | Tempo na aba | > 60s |
| Badges | Badges por usuário | > 2 |
| Onboarding | Conclusão cadastro | > 40% |