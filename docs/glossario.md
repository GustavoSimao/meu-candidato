# Glossário do Meu Candidato

## Termos do Domínio

### Políticos e Mandatos

**Deputado Federal**
Representante eleito pela Câmara dos Deputados. Mandato de 4 anos. Cada estado tem número de deputados proporcional à população.

**Senador**
Representante eleito pelo Senado Federal. Mandato de 8 anos. Cada estado tem 3 senadores, totalizando 81.

**Mandato**
Período em que um político exerce um cargo eletivo. Pode ser na Câmara (deputado federal) ou no Senado (senador). Um político pode ter múltiplos mandatos ao longo da vida.

**Suplente**
Político eleito como reserva. Assume o cargo se o titular se licenciar, renunciar ou falecer.

**UF (Unidade Federativa)**
Sigla do estado brasileiro (ex: SP, RJ, MG, DF). Usada para identificar a origem do mandato.

**Partido Político**
Organização política à qual o deputado/senador está filiado. Pode mudar durante o mandato.

**CPF do Político**
Cadastro de Pessoa Física. Identificador único do político. Usado como chave natural para deduplicação.

**Número Eleitoral**
Número pelo qual o candidato concorre nas eleições. Único por partido/UF.

### Atividade Legislativa

**Proposição**
Qualquer matéria submetida à apreciação do Legislativo. Tipos principais:
- **PL** (Projeto de Lei): Cria, altera ou revoga leis
- **PEC** (Proposta de Emenda à Constituição): Altera a Constituição
- **MPV** (Medida Provisória): Tem força de lei, editada pelo Presidente
- **PDC** (Projeto de Decreto Legislativo): Para assuntos de competência exclusiva do Congresso
- **REQ** (Requerimento): Solicita informações ou providências
- **PLP** (Projeto de Lei Complementar): Para leis complementares
- **PRE** (Projeto de Resolução): Para regimento interno
- **RIC** (Regimento Interno): Regras de funcionamento

**Votação**
Registro de como cada parlamentar votou em uma proposição. Valores possíveis:
- **favor**: Votou a favor
- **contra**: Votou contra
- **abstencao**: Absteve-se
- **ausente**: Não compareceu
- **obstrucao**: Obstruiu a votação
- **art17**: Artigo 17 (licença)
- **desconhecido**: Valor não identificado

**Sessão Legislativa**
Reunião deliberativa onde ocorrem as votações. Tem número e data.

**External ID da Proposição**
Identificador único no formato `{siglaTipo}-{numero}-{ano}` (ex: `PL-1234-2024`). Chave natural para upsert.

**Tramitação**
Processo de tramitação da proposição nas comissões e plenário. Status exemplos: "Em tramitação", "Aprovado", "Arquivado", "Vetado".

### Financeiro

**Cota de Auxílio Parlamentar (Cota para Exercício da Atividade Parlamentar)**
Verba mensal para custear despesas do mandato (gabinete, viagem, combustível, etc.). Valor varia por UF.

**Despesa Parlamentar**
Gasto individual realizado com a cota. Tem tipo, valor, data, fornecedor e documento fiscal.

**Tipos de Despesa**:
- Combustíveis
- Manutenção
- Telefonia
- Material de escritório
- Passagens aéreas
- Locação de veículos
- Serviços de terceiros
- Alimentação
- Hospedagem
- Outros

**Financiamento de Campanha**
Recursos arrecadados para campanha eleitoral. Fonte: TSE (DivulgaCandContas).

**Tipos de Doador**:
- **Pessoa física**: CPF do doador
- **Pessoa jurídica**: CNPJ do doador (proibido para eleições majoritárias desde 2015)
- **Fundo partidário**: Recursos do Fundo Partidário
- **Recursos próprios**: Dinheiro do próprio candidato

**Valor em Centavos**
Todos os valores monetários armazenados como inteiros (centavos) para evitar problemas de ponto flutuante. Ex: R$ 1.234,56 = 123456 centavos.

### Engajamento

**Follow (Seguir)**
Ação do usuário de acompanhar um político. Gera notificações de novas votações/proposições.

**Badge (Insígnia/Conquista)**
Reconhecimento automático baseado em métricas de atividade legislativa:
- **Ficha Limpa**: Sem condenações (requer integração externa)
- **Presença Alta**: Compareceu à maioria das votações
- **Legislador Ativo**: Apresentou muitas proposições

