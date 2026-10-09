# Design - Meu Candidato

## Princípios de Design

1. **Clareza sobre densidade** - O cidadão comum não tem formação em direito ou política. Cada tela deve responder uma pergunta clara sem jargão.
2. **Progressive disclosure** - Resumo primeiro, detalhe sob demanda. Lista → Card → Detalhe.
3. **Dados como protagonista** - Interface limpa que deixa os números e fatos falarem. Sem opinião, sem cores partidárias.
4. **Acessibilidade** - Contraste alto, fontes legíveis, navegação por teclado, screen readers.
5. **Mobile-first** - Maioria dos acessos será por celular. Touch targets adequados, scroll natural.

## Componentes Principais

### Header/Navigation
- Logo + nome "Meu Candidato"
- Busca global (político, partido, UF)
- Link para "Meus políticos" (dashboard do usuário logado)

### Busca de Políticos
- Filtros visuais: UF (mapa ou dropdown), Partido (autocomplete), Cargo (Deputado/Senador)
- Lista paginada com cards: Foto, Nome, Partido, UF, Badges
- Ordenação: Alfabética, Mais votado, Mais ativo

### Perfil do Político
**Aba Visão Geral** (default):
- Foto oficial, Nome, Cargo atual, Partido, UF
- Badges conquistadas (ícones com tooltip)
- Botão "Seguir"
- Resumo rápido: Total proposições, Presença em votações, Gasto total cota

**Aba Atividade Legislativa**:
- Sub-abas: Votações | Proposições
- Filtros por data, casa, tipo
- Cards expansíveis com resumo em português

**Aba Financeiro**:
- Sub-abas: Cota Parlamentar | Campanha
- Gráficos: Pizza (por tipo), Linha (temporal)
- Tabela detalhada paginada

**Aba Mandatos**:
- Linha do tempo visual
- Cargo, Casa, UF, Período, Suplência

### Dashboard do Usuário (logado)
- Lista de políticos seguidos com status badge
- Feed de atividade: "João Silva votou a favor do PL 1234/2024"
- Notificações de novos badges

### Estados Vazios
- "Nenhum político encontrado" → Sugestão de ajuste de filtros
- "Você não segue ninguém" → Link para busca
- "Sem dados para este período" → Explicação

## Fluxos de Navegação

Ver `user-flows.md` para detalhes.

## Paleta de Cores (Neutra)

| Uso | Cor | Hex |
|-----|-----|-----|
| Primária (links, botões) | Azul neutro | #2563EB |
| Sucesso (badges positivas) | Verde | #16A34A |
| Alerta (gastos altos) | Laranja | #EA580C |
| Erro (falhas) | Vermelho | #DC2626 |
| Texto principal | Cinza escuro | #1F2937 |
| Texto secundário | Cinza médio | #6B7280 |
| Fundo | Branco | #FFFFFF |
| Fundo alternado | Cinza claro | #F9FAFB |
| Borda | Cinza claro | #E5E7EB |

**Não usar**: Cores partidárias (vermelho PT, azul PSDB, verde PV, etc.)

## Tipografia

- **Títulos**: Inter, 600 weight
- **Corpo**: Inter, 400 weight
- **Dados/números**: JetBrains Mono (tabular-nums)
- **Escala**: 12px (caption) → 14px (body) → 16px (body-lg) → 20px (h3) → 24px (h2) → 32px (h1)

## Ícones

- Lucide React (leve, consistente)
- Badges: Shield (Ficha Limpa), CheckCircle (Presença), FileText (Legislador)
- Navegação: ChevronRight, ChevronDown, Search, Filter, Star, Bell

## Responsividade

| Breakpoint | Largura | Layout |
|------------|---------|--------|
| Mobile | < 640px | Single column, bottom nav |
| Tablet | 640-1024px | Two column sidebar + content |
| Desktop | > 1024px | Three column (filters, list, detail) |

## Acessibilidade (WCAG 2.1 AA)

- Contraste mínimo 4.5:1 (texto), 3:1 (UI)
- Focus visible em todos elementos interativos
- ARIA labels em ícones sem texto
- Alt text em todas imagens (foto oficial: "Foto oficial do deputado João Silva")
- Ordem de tab lógica
- Skip to main content link

## Estados de Loading

- Skeleton screens para listas e cards
- Spinner inline para ações (follow, badge check)
- Progress bar para ingestão (página admin)

## Tratamento de Erros

- Toast para erros de rede (tenta novamente em 5s)
- Inline validation para formulários
- Página 404 amigável com busca
- Página 500 com ID do erro para suporte