**Badge Rule (Regra de Badge)**
Critério configurável para concessão de badge. Ex: "Apresentou mais de 10 proposições no ano" → threshold=10.

**Dashboard do Usuário**
Tela pessoal com políticos seguidos, badges conquistados e atividade recente.

### Ingestão de Dados

**Pipeline de Ingestão**
Processo ETL (Extract, Transform, Load) para trazer dados das APIs oficiais para o banco.

**Raw Data (Dados Brutos)**
Resposta original da API salva em JSON para auditoria e reprocessamento. Organizada por fonte/dataset/data/página.

**Quarentena**
Registros que falharam na validação/transformação. Salvos em Parquet com lista de erros para análise posterior.

**Upsert**
Operação "insert or update". Usa chave natural (CPF, external_id) para evitar duplicatas.

**Rate Limiting**
Controle de taxa de requisições para não sobrecarregar APIs oficiais (ex: 30 req/s).

**Backoff Exponencial**
Estratégia de retry com espera crescente (1s, 2s, 4s, 8s...) até máximo configurado.

**Job de Ingestão**
Registro de uma execução do pipeline. Status: pending → running → completed/failed/partial.

**Dados Incrementais**
Ingestão apenas de dados novos/alterados desde última execução (usando dataInicio/dataApresentacaoInicio).

## Termos Técnicos

**DDD (Domain-Driven Design)**
Arquitetura centrada no domínio. Separação em bounded contexts (Político, Legislativo, Financeiro, Engajamento, Ingestão).

**Bounded Context**
Limite explícito de um modelo de domínio. Cada contexto tem suas próprias entidades, value objects, repositórios e serviços.

**Entity (Entidade)**
Objeto com identidade única que muda ao longo do tempo (ex: Politician, Vote).

**Value Object (Objeto de Valor)**
Objeto imutável sem identidade própria, definido apenas por seus atributos (ex: CPF, UF, Amount).

**Aggregate (Agregado)**
Cluster de entidades/value objects tratado como unidade para consistência (ex: Politician com Mandates).

**Repository (Repositório)**
Abstração de persistência. Interface no domínio, implementação na infraestrutura (SQLAlchemy).

**Service (Serviço de Aplicação)**
Caso de uso / orquestração. Coordena repositórios e entidades. Não contém regra de negócio complexa.

**DTO (Data Transfer Object)**
Schema de entrada/saída da API (Pydantic). Validação de contrato.

**ORM (Object-Relational Mapping)**
Mapeamento objeto-relacional. SQLAlchemy 2.0 async.

**Migration (Migração)**
Versionamento do schema do banco. Alembic.

**Async/Await**
Programação assíncrona nativa do Python. Usado em toda camada de dados (FastAPI, SQLAlchemy, httpx).

**Dependency Injection (Injeção de Dependência)**
FastAPI Depends() para fornecer repositórios, serviços, sessão de banco.

**Structured Logging (Log Estruturado)**
Logs em JSON com structlog. Campos: level, event, timestamp, contexto.

**Pydantic v2**
Validação de dados, serialização, settings management (pydantic-settings).

**SQLAlchemy 2.0**
ORM moderno com tipagem nativa, async, select() em vez de query().

**PostgreSQL 16**
Banco de dados relacional. Recursos: JSONB, índices compostos, constraints, partitioning.

**Docker Compose**
Orquestração de containers para desenvolvimento (API, DB, Worker).

**OpenAPI/Swagger**
Documentação automática da API. Disponível em `/docs` e `/redoc`.

## Siglas e Abreviações

| Sigla | Significado |
|-------|-------------|
| API | Application Programming Interface |
| CPF | Cadastro de Pessoa Física |
| CNPJ | Cadastro Nacional de Pessoa Jurídica |
| DDD | Domain-Driven Design |
| DTO | Data Transfer Object |
| ETL | Extract, Transform, Load |
| FK | Foreign Key (Chave Estrangeira) |
| ORM | Object-Relational Mapping |
| PK | Primary Key (Chave Primária) |
| REPL | Read-Eval-Print Loop |
| SQL | Structured Query Language |
| TSE | Tribunal Superior Eleitoral |
| UF | Unidade Federativa |
| UX | User Experience |

---

*Este glossário é um documento vivo. Adicione termos conforme o domínio evolui.*